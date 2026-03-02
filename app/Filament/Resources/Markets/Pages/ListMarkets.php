<?php

namespace App\Filament\Resources\Markets\Pages;

use App\Filament\Resources\Markets\MarketResource;
use App\Models\Market;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;
use Illuminate\View\View;

class ListMarkets extends ListRecords
{
    protected static string $resource = MarketResource::class;

    protected static ?string $title = 'Mercados';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('showMarketsMap')
                ->label('Ver no mapa')
                ->icon('heroicon-o-map')
                ->color('gray')
                ->modalHeading('Supermercados no mapa')
                ->modalWidth(Width::FiveExtraLarge)
                ->modalSubmitAction(false)
                ->modalContent(function (): View {
                    $markets = Market::query()
                        ->with('addresses')
                        ->orderBy('name')
                        ->get()
                        ->map(function (Market $market): array {
                            $address = $market->addresses->first();

                            return [
                                'id' => $market->id,
                                'name' => $market->name,
                                'image' => $market->logo,
                                'address' => $address
                                    ? "{$address->street}, {$address->number}, {$address->neighborhood}, {$address->city} - {$address->state}"
                                    : 'Endereco nao informado',
                                'lat' => $address?->latitude !== null ? (float) $address->latitude : null,
                                'lng' => $address?->longitude !== null ? (float) $address->longitude : null,
                                'resource_url' => \App\Filament\Resources\Markets\MarketResource::getUrl('view', ['record' => $market]),
                            ];
                        })
                        ->values()
                        ->all();

                    return view('filament.pages.components.market-map', [
                        'markets' => $markets,
                    ]);
                }),
            Action::make('createMarket')
                ->label('Novo mercado')
                ->icon('heroicon-o-plus')
                ->modalHeading('Adicionar mercado')
                ->schema([
                    TextInput::make('name')
                        ->label('Nome')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('cnpj')
                        ->label('CNPJ')
                        ->maxLength(18)
                        ->unique(table: Market::class, column: 'cnpj'),
                    TextInput::make('logo')
                        ->label('URL da logo')
                        ->url()
                        ->maxLength(255),
                ])
                ->action(function (array $data): void {
                    Market::query()->create($data);

                    Notification::make()
                        ->title('Mercado criado com sucesso.')
                        ->success()
                        ->send();
                }),
        ];
    }
}
