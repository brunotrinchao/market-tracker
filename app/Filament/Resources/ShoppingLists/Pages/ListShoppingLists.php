<?php

namespace App\Filament\Resources\ShoppingLists\Pages;

use App\Filament\Resources\ShoppingLists\ShoppingListResource;
use App\Models\ShoppingList;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListShoppingLists extends ListRecords
{
    protected static string $resource = ShoppingListResource::class;

    protected static ?string $title = 'Listas de compra';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('createShoppingList')
                ->label('Nova lista')
                ->icon('heroicon-o-plus')
                ->modalHeading('Adicionar lista de compra')
                ->schema([
                    TextInput::make('name')
                        ->label('Nome da lista')
                        ->required()
                        ->maxLength(255),
                    Textarea::make('notes')
                        ->label('Observacoes')
                        ->rows(3)
                        ->maxLength(1000),
                ])
                ->action(function (array $data): void {
                    ShoppingList::query()->create($data);

                    Notification::make()
                        ->title('Lista criada com sucesso.')
                        ->success()
                        ->send();
                }),
        ];
    }
}
