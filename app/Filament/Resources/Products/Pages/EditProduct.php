<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use App\Services\Products\ProductCategoryClassifier;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected static ?string $title = 'Editar produto';

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()->label('Detalhes'),
            DeleteAction::make()
                ->label('Excluir')
                ->requiresConfirmation(),
        ];
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Produto atualizado com sucesso.');
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (! empty($data['category_id'])) {
            return $data;
        }

        $data['category_id'] = app(ProductCategoryClassifier::class)
            ->inferCategoryId((string) ($data['name'] ?? ''));

        return $data;
    }
}
