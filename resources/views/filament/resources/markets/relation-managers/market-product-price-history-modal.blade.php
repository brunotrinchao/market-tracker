@php
    $prices = $history->pluck('unit_price')->map(fn ($value) => (float) $value)->values();
    $labels = $history->pluck('issued_at')->map(fn ($date) => \Illuminate\Support\Carbon::parse($date)->format('d/m/Y'))->values();

    $minPrice = $prices->min();
    $maxPrice = $prices->max();
    $latestPrice = $prices->last();
    $firstPrice = $prices->first();
    $variation = $firstPrice ? (($latestPrice - $firstPrice) / $firstPrice) * 100 : 0;
    $variationColor = $variation > 0 ? '#991b1b' : ($variation < 0 ? '#166534' : '#6b7280');
    $chartId = 'market-price-chart-' . uniqid();
@endphp

<div style="display: grid; gap: 16px;">
    <div style="font-size: 14px; color: #374151;">
        <strong>{{ $marketProduct->product->name ?? 'Produto' }}</strong>
        <span style="color: #6b7280;"> - {{ $marketProduct->unit }}</span>
    </div>

    @if ($prices->isEmpty())
        <p style="font-size: 14px; color: #6b7280;">Sem historico de precos para este produto neste mercado.</p>
    @else
        <div style="display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 12px; font-size: 12px;">
            <div style="border: 1px solid #e5e7eb; background: #f9fafb; border-radius: 10px; padding: 12px;">
                <div style="color: #6b7280;">Menor</div>
                <div style="font-size: 16px; font-weight: 600;">R$ {{ number_format($minPrice, 2, ',', '.') }}</div>
            </div>
            <div style="border: 1px solid #e5e7eb; background: #f9fafb; border-radius: 10px; padding: 12px;">
                <div style="color: #6b7280;">Maior</div>
                <div style="font-size: 16px; font-weight: 600;">R$ {{ number_format($maxPrice, 2, ',', '.') }}</div>
            </div>
            <div style="border: 1px solid #e5e7eb; background: #f9fafb; border-radius: 10px; padding: 12px;">
                <div style="color: #6b7280;">Ultimo</div>
                <div style="font-size: 16px; font-weight: 600;">R$ {{ number_format($latestPrice, 2, ',', '.') }}</div>
                <div style="margin-top: 4px; font-size: 11px; color: {{ $variationColor }};">
                    {{ $variation >= 0 ? '+' : '' }}{{ number_format($variation, 2, ',', '.') }}%
                </div>
            </div>
        </div>

        <div
            x-data="{
                chartId: @js($chartId),
                labels: @js($labels->all()),
                values: @js($prices->all()),
                async initChart() {
                    const ensureChartJs = async () => {
                        if (typeof window.Chart !== 'undefined') {
                            return;
                        }

                        if (window.__chartJsLoadingPromise) {
                            await window.__chartJsLoadingPromise;
                            return;
                        }

                        window.__chartJsLoadingPromise = new Promise((resolve, reject) => {
                            const existing = document.querySelector(`script[data-chartjs='1']`);

                            if (existing) {
                                existing.addEventListener('load', () => resolve(), { once: true });
                                existing.addEventListener('error', () => reject(), { once: true });
                                return;
                            }

                            const script = document.createElement('script');
                            script.src = 'https://cdn.jsdelivr.net/npm/chart.js@4.4.6/dist/chart.umd.min.js';
                            script.async = true;
                            script.dataset.chartjs = '1';
                            script.onload = () => resolve();
                            script.onerror = () => reject();
                            document.head.appendChild(script);
                        });

                        await window.__chartJsLoadingPromise;
                    };

                    await ensureChartJs();

                    const canvas = document.getElementById(this.chartId);
                    if (!canvas || typeof window.Chart === 'undefined') {
                        return;
                    }

                    const ctx = canvas.getContext('2d');
                    if (!ctx) {
                        return;
                    }

                    if (!window.__marketCharts) {
                        window.__marketCharts = {};
                    }

                    if (window.__marketCharts[this.chartId]) {
                        window.__marketCharts[this.chartId].destroy();
                    }

                    const gradient = ctx.createLinearGradient(0, 0, 0, 260);
                    gradient.addColorStop(0, 'rgba(37, 99, 235, 0.30)');
                    gradient.addColorStop(1, 'rgba(37, 99, 235, 0.02)');

                    const currency = new Intl.NumberFormat('pt-BR', {
                        style: 'currency',
                        currency: 'BRL',
                    });

                    window.__marketCharts[this.chartId] = new window.Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: this.labels,
                            datasets: [{
                                data: this.values,
                                borderColor: '#2563eb',
                                backgroundColor: gradient,
                                fill: true,
                                borderWidth: 2.5,
                                pointRadius: 3.5,
                                pointHoverRadius: 5,
                                pointBackgroundColor: '#2563eb',
                                pointBorderColor: '#ffffff',
                                pointBorderWidth: 1.5,
                                tension: 0.28,
                            }],
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            interaction: {
                                mode: 'index',
                                intersect: false,
                            },
                            plugins: {
                                legend: {
                                    display: false,
                                },
                                tooltip: {
                                    callbacks: {
                                        label: (context) => {
                                            const value = context && context.parsed && typeof context.parsed.y !== 'undefined'
                                                ? context.parsed.y
                                                : 0;
                                            return `Preco: ${currency.format(value)}`;
                                        },
                                    },
                                },
                            },
                            scales: {
                                y: {
                                    grid: {
                                        color: '#e5e7eb',
                                    },
                                    ticks: {
                                        callback: (value) => currency.format(Number(value)),
                                    },
                                },
                                x: {
                                    grid: {
                                        display: false,
                                    },
                                    ticks: {
                                        autoSkip: true,
                                        maxTicksLimit: 8,
                                    },
                                },
                            },
                        },
                    });
                },
            }"
            x-init="initChart()"
            style="overflow: hidden; border: 1px solid #e5e7eb; background: #ffffff; border-radius: 10px; padding: 12px;"
        >
            <div style="position: relative; height: 260px; width: 100%;">
                <canvas id="{{ $chartId }}" style="display: block; width: 100%; height: 100%;"></canvas>
            </div>
        </div>
    @endif
</div>
