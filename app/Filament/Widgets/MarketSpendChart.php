<?php

namespace App\Filament\Widgets;

use App\Models\Invoice;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class MarketSpendChart extends ChartWidget
{
    protected ?string $heading = 'Gasto por supermercado (últimos 30 dias)';

    protected int|string|array $columnSpan = 4;

    protected function getData(): array
    {
        $rows = Invoice::query()
            ->join('markets', 'invoices.market_id', '=', 'markets.id')
            ->whereDate('invoices.issued_at', '>=', now()->subDays(29)->toDateString())
            ->groupBy('markets.id', 'markets.name')
            ->orderByDesc(DB::raw('SUM(invoices.total_amount)'))
            ->limit(6)
            ->get([
                'markets.name as market_name',
                DB::raw('SUM(invoices.total_amount) as total_amount'),
            ]);

        return [
            'datasets' => [
                [
                    'label' => 'Gasto',
                    'data' => $rows->map(fn ($row): float => round((float) $row->total_amount, 2))->all(),
                    'backgroundColor' => ['#0ea5e9', '#2563eb', '#14b8a6', '#22c55e', '#f59e0b', '#ef4444'],
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

