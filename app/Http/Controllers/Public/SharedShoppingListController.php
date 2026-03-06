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
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Contracts\View\View;
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
        $selectedMarketIds = $shoppingList->items
            ->pluck('market_id')
            ->filter(fn ($value): bool => $value !== null)
            ->map(fn ($value): int => (int) $value)
            ->unique()
            ->values();

        $selectedMarkets = Market::query()
            ->with('addresses')
            ->whereIn('id', $selectedMarketIds)
            ->get()
            ->keyBy('id');

        foreach ($shoppingList->items as $item) {
            if (! $item->product) {
                continue;
            }

            $selectedMarketId = $item->market_id ? (int) $item->market_id : null;
            $offer = $this->resolveOfferForProduct((int) $item->product_id, $selectedMarketId);
            $fallbackLastPrice = $this->resolveLastPriceForProduct((int) $item->product_id);

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

        if (filled($validated['product_id'] ?? null)) {
            $product = Product::query()->find((int) $validated['product_id']);
        }

        if (! $product && $barcode !== '') {
            $product = Product::query()->where('barcode', $barcode)->first();
        }

        if (! $product) {
            $product = Product::query()
                ->whereRaw('LOWER(name) = ?', [mb_strtolower($productName)])
                ->first();
        }

        if (! $product) {
            $product = Product::query()->create([
                'name' => $productName,
                'original_name' => $productName,
                'barcode' => $barcode !== '' ? $barcode : null,
            ]);
        } elseif ($barcode !== '' && ! $product->barcode) {
            $product->update(['barcode' => $barcode]);
        }

        $forcedMarketId = filled($validated['market_id'] ?? null) ? (int) $validated['market_id'] : null;
        $cheapestMarketId = $this->resolveCheapestMarketId((int) $product->id);
        $targetMarketId = $forcedMarketId ?? $cheapestMarketId;
        $canPersistMarket = Schema::hasColumn('shopping_list_items', 'market_id');
        $itemsQuery = $shoppingList->items()->where('product_id', $product->id);

        if ($canPersistMarket && $targetMarketId !== null) {
            $existing = (clone $itemsQuery)
                ->where('market_id', $targetMarketId)
                ->first();

            if (! $existing) {
                $existing = (clone $itemsQuery)
                    ->whereNull('market_id')
                    ->first();
            }
        } else {
            $existing = $itemsQuery->first();
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
        } else {
            $payload = [
                'product_id' => (int) $product->id,
                'quantity' => (float) $validated['quantity'],
            ];

            if ($canPersistMarket && $targetMarketId !== null) {
                $payload['market_id'] = $targetMarketId;
            }

            $shoppingList->items()->create($payload);
        }

        return redirect()
            ->route('shared-shopping-lists.show', ['token' => $token])
            ->with('shared_list_success', 'Produto adicionado com sucesso.');
    }

    public function searchProducts(string $token, Request $request): JsonResponse
    {
        ShoppingList::query()
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
            ->orderBy('products.name')
            ->limit(12)
            ->get(['products.id', 'products.name', 'products.barcode', 'products.image'])
            ->unique('id')
            ->values();

        return response()->json([
            'data' => $products->map(fn (Product $product): array => [
                'id' => (int) $product->id,
                'name' => (string) $product->name,
                'barcode' => $product->barcode ? (string) $product->barcode : null,
                'image' => $product->image ? (string) $product->image : null,
                'last_price' => $this->resolveLastPriceForProduct((int) $product->id),
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

        return response()->json([
            'barcode' => $cleanBarcode,
            'found' => $product !== null,
            'product_name' => $product?->name ?? null,
            'suggested_name' => $product?->name ?? ('Produto ' . $cleanBarcode),
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

    public function appleCalendarIcs(string $token): Response
    {
        $shoppingList = ShoppingList::query()
            ->where('share_token', $token)
            ->firstOrFail();

        $date = $shoppingList->shopping_date
            ? Carbon::parse($shoppingList->shopping_date)
            : Carbon::parse($shoppingList->created_at ?? now());

        $start = $date->copy()->startOfDay();
        $end = $start->copy()->addHour();

        $publicUrl = route('shared-shopping-lists.show', ['token' => $shoppingList->share_token]);
        $summary = $this->escapeIcsText('Lista de compras - ' . $shoppingList->name);
        $description = $this->escapeIcsText('Checklist: ' . $publicUrl);
        $uid = 'shopping-list-' . $shoppingList->id . '@market-tracker';
        $dtStamp = now()->utc()->format('Ymd\THis\Z');

        $ics = implode("\r\n", [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//Market Tracker//Shopping List//PT-BR',
            'CALSCALE:GREGORIAN',
            'BEGIN:VEVENT',
            'UID:' . $uid,
            'DTSTAMP:' . $dtStamp,
            'DTSTART:' . $start->format('Ymd\THis'),
            'DTEND:' . $end->format('Ymd\THis'),
            'SUMMARY:' . $summary,
            'DESCRIPTION:' . $description,
            'END:VEVENT',
            'END:VCALENDAR',
            '',
        ]);

        return response($ics, 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="lista-de-compras.ics"',
        ]);
    }

    private function resolveOfferForProduct(int $productId, ?int $selectedMarketId = null): ?array
    {
        $query = InvoiceItem::query()
            ->join('market_products as mp', 'invoice_items.market_product_id', '=', 'mp.id')
            ->join('markets as m', 'mp.market_id', '=', 'm.id')
            ->leftJoin('addresses as a', 'a.market_id', '=', 'm.id')
            ->select([
                'm.id as market_id',
                'm.name as market_name',
                'a.street',
                'a.number',
                'a.neighborhood',
                'a.city',
                'a.state',
                'invoice_items.unit_price',
                'invoice_items.created_at',
            ])
            ->where('mp.product_id', $productId);

        if ($selectedMarketId) {
            $query->where('mp.market_id', $selectedMarketId)
                ->orderByDesc('invoice_items.created_at');
        } else {
            $query->orderBy('invoice_items.unit_price');
        }

        $offer = $query->first();

        if (! $offer) {
            return null;
        }

        $address = collect([
            trim(implode(', ', array_filter([$offer->street, $offer->number]))),
            $offer->neighborhood,
            trim(implode(' - ', array_filter([$offer->city, $offer->state]))),
        ])
            ->filter(fn (?string $part): bool => filled($part))
            ->implode(', ');

        return [
            'market_id' => (int) $offer->market_id,
            'market_name' => (string) $offer->market_name,
            'market_address' => $address !== '' ? $address : '-',
            'unit_price' => $offer->unit_price !== null ? (float) $offer->unit_price : null,
        ];
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

    private function resolveLastPriceForProduct(int $productId): ?float
    {
        $lastPrice = InvoiceItem::query()
            ->join('market_products as mp', 'invoice_items.market_product_id', '=', 'mp.id')
            ->join('invoices as inv', 'invoice_items.invoice_id', '=', 'inv.id')
            ->where('mp.product_id', $productId)
            ->orderByDesc('inv.issued_at')
            ->orderByDesc('invoice_items.created_at')
            ->value('invoice_items.unit_price');

        return $lastPrice !== null ? (float) $lastPrice : null;
    }

    private function escapeIcsText(string $value): string
    {
        return str_replace(
            ["\\", ';', ',', "\r\n", "\n", "\r"],
            ['\\\\', '\;', '\,', '\n', '\n', '\n'],
            $value
        );
    }
}
