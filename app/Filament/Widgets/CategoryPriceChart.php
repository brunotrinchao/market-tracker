<?php

namespace App\Filament\Widgets;

use App\Models\InvoiceItem;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class CategoryPriceChart extends ChartWidget
{
    protected ?string $heading = 'Preço médio por categoria';

    protected int|string|array $columnSpan = 6;

    protected function getData(): array
    {
        $rows = InvoiceItem::query()
            ->join('market_products as mp', 'invoice_items.market_product_id', '=', 'mp.id')
            ->join('products', 'mp.product_id', '=', 'products.id')
            ->whereNotNull('products.category')
            ->whereDate('invoice_items.created_at', '>=', now()->subDays(90)->toDateString())
            ->groupBy('products.category')
            ->orderByDesc(DB::raw('AVG(invoice_items.unit_price)'))
            ->limit(8)
            ->get([
                'products.category',
                DB::raw('AVG(invoice_items.unit_price) as avg_price'),
            ]);

        return [
            'datasets' => [
                [
                    'label' => 'Preço médio',
                    'data' => $rows->map(fn ($row): float => round((float) $row->avg_price, 2))->all(),
                    'backgroundColor' => '#f59e0b',
                    'borderColor' => '#d97706',
                ],
            ],
            'labels' => $rows->pluck('category')->all(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}

