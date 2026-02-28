<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use App\Models\Product;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    protected static ?string $title = 'Produtos';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('createProduct')
                ->label('Novo produto')
                ->icon('heroicon-o-plus')
                ->modalHeading('Adicionar produto')
                ->schema([
                    TextInput::make('name')
                        ->label('Nome')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('category')
                        ->label('Categoria')
                        ->maxLength(255),
                    TextInput::make('image')
                        ->label('URL da imagem')
                        ->url()
                        ->maxLength(255),
                ])
                ->action(function (array $data): void {
                    Product::query()->create([
                        'name' => $data['name'],
                        'category' => $data['category'] ?? null,
                        'image' => $data['image'] ?? null,
                    ]);

                    Notification::make()
                        ->title('Produto criado com sucesso.')
                        ->success()
                        ->send();
                }),
        ];
    }
}
