<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Models\Category;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informacoes do produto')
                    ->description('Dados utilizados para padronizar comparacao de precos entre mercados.')
                    ->columns(2)
                    ->components([
                        TextInput::make('name')
                            ->label('Nome')
                            ->placeholder('Ex: Cafe Torrado 500g')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        TextInput::make('original_name')
                            ->label('Nome original')
                            ->placeholder('Ex: CAFE TORR 500G')
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Select::make('category_id')
                            ->label('Categoria')
                            ->relationship('category', 'name')
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
                            ->placeholder('Sem categoria')
                            ->helperText('Se nao informar, o sistema tenta identificar automaticamente.'),
                        TextInput::make('image')
                            ->label('URL da imagem')
                            ->placeholder('https://...')
                            ->url()
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
