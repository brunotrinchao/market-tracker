<?php

namespace App\Filament\Resources\Markets\Pages;

use App\Filament\Resources\Markets\MarketResource;
use App\Models\Market;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListMarkets extends ListRecords
{
    protected static string $resource = MarketResource::class;

    protected static ?string $title = 'Mercados';

    protected function getHeaderActions(): array
    {
        return [
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
