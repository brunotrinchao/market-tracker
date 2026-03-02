<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use App\Models\Category;
use App\Models\Product;
use App\Services\Products\ProductCategoryClassifier;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
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
                    Select::make('category_id')
                        ->label('Categoria')
                        ->options(fn (): array => Category::query()->orderBy('name')->pluck('name', 'id')->all())
                        ->searchable()
                        ->preload()
                        ->placeholder('Sem categoria'),
                    TextInput::make('image')
                        ->label('URL da imagem')
                        ->url()
                        ->maxLength(255),
                ])
                ->action(function (array $data): void {
                    $categoryId = $data['category_id'] ?? null;

                    if (! $categoryId) {
                        $categoryId = app(ProductCategoryClassifier::class)
                            ->inferCategoryId((string) ($data['name'] ?? ''));
                    }

                    Product::query()->create([
                        'name' => $data['name'],
                        'category_id' => $categoryId,
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
