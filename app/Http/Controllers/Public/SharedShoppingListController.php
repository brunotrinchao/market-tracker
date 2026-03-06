<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\InvoiceItem;
use App\Models\Market;
use App\Models\Product;
use App\Models\ShoppingList;
use App\Models\ShoppingListItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

class SharedShoppingListController extends Controller
{
    public function show(string $token): View
    {
        $shoppingList = ShoppingList::query()
            ->where('share_token', $token)
            ->with(['items.product'])
            ->firstOrFail();

        $groups = [];
        $items = $shoppingList->items
            ->filter(fn (ShoppingListItem $item): bool => $item->product !== null)
            ->values();
        $productIds = $items
            ->pluck('product_id')
            ->map(fn ($value): int => (int) $value)
            ->unique()
            ->values()
            ->all();

        $selectedMarketIds = $items
            ->pluck('market_id')
            ->filter(fn ($value): bool => $value !== null)
            ->map(fn ($value): int => (int) $value)
            ->unique()
            ->values()
            ->all();

        $selectedMarkets = Market::query()
            ->with('addresses')
            ->whereIn('id', $selectedMarketIds)
            ->get()
            ->keyBy('id');

        $selectedOffersByProductMarket = $this->resolveSelectedOffersForProducts($productIds, $selectedMarketIds);
        $cheapestOffersByProduct = $this->resolveCheapestOffersForProducts($productIds);
        $lastPricesByProduct = $this->resolveLastPricesForProducts($productIds);

        foreach ($items as $item) {
            $productId = (int) $item->product_id;

            $selectedMarketId = $item->market_id ? (int) $item->market_id : null;
            $offer = null;
            if ($selectedMarketId !== null) {
                $offer = $selectedOffersByProductMarket[$productId . ':' . $selectedMarketId] ?? null;
            }
            if (! $offer) {
                $offer = $cheapestOffersByProduct[$productId] ?? null;
            }
            $fallbackLastPrice = $lastPricesByProduct[$productId] ?? null;

            $selectedMarket = $selectedMarketId ? $selectedMarkets->get($selectedMarketId) : null;

            $selectedMarketAddress = '-';
            if ($selectedMarket) {
                $address = $selectedMarket->addresses->first();
                $selectedMarketAddress = $address
                    ? trim(implode(', ', array_filter([
                        trim(implode(', ', array_filter([$address->street, $address->number]))),
                        $address->neighborhood,
                        trim(implode(' - ', array_filter([$address->city, $address->state]))),
                    ])))
                    : '-';
            }

            $marketId = (string) ($selectedMarketId ?? ($offer['market_id'] ?? 'sem_mercado'));
            $marketName = $selectedMarket?->name ?? ($offer['market_name'] ?? 'Sem supermercado');
            $marketAddress = $selectedMarketAddress !== '-' ? $selectedMarketAddress : ($offer['market_address'] ?? '-');
            $unitPrice = is_array($offer) ? ($offer['unit_price'] ?? null) : null;
            $displayUnitPrice = $unitPrice ?? $fallbackLastPrice;

            $groups[$marketId] ??= [
                'market_id' => $selectedMarketId ?? ($offer['market_id'] ?? null),
                'market_name' => $marketName,
                'market_address' => $marketAddress,
                'items' => [],
            ];

            $groups[$marketId]['items'][] = [
                'id' => (int) $item->id,
                'name' => (string) $item->product->name,
                'quantity' => number_format((float) $item->quantity, 3, ',', '.'),
                'unit_price' => $displayUnitPrice !== null
                    ? 'R$ ' . number_format((float) $displayUnitPrice, 2, ',', '.')
                    : '-',
                'subtotal' => $displayUnitPrice !== null
                    ? 'R$ ' . number_format((float) $displayUnitPrice * (float) $item->quantity, 2, ',', '.')
                    : '-',
            ];
        }

        return view('public.shopping-list', [
            'shoppingList' => $shoppingList,
            'groups' => array_values($groups),
        ]);
    }

    public function storeItem(Request $request, string $token): RedirectResponse
    {
        $shoppingList = ShoppingList::query()
            ->where('share_token', $token)
            ->firstOrFail();

        $validated = $request->validate([
            'product_id' => ['nullable', 'integer'],
            'product_name' => ['required', 'string', 'max:255'],
            'barcode' => ['nullable', 'string', 'max:64'],
            'quantity' => ['required', 'numeric', 'min:0.001'],
            'market_id' => ['nullable', 'integer'],
        ]);

        $productName = trim((string) $validated['product_name']);
        $barcode = trim((string) ($validated['barcode'] ?? ''));

        $product = null;
        $cosmosProduct = null;

        if (filled($validated['product_id'] ?? null)) {
            $product = Product::query()->find((int) $validated['product_id']);
        }

        if ($barcode !== '') {
            if (! $product) {
                $product = Product::query()->where('barcode', $barcode)->first();
            }

            if (! $product || ! $product->image) {
                $cosmosProduct = $this->fetchCosmosProduct($barcode);
            }
        }

        if (! $product) {
            $product = Product::query()
                ->whereRaw('LOWER(name) = ?', [mb_strtolower($productName)])
                ->first();
        }

        if (! $product) {
            $resolvedProductName = $productName;
            if (
                ($cosmosProduct['description'] ?? null)
                && preg_match('/^Produto\s+\d+$/u', $productName)
            ) {
                $resolvedProductName = (string) $cosmosProduct['description'];
            }

            $product = Product::query()->create([
                'name' => $resolvedProductName,
                'original_name' => $resolvedProductName,
                'barcode' => $barcode !== '' ? $barcode : null,
                'image' => $cosmosProduct['thumbnail'] ?? null,
            ]);
        } else {
            $productUpdates = [];

            if ($barcode !== '' && ! $product->barcode) {
                $productUpdates['barcode'] = $barcode;
            }

            if (! $product->image && ($cosmosProduct['thumbnail'] ?? null)) {
                $productUpdates['image'] = (string) $cosmosProduct['thumbnail'];
            }

            if ($productUpdates !== []) {
                $product->update($productUpdates);
            }
        }

        $forcedMarketId = filled($validated['market_id'] ?? null) ? (int) $validated['market_id'] : null;
        $cheapestMarketId = $this->resolveCheapestMarketId((int) $product->id);
        $targetMarketId = $forcedMarketId ?? $cheapestMarketId;
        $canPersistMarket = Schema::hasColumn('shopping_list_items', 'market_id');
        DB::transaction(function () use ($shoppingList, $product, $validated, $canPersistMarket, $targetMarketId): void {
            ShoppingList::query()
                ->whereKey($shoppingList->id)
                ->lockForUpdate()
                ->first();

            $itemsQuery = $shoppingList->items()->where('product_id', $product->id);

            if ($canPersistMarket && $targetMarketId !== null) {
                $existing = (clone $itemsQuery)
                    ->where('market_id', $targetMarketId)
                    ->lockForUpdate()
                    ->first();

                if (! $existing) {
                    $existing = (clone $itemsQuery)
                        ->whereNull('market_id')
                        ->lockForUpdate()
                        ->first();
                }
            } else {
                $existing = $itemsQuery
                    ->lockForUpdate()
                    ->first();
            }

            if ($existing) {
                $payload = [
                    'quantity' => (float) $existing->quantity + (float) $validated['quantity'],
                ];

                if (
                    $canPersistMarket
                    && $targetMarketId !== null
                    && ! $existing->market_id
                ) {
                    $payload['market_id'] = $targetMarketId;
                }

                $existing->update($payload);

                return;
            }

            $payload = [
                'product_id' => (int) $product->id,
                'quantity' => (float) $validated['quantity'],
            ];

            if ($canPersistMarket && $targetMarketId !== null) {
                $payload['market_id'] = $targetMarketId;
            }

            $shoppingList->items()->create($payload);
        });

        return redirect()
            ->route('shared-shopping-lists.show', ['token' => $token])
            ->with('shared_list_success', 'Produto adicionado com sucesso.');
    }

    public function searchProducts(string $token, Request $request): JsonResponse
    {
        $shoppingList = ShoppingList::query()
            ->where('share_token', $token)
            ->firstOrFail();

        $q = trim((string) $request->query('q', ''));
        $marketId = (int) $request->query('market_id', 0);

        if (mb_strlen($q) < 2 || $marketId <= 0) {
            return response()->json(['data' => []]);
        }

        $products = Product::query()
            ->join('market_products as mp', 'products.id', '=', 'mp.product_id')
            ->where('mp.market_id', $marketId)
            ->where(function ($query) use ($q): void {
                $query
                    ->where('products.name', 'like', '%' . $q . '%')
                    ->orWhere('products.original_name', 'like', '%' . $q . '%')
                    ->orWhere('products.barcode', 'like', '%' . $q . '%');
            })
            ->selectSub(function ($query) use ($shoppingList): void {
                $query->from('shopping_list_items as sli')
                    ->selectRaw('count(*)')
                    ->whereColumn('sli.product_id', 'products.id')
                    ->where('sli.shopping_list_id', (int) $shoppingList->id);
            }, 'in_list_count')
            ->selectSub(function ($query): void {
                $query->from('invoice_items as ii')
                    ->join('market_products as mp2', 'ii.market_product_id', '=', 'mp2.id')
                    ->selectRaw('count(*)')
                    ->whereColumn('mp2.product_id', 'products.id');
            }, 'usage_count')
            ->orderByDesc('in_list_count')
            ->orderByDesc('usage_count')
            ->orderBy('products.name')
            ->limit(12)
            ->get(['products.id', 'products.name', 'products.barcode', 'products.image'])
            ->unique('id')
            ->values();
        $lastPricesByProduct = $this->resolveLastPricesForProducts(
            $products
                ->pluck('id')
                ->map(fn ($value): int => (int) $value)
                ->all()
        );

        return response()->json([
            'data' => $products->map(fn (Product $product): array => [
                'id' => (int) $product->id,
                'name' => (string) $product->name,
                'barcode' => $product->barcode ? (string) $product->barcode : null,
                'image' => $product->image ? (string) $product->image : null,
                'last_price' => $lastPricesByProduct[(int) $product->id] ?? null,
                'in_list' => ((int) ($product->in_list_count ?? 0)) > 0,
                'usage_count' => (int) ($product->usage_count ?? 0),
            ])->values()->all(),
        ]);
    }

    public function lookupBarcode(string $token, string $barcode): JsonResponse
    {
        ShoppingList::query()
            ->where('share_token', $token)
            ->firstOrFail();

        $cleanBarcode = trim($barcode);

        $product = Product::query()
            ->where('barcode', $cleanBarcode)
            ->first();

        $cosmosProduct = null;
        if (! $product && $cleanBarcode !== '') {
            $cosmosProduct = $this->fetchCosmosProduct($cleanBarcode);
        }

        $resolvedName = $product?->name
            ?? ($cosmosProduct['description'] ?? null)
            ?? ('Produto ' . $cleanBarcode);

        return response()->json([
            'barcode' => $cleanBarcode,
            'found' => $product !== null || $cosmosProduct !== null,
            'product_name' => $resolvedName,
            'suggested_name' => $resolvedName,
            'thumbnail' => $product?->image ?: ($cosmosProduct['thumbnail'] ?? null),
            'brand_name' => $cosmosProduct['brand_name'] ?? null,
            'avg_price' => $cosmosProduct['avg_price'] ?? null,
            'source' => $product ? 'local' : ($cosmosProduct ? 'cosmos' : 'none'),
        ]);
    }

    public function removeItem(string $token, ShoppingListItem $item): RedirectResponse
    {
        $shoppingList = ShoppingList::query()
            ->where('share_token', $token)
            ->firstOrFail();

        if ((int) $item->shopping_list_id !== (int) $shoppingList->id) {
            abort(404);
        }

        $item->delete();

        return redirect()
            ->route('shared-shopping-lists.show', ['token' => $token])
            ->with('shared_list_success', 'Produto removido da lista.');
    }

    private function resolveSelectedOffersForProducts(array $productIds, array $marketIds): array
    {
        if ($productIds === [] || $marketIds === []) {
            return [];
        }

        $rows = InvoiceItem::query()
            ->join('market_products as mp', 'invoice_items.market_product_id', '=', 'mp.id')
            ->join('markets as m', 'mp.market_id', '=', 'm.id')
            ->leftJoin('addresses as a', 'a.market_id', '=', 'm.id')
            ->whereIn('mp.product_id', $productIds)
            ->whereIn('mp.market_id', $marketIds)
            ->orderBy('mp.product_id')
            ->orderBy('mp.market_id')
            ->orderByDesc('invoice_items.created_at')
            ->get([
                'mp.product_id',
                'm.id as market_id',
                'm.name as market_name',
                'a.street',
                'a.number',
                'a.neighborhood',
                'a.city',
                'a.state',
                'invoice_items.unit_price',
            ]);

        $offers = [];
        foreach ($rows as $row) {
            $key = (int) $row->product_id . ':' . (int) $row->market_id;
            if (isset($offers[$key])) {
                continue;
            }

            $offers[$key] = [
                'market_id' => (int) $row->market_id,
                'market_name' => (string) $row->market_name,
                'market_address' => $this->formatAddress(
                    $row->street,
                    $row->number,
                    $row->neighborhood,
                    $row->city,
                    $row->state,
                ),
                'unit_price' => $row->unit_price !== null ? (float) $row->unit_price : null,
            ];
        }

        return $offers;
    }

    private function resolveCheapestOffersForProducts(array $productIds): array
    {
        if ($productIds === []) {
            return [];
        }

        $rows = InvoiceItem::query()
            ->join('market_products as mp', 'invoice_items.market_product_id', '=', 'mp.id')
            ->join('markets as m', 'mp.market_id', '=', 'm.id')
            ->leftJoin('addresses as a', 'a.market_id', '=', 'm.id')
            ->whereIn('mp.product_id', $productIds)
            ->orderBy('mp.product_id')
            ->orderBy('invoice_items.unit_price')
            ->get([
                'mp.product_id',
                'm.id as market_id',
                'm.name as market_name',
                'a.street',
                'a.number',
                'a.neighborhood',
                'a.city',
                'a.state',
                'invoice_items.unit_price',
            ]);

        $offers = [];
        foreach ($rows as $row) {
            $productId = (int) $row->product_id;
            if (isset($offers[$productId])) {
                continue;
            }

            $offers[$productId] = [
                'market_id' => (int) $row->market_id,
                'market_name' => (string) $row->market_name,
                'market_address' => $this->formatAddress(
                    $row->street,
                    $row->number,
                    $row->neighborhood,
                    $row->city,
                    $row->state,
                ),
                'unit_price' => $row->unit_price !== null ? (float) $row->unit_price : null,
            ];
        }

        return $offers;
    }

    private function resolveCheapestMarketId(int $productId): ?int
    {
        $marketId = InvoiceItem::query()
            ->join('market_products as mp', 'invoice_items.market_product_id', '=', 'mp.id')
            ->where('mp.product_id', $productId)
            ->orderBy('invoice_items.unit_price')
            ->value('mp.market_id');

        return $marketId !== null ? (int) $marketId : null;
    }

    private function resolveLastPricesForProducts(array $productIds): array
    {
        if ($productIds === []) {
            return [];
        }

        $rows = InvoiceItem::query()
            ->join('market_products as mp', 'invoice_items.market_product_id', '=', 'mp.id')
            ->join('invoices as inv', 'invoice_items.invoice_id', '=', 'inv.id')
            ->whereIn('mp.product_id', $productIds)
            ->orderBy('mp.product_id')
            ->orderByDesc('inv.issued_at')
            ->orderByDesc('invoice_items.created_at')
            ->get([
                'mp.product_id',
                'invoice_items.unit_price',
            ]);

        $prices = [];
        foreach ($rows as $row) {
            $productId = (int) $row->product_id;
            if (isset($prices[$productId])) {
                continue;
            }

            $prices[$productId] = $row->unit_price !== null ? (float) $row->unit_price : null;
        }

        return $prices;
    }

    private function fetchCosmosProduct(string $barcode): ?array
    {
        $cleanBarcode = trim($barcode);
        $token = (string) config('services.cosmos.token', '');

        if ($cleanBarcode === '' || $token === '') {
            return null;
        }

        $baseUrl = (string) config('services.cosmos.base_url', 'https://api.cosmos.bluesoft.com.br');
        $timeout = (int) config('services.cosmos.timeout', 10);
        $userAgent = (string) config('services.cosmos.user_agent', 'Cosmos-API-Request');

        try {
            $response = Http::baseUrl($baseUrl)
                ->timeout($timeout > 0 ? $timeout : 10)
                ->acceptJson()
                ->withHeaders([
                    'X-Cosmos-Token' => $token,
                    'User-Agent' => $userAgent,
                ])
                ->get('/gtins/' . rawurlencode($cleanBarcode) . '.json');

            if (! $response->successful()) {
                return null;
            }

            $payload = $response->json();


            $description = isset($payload['description']) ? trim((string) $payload['description']) : '';

            return [
                'description' => $description !== '' ? $description : null,
                'thumbnail' => isset($payload['thumbnail']) && $payload['thumbnail'] !== ''
                    ? (string) $payload['thumbnail']
                    : null,
                'brand_name' => isset($payload['brand']['name']) && $payload['brand']['name'] !== ''
                    ? (string) $payload['brand']['name']
                    : null,
                'avg_price' => isset($payload['avg_price']) ? (float) $payload['avg_price'] : null,
            ];
        } catch (\Throwable) {
            return null;
        }
    }

    private function formatAddress(?string $street, ?string $number, ?string $neighborhood, ?string $city, ?string $state): string
    {
        $address = collect([
            trim(implode(', ', array_filter([$street, $number]))),
            $neighborhood,
            trim(implode(' - ', array_filter([$city, $state]))),
        ])
            ->filter(fn (?string $part): bool => filled($part))
            ->implode(', ');

        return $address !== '' ? $address : '-';
    }
}
