<?php

namespace App\Filament\Widgets;

use App\Models\InvoiceItem;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class ItemsByMarketChart extends ChartWidget
{
    protected ?string $heading = 'Itens registrados por supermercado (30 dias)';

    protected int|string|array $columnSpan = 4;

    protected ?string $maxHeight = '220px';

    protected function getData(): array
    {
        $rows = InvoiceItem::query()
            ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
            ->join('market_products as mp', 'invoice_items.market_product_id', '=', 'mp.id')
            ->join('markets', 'mp.market_id', '=', 'markets.id')
            ->whereDate('invoices.issued_at', '>=', now()->subDays(29)->toDateString())
            ->groupBy('markets.id', 'markets.name')
            ->orderByDesc(DB::raw('COUNT(invoice_items.id)'))
            ->limit(8)
            ->get([
                'markets.name as market_name',
                DB::raw('COUNT(invoice_items.id) as total_items'),
            ]);

        return [
            'datasets' => [
                [
                    'label' => 'Quantidade de itens',
                    'data' => $rows->map(fn ($row): int => (int) $row->total_items)->all(),
                    'backgroundColor' => '#0ea5e9',
                    'borderColor' => '#0284c7',
                ],
            ],
            'labels' => $rows->pluck('market_name')->all(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
