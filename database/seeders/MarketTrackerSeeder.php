<?php

namespace Database\Seeders;

use App\Models\Address;
use App\Models\Category;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Market;
use App\Models\MarketProduct;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class MarketTrackerSeeder extends Seeder
{
    public function run(): void
    {
        InvoiceItem::query()->delete();
        Invoice::query()->delete();
        MarketProduct::query()->delete();
        Address::query()->delete();
        Product::query()->delete();
        Market::query()->delete();

        $categoriesByName = Category::query()->pluck('id', 'name');

        $products = collect([
            ['name' => 'Arroz Tipo 1 5kg', 'category' => 'Mercearia', 'base_price' => 29.90, 'unit' => 'UN'],
            ['name' => 'Feijao Carioca 1kg', 'category' => 'Mercearia', 'base_price' => 9.80, 'unit' => 'UN'],
            ['name' => 'Cafe Torrado 500g', 'category' => 'Mercearia', 'base_price' => 17.90, 'unit' => 'UN'],
            ['name' => 'Leite Integral 1L', 'category' => 'Laticinios', 'base_price' => 5.60, 'unit' => 'UN'],
            ['name' => 'Oleo de Soja 900ml', 'category' => 'Mercearia', 'base_price' => 8.20, 'unit' => 'UN'],
            ['name' => 'Acucar Cristal 5kg', 'category' => 'Mercearia', 'base_price' => 21.50, 'unit' => 'UN'],
            ['name' => 'Tomate', 'category' => 'Hortifruti', 'base_price' => 8.50, 'unit' => 'KG'],
            ['name' => 'Banana Prata', 'category' => 'Hortifruti', 'base_price' => 6.70, 'unit' => 'KG'],
            ['name' => 'Batata Inglesa', 'category' => 'Hortifruti', 'base_price' => 7.90, 'unit' => 'KG'],
            ['name' => 'Papel Higienico 12 rolos', 'category' => 'Limpeza', 'base_price' => 18.90, 'unit' => 'UN'],
        ])->map(function (array $data) {
            $product = Product::query()->create([
                'name' => $data['name'],
                'category_id' => $categoriesByName[$data['category']] ?? null,
                'image' => null,
            ]);

            return [
                'id' => $product->id,
                'name' => $product->name,
                'category_id' => $product->category_id,
                'image' => $product->image,
                'base_price' => $data['base_price'],
                'unit' => $data['unit'],
            ];
        });

        $markets = [
            [
                'name' => 'Supermercado Centro BH',
                'cnpj' => '04641376000101',
                'logo' => null,
                'address' => [
                    'street' => 'Av. Afonso Pena',
                    'number' => '1530',
                    'neighborhood' => 'Centro',
                    'city' => 'Belo Horizonte',
                    'state' => 'MG',
                    'zip_code' => '30130-003',
                    'latitude' => -19.92265531,
                    'longitude' => -43.93777557,
                ],
            ],
            [
                'name' => 'Mercado Savassi',
                'cnpj' => '04641376000102',
                'logo' => null,
                'address' => [
                    'street' => 'Rua Pernambuco',
                    'number' => '1200',
                    'neighborhood' => 'Savassi',
                    'city' => 'Belo Horizonte',
                    'state' => 'MG',
                    'zip_code' => '30130-151',
                    'latitude' => -19.93792816,
                    'longitude' => -43.93013061,
                ],
            ],
            [
                'name' => 'Atacarejo Pampulha',
                'cnpj' => '04641376000103',
                'logo' => null,
                'address' => [
                    'street' => 'Av. Antonio Carlos',
                    'number' => '7590',
                    'neighborhood' => 'Pampulha',
                    'city' => 'Belo Horizonte',
                    'state' => 'MG',
                    'zip_code' => '31270-901',
                    'latitude' => -19.86128868,
                    'longitude' => -43.96668224,
                ],
            ],
        ];

        foreach ($markets as $marketIndex => $marketData) {
            $market = Market::query()->create([
                'name' => $marketData['name'],
                'cnpj' => $marketData['cnpj'],
                'logo' => $marketData['logo'],
            ]);

            $market->addresses()->create($marketData['address']);

            $marketProducts = $products->map(function (array $productData, int $productIndex) use ($market, $marketIndex) {
                $marketProduct = MarketProduct::query()->create([
                    'market_id' => $market->id,
                    'product_id' => $productData['id'],
                    'external_code' => sprintf('%d%04d', $marketIndex + 1, $productIndex + 1),
                    'unit' => $productData['unit'],
                ]);

                return [
                    'id' => $marketProduct->id,
                    'market_id' => $marketProduct->market_id,
                    'product_id' => $marketProduct->product_id,
                    'external_code' => $marketProduct->external_code,
                    'unit' => $marketProduct->unit,
                    'base_price' => $productData['base_price'] * (1 + ($marketIndex * 0.03)),
                ];
            });

            for ($invoiceNumber = 0; $invoiceNumber < 8; $invoiceNumber++) {
                $issuedAt = Carbon::now()->subWeeks(8 - $invoiceNumber)->setTime(10 + $marketIndex, 15, 0);

                $invoice = Invoice::query()->create([
                    'market_id' => $market->id,
                    'access_key' => Str::uuid()->toString(),
                    'issued_at' => $issuedAt,
                    'total_amount' => 0,
                ]);

                $itemsTotal = 0;
                $selectedMarketProducts = $marketProducts->shuffle()->take(6)->values();

                foreach ($selectedMarketProducts as $itemIndex => $marketProductData) {
                    $trend = (($invoiceNumber - 4) * 0.01);
                    $noise = (($marketIndex + $itemIndex) % 3 - 1) * 0.015;
                    $unitPrice = round($marketProductData['base_price'] * (1 + $trend + $noise), 2);
                    $quantity = $marketProductData['unit'] === 'KG'
                        ? round(0.6 + (($itemIndex + $invoiceNumber) % 5) * 0.25, 3)
                        : 1.000;
                    $totalPrice = round($unitPrice * $quantity, 2);
                    $itemsTotal += $totalPrice;

                    InvoiceItem::query()->create([
                        'invoice_id' => $invoice->id,
                        'market_product_id' => $marketProductData['id'],
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                        'total_price' => $totalPrice,
                    ]);
                }

                $invoice->update([
                    'total_amount' => round($itemsTotal, 2),
                ]);
            }
        }
    }
}
