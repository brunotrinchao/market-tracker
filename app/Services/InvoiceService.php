<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Market;
use App\Models\MarketProduct;
use App\Models\Product;
use App\Services\Products\ProductCategoryClassifier;
use App\Services\Products\ProductNameNormalizer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class InvoiceService
{
    public function __construct(
        private ProductNameNormalizer $productNameNormalizer,
        private ProductCategoryClassifier $productCategoryClassifier,
    ) {
    }

    /**
     * Busca dados da empresa via CNPJ e retorna o Nome Fantasia.
     */
    public function getMarketDataByCnpj(?string $cnpj): array
    {
        if (! $cnpj) {
            return [
                'name' => 'Mercado Desconhecido',
                'cnpj' => null,
            ];
        }

        // Limpa o CNPJ para a URL
        $cleanCnpj = preg_replace('/\D/', '', $cnpj);

        if (! $cleanCnpj) {
            return [
                'name' => 'Mercado Desconhecido',
                'cnpj' => null,
            ];
        }
        
        try {
            $response = Http::get("https://kitana.opencnpj.com/cnpj/{$cleanCnpj}");

            if ($response->successful() && $response->json('success')) {
                $data = $response->json('data');
                
                return [
                    'name' => $data['nomeFantasia'] ?? $data['razaoSocial'], // Prioriza Nome Fantasia
                    'cnpj' => $data['cnpj'],
                    'address' => [
                        'logradouro' => $data['logradouro'],
                        'numero' => $data['numero'],
                        'bairro' => $data['bairro'],
                        'municipio' => $data['municipio'],
                        'uf' => $data['uf'],
                        'cep' => $data['cep'],
                    ]
                ];
            }
        } catch (\Exception $e) {
            Log::error("Erro ao consultar CNPJ {$cnpj}: " . $e->getMessage());
        }

        // Fallback caso a API falhe ou não encontre
        return [
            'name' => 'Mercado Desconhecido',
            'cnpj' => $cnpj,
        ];
    }
    
    /**
     * Obtém as coordenadas (lat, lng) baseadas no endereço.
     */
    public function getCoordinates(array $address): array
    {
        $addressString = implode(', ', array_filter([
            $address['logradouro'] ?? null,
            $address['numero'] ?? null,
            $address['bairro'] ?? null,
            $address['municipio'] ?? null,
            $address['uf'] ?? null,
        ]));

        if ($addressString === '') {
            return ['latitude' => null, 'longitude' => null];
        }
        
        // Exemplo usando Google Maps Geocoding API
        $response = Http::get('https://maps.googleapis.com/maps/api/geocode/json', [
            'address' => $addressString,
            'key' => config('services.google_maps.key'),
        ]);

        if ($response->successful() && isset($response->json('results')[0])) {
            $location = $response->json('results')[0]['geometry']['location'];
            return [
                'latitude' => $location['lat'],
                'longitude' => $location['lng'],
            ];
        }

        return ['latitude' => null, 'longitude' => null];
    }

    public function getCoordinatesFromNormalizedAddress(array $address): array
    {
        $mapped = [
            'logradouro' => $address['street'] ?? null,
            'numero' => $address['number'] ?? null,
            'bairro' => $address['neighborhood'] ?? null,
            'municipio' => $address['city'] ?? null,
            'uf' => $address['state'] ?? null,
        ];

        return $this->getCoordinates($mapped);
    }

    /**
     * Workflow completo de enriquecimento
     */
    public function enrichMarketData(string $cnpj): array
    {
        // 1. Busca Nome Fantasia e Endereço na Kitana
        $marketInfo = $this->getMarketDataByCnpj($cnpj);

        // 2. Busca Coordenadas se o endereço foi encontrado
        if (isset($marketInfo['address'])) {
            $coords = $this->getCoordinates($marketInfo['address']);
            $marketInfo['coordinates'] = $coords;
        }

        return $marketInfo;
    }

    /**
     * Processa os dados extraídos da nota fiscal.
     * Espera um array com: market_info, items, e invoice_details.
     */
    public function storeInvoiceData(array $rawData): Invoice
    {
        return DB::transaction(function () use ($rawData) {
            $rawData = $this->normalizeRawData($rawData);
            $rawCnpj = $rawData['market']['cnpj'] ?? null;

            // 1. Tenta localizar mercado existente.
            $market = null;
            if ($rawCnpj) {
                $market = Market::query()->where('cnpj', $rawCnpj)->first();
            }
            if (! $market) {
                $market = Market::query()->where('name', $rawData['market']['name'])->first();
            }

            // 2. Somente para mercado novo: enriquece, cria mercado e endereço com coordenadas.
            if (! $market) {
                $marketInfo = $this->getMarketDataByCnpj($rawCnpj);

                if (! empty($marketInfo['name']) && $marketInfo['name'] !== 'Mercado Desconhecido') {
                    $rawData['market']['name'] = $marketInfo['name'];
                }

                $rawData['market']['address_api'] = $marketInfo['address'] ?? null;
                $rawData['market']['address_details'] = $this->mergeAddressData(
                    $rawData['market']['address_details'] ?? [],
                    $rawData['market']['address_api'] ?? [],
                );
                $rawData['market']['zip_code'] = $this->normalizeZipCode(
                    $rawData['market']['zip_code'] ?? ($rawData['market']['address_api']['cep'] ?? null),
                );

                $market = Market::create([
                    'name' => $rawData['market']['name'],
                    'cnpj' => $rawData['market']['cnpj'],
                ]);

                $coords = $this->getCoordinatesFromNormalizedAddress($rawData['market']['address_details']);
                $market->addresses()->create(array_merge(
                    $rawData['market']['address_details'],
                    [
                        'zip_code' => $rawData['market']['zip_code'],
                        'latitude' => $coords['latitude'],
                        'longitude' => $coords['longitude'],
                    ]
                ));
            }

            // 3. Criar a Nota Fiscal (Invoice)
            $invoice = $market->invoices()->create([
                'access_key' => $rawData['invoice']['access_key'],
                'issued_at' => $rawData['invoice']['issued_at'], // Ex: 12/02/2026 [cite: 32]
                'total_amount' => $rawData['invoice']['total_amount'], // Ex: 193.40
                'ai_provider' => data_get($rawData, '_ai.provider'),
                'ai_model' => data_get($rawData, '_ai.model'),
                'ai_raw_response' => data_get($rawData, '_ai.raw_response'),
                'ai_payload' => data_get($rawData, '_ai.payload'),
            ]);

            // 4. Processar Itens
            foreach ($rawData['items'] as $item) {
                $this->processItem($invoice, $market, $item);
            }

            return $invoice;
        });
    }

    protected function processItem(Invoice $invoice, Market $market, array $itemData)
    {
        $names = $this->productNameNormalizer->normalizeWithOriginal(
            $itemData['original_name'] ?? $itemData['name'] ?? null
        );
        $normalizedName = $names['normalized'];
        $originalName = $names['original'];
        $unitCode = $this->normalizeUnitCode($itemData['unit'] ?? null);
        $suggestedCategoryId = $this->productCategoryClassifier->inferCategoryIdFromSuggestion(
            $itemData['category_suggestion'] ?? null
        );

        // A. Tenta localizar um vínculo existente para este mercado pelo código interno
        // Ex: Código 2634029 para "LIMP BH PERF 2L"
        $marketProduct = MarketProduct::where('market_id', $market->id)
            ->where('external_code', $itemData['code'])
            ->first();

        if (!$marketProduct) {
            // B. Se o vínculo não existe, procuramos o Produto Global pelo nome
            $product = Product::firstOrCreate(
                ['name' => $normalizedName],
                ['original_name' => $originalName !== '' ? $originalName : null]
            );

            if (! $product->original_name && $originalName !== '') {
                $product->update(['original_name' => $originalName]);
            }

            if (! $product->category_id) {
                $inferredCategoryId = $suggestedCategoryId
                    ?? $this->productCategoryClassifier->inferCategoryId($product->name);

                if ($inferredCategoryId) {
                    $product->update(['category_id' => $inferredCategoryId]);
                }
            }

            // C. Criamos o vínculo (De-Para) para compras futuras
            $marketProduct = MarketProduct::create([
                'market_id' => $market->id,
                'product_id' => $product->id,
                'external_code' => $itemData['code'],
                'unit' => $unitCode, // KG, UN, L
            ]);
        } elseif ($unitCode && $marketProduct->unit !== $unitCode) {
            // Mantém a unidade alinhada com a última nota fiscal importada.
            $marketProduct->update([
                'unit' => $unitCode,
            ]);
        }

        if ($suggestedCategoryId) {
            $productFromLink = $marketProduct->product()->first();

            if ($productFromLink && ! $productFromLink->category_id) {
                $productFromLink->update(['category_id' => $suggestedCategoryId]);
            }
        }

        // Registra o item na nota (O histórico de preço)
        $invoice->items()->create([
            'market_product_id' => $marketProduct->id,
            'original_name' => $originalName !== '' ? $originalName : null,
            'quantity' => $itemData['quantity'],
            'unit_price' => $itemData['unit_price'],
            'total_price' => $itemData['total_price'],
        ]);
    }

    private function normalizeUnitCode(?string $unit): string
    {
        $unit = strtoupper(trim((string) $unit));
        $unit = preg_replace('/\s+/', '', $unit) ?? $unit;

        return match ($unit) {
            'KG', 'KGS', 'KILO', 'KILOGRAMA', 'KILOGRAMAS', 'QUILO', 'QUILOGRAMA', 'QUILOGRAMAS' => 'KG',
            'L', 'LT', 'LTS', 'LITRO', 'LITROS' => 'L',
            'UN', 'UND', 'UNID', 'UNIDADE', 'UNIDADES', 'PCT', 'PC', 'PCA', 'PEC', 'PECA', 'PECAS' => 'UN',
            default => 'UN',
        };
    }

    private function normalizeRawData(array $rawData): array
    {
        $rawData['market'] ??= [];
        $rawData['invoice'] ??= [];
        $rawData['items'] = is_array($rawData['items'] ?? null) ? $rawData['items'] : [];

        $rawData['market']['name'] = trim((string) ($rawData['market']['name'] ?? 'Mercado Desconhecido'));
        $rawData['market']['cnpj'] = $this->normalizeDigits($rawData['market']['cnpj'] ?? null);
        $rawData['market']['zip_code'] = $this->normalizeZipCode($rawData['market']['zip_code'] ?? null);

        $issuedAt = $rawData['invoice']['issued_at'] ?? now();
        $rawData['invoice']['issued_at'] = $issuedAt;
        $rawData['invoice']['total_amount'] = $this->normalizeNumber($rawData['invoice']['total_amount'] ?? null);
        $rawData['invoice']['access_key'] = $this->normalizeStringOrNull($rawData['invoice']['access_key'] ?? null);

        return $rawData;
    }

    private function mergeAddressData(array $addressDetails, array $apiAddress): array
    {
        return [
            'street' => $this->normalizeStringOrNull($addressDetails['street'] ?? null)
                ?? $this->normalizeStringOrNull($apiAddress['logradouro'] ?? null)
                ?? 'Nao informado',
            'number' => $this->normalizeStringOrNull($addressDetails['number'] ?? null)
                ?? $this->normalizeStringOrNull($apiAddress['numero'] ?? null)
                ?? 'S/N',
            'neighborhood' => $this->normalizeStringOrNull($addressDetails['neighborhood'] ?? null)
                ?? $this->normalizeStringOrNull($apiAddress['bairro'] ?? null)
                ?? 'Nao informado',
            'city' => $this->normalizeStringOrNull($addressDetails['city'] ?? null)
                ?? $this->normalizeStringOrNull($apiAddress['municipio'] ?? null)
                ?? 'Nao informado',
            'state' => $this->normalizeState(
                $addressDetails['state'] ?? ($apiAddress['uf'] ?? null)
            ) ?? 'NA',
        ];
    }

    private function normalizeState(?string $state): ?string
    {
        $state = strtoupper(trim((string) $state));

        if ($state === '') {
            return null;
        }

        return substr($state, 0, 2);
    }

    private function normalizeZipCode(?string $zipCode): string
    {
        $zipCode = $this->normalizeDigits($zipCode);

        return $zipCode ?: '00000000';
    }

    private function normalizeDigits(?string $value): ?string
    {
        $value = preg_replace('/\D/', '', (string) $value) ?? '';

        return $value !== '' ? $value : null;
    }

    private function normalizeStringOrNull(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }

    private function normalizeNumber(mixed $value): float
    {
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        $value = trim((string) $value);

        if ($value === '') {
            return 0.0;
        }

        $value = preg_replace('/[^\d,.\-]/', '', $value) ?? $value;

        if (str_contains($value, ',')) {
            $value = str_replace('.', '', $value);
            $value = str_replace(',', '.', $value);
        }

        return is_numeric($value) ? (float) $value : 0.0;
    }
}
