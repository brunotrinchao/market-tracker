<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use App\Services\Products\ProductCategoryClassifier;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    protected static ?string $title = 'Criar produto';

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('view', [
            'record' => $this->getRecord(),
        ]);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (! empty($data['category_id'])) {
            return $data;
        }

        $data['category_id'] = app(ProductCategoryClassifier::class)
            ->inferCategoryId((string) ($data['name'] ?? ''));

        return $data;
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Produto criado com sucesso.');
    }
}
