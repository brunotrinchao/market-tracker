<?php

namespace App\Filament\Pages;

use App\Filament\Resources\Markets\MarketResource;
use App\Models\Market;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Actions\Action;
use Filament\Support\Enums\Width;

class Dashboard extends BaseDashboard
{
    protected function getHeaderActions(): array
    {
        return [
            Action::make('viewMarketMap')
                ->label('Ver Mercados no Mapa')
                ->icon('heroicon-o-map')
                ->color('info')
                ->modalHeading('Mapa de Supermercados Próximos')
                ->modalWidth(Width::Full)
                ->modalSubmitAction(false) // Apenas visualização
                ->modalContent(fn () => view('filament.pages.components.market-map', [
                    'markets' => Market::with('addresses')
                        ->get()
                        ->map(function ($market) {
                            $address = $market->addresses()->first();
                            if (! $address) {
                                return null;
                            }
                            return [
                                'id' => $market->id,
                                'name' => $market->name,
                                'image' => $market->image ?? $market->logo,
                                'lat' => $address->latitude !== null ? (float) $address->latitude : null,
                                'lng' => $address->longitude !== null ? (float) $address->longitude : null,
                                'address' => "{$address->street}, {$address->number}, {$address->neighborhood}, {$address->city} - {$address->state}",
                                'resource_url' => MarketResource::getUrl('view', ['record' => $market]),
                            ];
                        })
                        ->filter()
                        ->values(),
                ])),
        ];
    }
}
