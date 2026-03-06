<?php

namespace App\Filament\Resources\ShoppingLists\Pages;

use App\Filament\Resources\ShoppingLists\ShoppingListResource;
use App\Models\ShoppingList;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Carbon;

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
                ->slideOver()
                ->modalHeading('Adicionar lista de compra')
                ->schema([
                    DatePicker::make('list_date')
                        ->label('Data')
                        ->default(now())
                        ->required(),
                    Textarea::make('notes')
                        ->label('Observações')
                        ->rows(3)
                        ->maxLength(1000),
                ])
                ->action(function (array $data): void {
                    $listDate = Carbon::parse($data['list_date']);

                    $shoppingList = ShoppingList::query()->create([
                        'name' => 'Lista - ' . $listDate->format('d/m/Y'),
                        'shopping_date' => $listDate->toDateString(),
                        'notes' => $data['notes'] ?? null,
                    ]);

                    Notification::make()
                        ->title('Lista criada com sucesso.')
                        ->success()
                        ->send();

                    $this->redirect(ShoppingListResource::getUrl('view', [
                        'record' => $shoppingList,
                    ]));
                }),
        ];
    }
}
