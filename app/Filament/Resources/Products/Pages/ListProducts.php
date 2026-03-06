<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use App\Models\Category;
use App\Models\Product;
use App\Services\Products\ProductCategoryClassifier;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Str;

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
                ->slideOver()
                ->modalHeading('Adicionar produto')
                ->schema([
                    TextInput::make('name')
                        ->label('Nome')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('barcode')
                        ->label('Código de barras (opcional)')
                        ->maxLength(64),
                    Select::make('category_id')
                        ->label('Categoria')
                        ->options(fn (): array => Category::query()->orderBy('name')->pluck('name', 'id')->all())
                        ->searchable()
                        ->preload()
                        ->createOptionForm([
                            TextInput::make('name')
                                ->label('Nome da categoria')
                                ->required()
                                ->maxLength(255),
                            TagsInput::make('keywords')
                                ->label('Palavras-chave')
                                ->placeholder('Ex: arroz, feijao, cafe'),
                        ])
                        ->createOptionUsing(function (array $data): int {
                            $name = trim((string) ($data['name'] ?? ''));
                            $keywords = collect((array) ($data['keywords'] ?? []))
                                ->map(fn (mixed $keyword): string => trim((string) $keyword))
                                ->filter()
                                ->values()
                                ->all();

                            $existingCategory = Category::query()
                                ->where('name', $name)
                                ->first();

                            if ($existingCategory) {
                                return (int) $existingCategory->getKey();
                            }

                            $baseSlug = Str::slug($name);
                            if ($baseSlug === '') {
                                $baseSlug = 'categoria';
                            }

                            $slug = $baseSlug;
                            $suffix = 2;

                            while (Category::query()->where('slug', $slug)->exists()) {
                                $slug = $baseSlug . '-' . $suffix;
                                $suffix++;
                            }

                            $category = Category::query()->create([
                                'name' => $name,
                                'slug' => $slug,
                                'keywords' => $keywords,
                            ]);

                            return (int) $category->getKey();
                        })
                        ->placeholder('Sem categoria'),
                    TextInput::make('image')
                        ->label('URL da imagem')
                        ->url()
                        ->maxLength(255),
                ])
                ->action(function (array $data): void {
                    $barcode = trim((string) ($data['barcode'] ?? ''));

                    if ($barcode !== '') {
                        $existingByBarcode = Product::query()
                            ->where('barcode', $barcode)
                            ->first();

                        if ($existingByBarcode) {
                            Notification::make()
                                ->title('Produto já cadastrado para este código de barras')
                                ->warning()
                                ->send();

                            return;
                        }
                    }

                    $categoryId = $data['category_id'] ?? null;

                    if (! $categoryId) {
                        $categoryId = app(ProductCategoryClassifier::class)
                            ->inferCategoryId((string) ($data['name'] ?? ''));
                    }

                    Product::query()->create([
                        'name' => $data['name'],
                        'original_name' => $data['name'],
                        'barcode' => $barcode !== '' ? $barcode : null,
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
