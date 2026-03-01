<?php

namespace App\Filament\Widgets;

use App\Models\InvoiceItem;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class PriceTrendChart extends ChartWidget
{
    protected ?string $heading = 'Evolução da cesta fixa de produtos (30 dias)';

    protected int|string|array $columnSpan = 8;

    protected function getData(): array
    {
        $endDate = now()->startOfDay();
        $startDate = $endDate->copy()->subDays(29);

        // Usa os produtos mais recorrentes no histórico recente para formar uma cesta estável.
        $basketProductIds = InvoiceItem::query()
            ->join('market_products as mp', 'invoice_items.market_product_id', '=', 'mp.id')
            ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
            ->whereDate('invoices.issued_at', '>=', now()->subDays(90)->toDateString())
            ->groupBy('mp.product_id')
            ->orderByRaw('COUNT(*) DESC')
            ->limit(15)
            ->pluck('mp.product_id')
            ->all();

        if (empty($basketProductIds)) {
            return ['datasets' => [], 'labels' => []];
        }

        $dailyPrices = InvoiceItem::query()
            ->join('market_products as mp', 'invoice_items.market_product_id', '=', 'mp.id')
            ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
            ->whereIn('mp.product_id', $basketProductIds)
            ->whereBetween('invoices.issued_at', [$startDate, $endDate->copy()->endOfDay()])
            ->get([
                'mp.product_id as product_id',
                'invoice_items.unit_price as unit_price',
                'invoices.issued_at as issued_at',
            ])
            ->groupBy(fn ($row) => $row->product_id . '|' . Carbon::parse($row->issued_at)->toDateString())
            ->map(fn ($group): float => round((float) $group->avg('unit_price'), 2));

        // Inicializa com último preço conhecido antes da janela para suavizar os primeiros dias.
        $lastKnownPriceByProduct = InvoiceItem::query()
            ->join('market_products as mp', 'invoice_items.market_product_id', '=', 'mp.id')
            ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
            ->whereIn('mp.product_id', $basketProductIds)
            ->where('invoices.issued_at', '<', $startDate)
            ->orderByDesc('invoices.issued_at')
            ->get([
                'mp.product_id as product_id',
                'invoice_items.unit_price as unit_price',
            ])
            ->unique('product_id')
            ->mapWithKeys(fn ($row): array => [(int) $row->product_id => (float) $row->unit_price])
            ->all();

        $labels = [];
        $series = [];

        $cursor = $startDate->copy();
        while ($cursor->lte($endDate)) {
            $dayKey = $cursor->toDateString();
            $labels[] = $cursor->format('d/m/Y H:i:s');

            $dayValues = [];

            foreach ($basketProductIds as $productId) {
                $groupKey = $productId . '|' . $dayKey;

                if ($dailyPrices->has($groupKey)) {
                    $lastKnownPriceByProduct[$productId] = (float) $dailyPrices->get($groupKey);
                }

                if (isset($lastKnownPriceByProduct[$productId])) {
                    $dayValues[] = $lastKnownPriceByProduct[$productId];
                }
            }

            $series[] = empty($dayValues) ? null : round(array_sum($dayValues) / count($dayValues), 2);
            $cursor->addDay();
        }

        return [
            'datasets' => [
                [
                    'label' => 'Preço médio da cesta',
                    'data' => $series,
                    'borderColor' => '#2563eb',
                    'backgroundColor' => 'rgba(37, 99, 235, 0.12)',
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
