<?php

namespace App\Filament\Resources\ShoppingLists\Pages;

use App\Filament\Resources\ShoppingLists\ShoppingListResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\ViewRecord;

class ViewShoppingList extends ViewRecord
{
    protected static string $resource = ShoppingListResource::class;

    protected static ?string $title = 'Detalhes da lista de compra';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('editShoppingList')
                ->label('Editar')
                ->icon('heroicon-o-pencil-square')
                ->modalHeading('Editar lista de compra')
                ->fillForm(fn (): array => [
                    'name' => $this->record->name,
                    'notes' => $this->record->notes,
                ])
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
                    $this->record->update($data);
                    $this->record->refresh();
                }),
            DeleteAction::make()
                ->label('Excluir')
                ->requiresConfirmation(),
        ];
    }
}
