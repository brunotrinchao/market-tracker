<?php

namespace App\Filament\Resources\Categories\Pages;

use App\Filament\Resources\Categories\CategoryResource;
use App\Models\Category;
use Filament\Actions\Action;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Str;

class ListCategories extends ListRecords
{
    protected static string $resource = CategoryResource::class;

    protected static ?string $title = 'Categorias';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('createCategory')
                ->label('Nova categoria')
                ->icon('heroicon-o-plus')
                ->modalHeading('Adicionar categoria')
                ->schema([
                    TextInput::make('name')
                        ->label('Nome')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('slug')
                        ->label('Slug')
                        ->maxLength(255)
                        ->helperText('Opcional. Se vazio, sera gerado automaticamente.'),
                    TagsInput::make('keywords')
                        ->label('Palavras-chave'),
                ])
                ->action(function (array $data): void {
                    $slug = trim((string) ($data['slug'] ?? ''));

                    if ($slug === '') {
                        $slug = Str::slug((string) $data['name']);
                    }

                    Category::query()->create([
                        'name' => $data['name'],
                        'slug' => $slug,
                        'keywords' => $data['keywords'] ?? [],
                    ]);

                    Notification::make()
                        ->title('Categoria criada com sucesso.')
                        ->success()
                        ->send();
                }),
        ];
    }
}
