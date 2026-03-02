<?php

namespace App\Filament\Widgets;

use App\Models\Invoice;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class InvoiceImportsTrendChart extends ChartWidget
{
    protected ?string $heading = 'Notas importadas por dia (30 dias)';

    protected int|string|array $columnSpan = 4;

    protected ?string $maxHeight = '220px';

    protected function getData(): array
    {
        $endDate = now()->startOfDay();
        $startDate = $endDate->copy()->subDays(29);

        $rows = Invoice::query()
            ->whereBetween('issued_at', [$startDate, $endDate->copy()->endOfDay()])
            ->selectRaw('DATE(issued_at) as day, COUNT(*) as total')
            ->groupBy('day')
            ->orderBy('day')
            ->pluck('total', 'day');

        $labels = [];
        $data = [];

        $cursor = $startDate->copy();
        while ($cursor->lte($endDate)) {
            $key = $cursor->toDateString();
            $labels[] = Carbon::parse($key)->format('d/m');
            $data[] = (int) ($rows[$key] ?? 0);
            $cursor->addDay();
        }

        return [
            'datasets' => [
                [
                    'label' => 'Notas importadas',
                    'data' => $data,
                    'borderColor' => '#2563eb',
                    'backgroundColor' => 'rgba(37, 99, 235, 0.15)',
                    'fill' => true,
                    'tension' => 0.3,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
