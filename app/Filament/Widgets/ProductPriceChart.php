<?php

namespace App\Filament\Widgets;

use App\Models\InvoiceItem;
use App\Models\Product;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class ProductPriceChart extends ChartWidget
{
    protected ?string $heading = 'Evolução de Preço (Geral e por Supermercado)';

    // Propriedade para receber o produto da página
    public ?Product $record = null;

    protected int|string|array $columnSpan = 'full';

    protected function getData(): array
    {
        if (! $this->record) {
            return [];
        }

        $productId = $this->record->id;

        // Série geral (média diária entre todos os mercados)
        $generalRows = InvoiceItem::query()
            ->join('market_products', 'invoice_items.market_product_id', '=', 'market_products.id')
            ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
            ->where('market_products.product_id', $productId)
            ->selectRaw('DATE(invoices.issued_at) as issued_date, AVG(invoice_items.unit_price) as avg_price')
            ->groupBy('issued_date')
            ->orderBy('issued_date')
            ->get();

        // Séries por supermercado (média diária por mercado)
        $marketRows = InvoiceItem::query()
            ->join('market_products', 'invoice_items.market_product_id', '=', 'market_products.id')
            ->join('markets', 'market_products.market_id', '=', 'markets.id')
            ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
            ->where('market_products.product_id', $productId)
            ->selectRaw('DATE(invoices.issued_at) as issued_date, markets.name as market_name, AVG(invoice_items.unit_price) as avg_price')
            ->groupBy('issued_date', 'market_name')
            ->orderBy('issued_date')
            ->get();

        $allDates = $generalRows
            ->pluck('issued_date')
            ->merge($marketRows->pluck('issued_date'))
            ->unique()
            ->sort()
            ->values();

        if ($allDates->isEmpty()) {
            return [
                'datasets' => [],
                'labels' => [],
            ];
        }

        $labels = $allDates
            ->map(fn ($date) => Carbon::parse($date)->format('d/m/Y H:i:s'))
            ->values()
            ->all();

        $generalMap = $generalRows
            ->mapWithKeys(fn ($row) => [$row->issued_date => (float) $row->avg_price]);

        $datasets = [[
            'label' => 'Média geral',
            'data' => $allDates->map(fn ($date) => $generalMap[$date] ?? null)->all(),
            'borderColor' => '#111827',
            'backgroundColor' => 'rgba(17, 24, 39, 0.08)',
            'borderDash' => [6, 4],
            'pointRadius' => 2,
            'pointHoverRadius' => 4,
            'tension' => 0.25,
            'fill' => false,
            'spanGaps' => true,
        ]];

        $palette = ['#2563eb', '#f97316', '#16a34a', '#a855f7', '#e11d48', '#0891b2', '#ca8a04'];

        $marketRows
            ->groupBy('market_name')
            ->each(function ($rows, $marketName) use (&$datasets, $allDates, $palette): void {
                $index = count($datasets) - 1;
                $color = $palette[$index % count($palette)];

                $marketMap = $rows
                    ->mapWithKeys(fn ($row) => [$row->issued_date => (float) $row->avg_price]);

                $datasets[] = [
                    'label' => $marketName,
                    'data' => $allDates->map(fn ($date) => $marketMap[$date] ?? null)->all(),
                    'borderColor' => $color,
                    'backgroundColor' => $color,
                    'pointRadius' => 2.5,
                    'pointHoverRadius' => 4,
                    'tension' => 0.25,
                    'fill' => false,
                    'spanGaps' => true,
                ];
            });

        return [
            'datasets' => $datasets,
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
