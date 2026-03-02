<?php

namespace App\Filament\Resources\ShoppingLists\Pages;

use App\Filament\Resources\ShoppingLists\ShoppingListResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateShoppingList extends CreateRecord
{
    protected static string $resource = ShoppingListResource::class;

    protected static ?string $title = 'Criar lista de compra';

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('edit', [
            'record' => $this->getRecord(),
        ]);
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Lista de compra criada com sucesso.');
    }
}
