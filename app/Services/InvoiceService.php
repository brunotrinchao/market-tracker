<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Market;
use App\Models\MarketProduct;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class InvoiceService
{
    /**
     * Busca dados da empresa via CNPJ e retorna o Nome Fantasia.
     */
    public function getMarketDataByCnpj(string $cnpj): array
    {
        // Limpa o CNPJ para a URL
        $cleanCnpj = preg_replace('/\D/', '', $cnpj);
        
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
        $addressString = "{$address['logradouro']}, {$address['numero']}, {$address['bairro']}, {$address['municipio']}, {$address['uf']}";
        
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

            // Antes de salvar, busca os dados reais da empresa
            $marketInfo = $this->getMarketDataByCnpj($rawData['market']['cnpj']);
            
            // Atualiza o nome para o Nome Fantasia oficial (Ex: SUPERMERCADOS BH)
            $rawData['market']['name'] = $marketInfo['name'];
            $rawData['market']['address_api'] = $marketInfo['address'] ?? null;

            // 1. Encontrar ou criar o Mercado pelo CNPJ
            $market = Market::firstOrCreate(
                ['cnpj' => $rawData['market']['cnpj']],
                ['name' => $rawData['market']['name']]
            );

            // 2. Garantir que o endereço esteja vinculado
            $market->addresses()->updateOrCreate(
                ['zip_code' => $rawData['market']['zip_code']],
                $rawData['market']['address_details']
            );

            // 3. Criar a Nota Fiscal (Invoice)
            $invoice = $market->invoices()->create([
                'access_key' => $rawData['invoice']['access_key'],
                'issued_at' => $rawData['invoice']['issued_at'], // Ex: 12/02/2026 [cite: 32]
                'total_amount' => $rawData['invoice']['total_amount'], // Ex: 193.40 
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
        // A. Tenta localizar um vínculo existente para este mercado pelo código interno
        // Ex: Código 2634029 para "LIMP BH PERF 2L"
        $marketProduct = MarketProduct::where('market_id', $market->id)
            ->where('external_code', $itemData['code'])
            ->first();

        if (!$marketProduct) {
            // B. Se o vínculo não existe, procuramos o Produto Global pelo nome
            $product = Product::firstOrCreate(
                ['name' => $itemData['name']]
            );

            // C. Criamos o vínculo (De-Para) para compras futuras
            $marketProduct = MarketProduct::create([
                'market_id' => $market->id,
                'product_id' => $product->id,
                'external_code' => $itemData['code'],
                'unit' => $itemData['unit'], // FR, KG, UN, etc
            ]);
        }

        // Registra o item na nota (O histórico de preço)
        $invoice->items()->create([
            'market_product_id' => $marketProduct->id,
            'quantity' => $itemData['quantity'],
            'unit_price' => $itemData['unit_price'],
            'total_price' => $itemData['total_price'],
        ]);
    }
}
