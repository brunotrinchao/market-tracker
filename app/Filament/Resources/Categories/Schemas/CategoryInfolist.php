<?php

namespace App\Filament\Resources\Categories\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CategoryInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Detalhes da categoria')
                    ->columns(2)
                    ->components([
                        TextEntry::make('name')
                            ->label('Nome')
                            ->weight('bold'),
                        TextEntry::make('slug')
                            ->label('Slug')
                            ->badge(),
                        TextEntry::make('products_count')
                            ->label('Produtos vinculados')
                            ->counts('products')
                            ->badge()
                            ->color('info'),
                        TextEntry::make('keywords_text')
                            ->label('Palavras-chave')
                            ->state(fn ($record): string => collect((array) ($record->keywords ?? []))
                                ->filter(fn ($keyword): bool => trim((string) $keyword) !== '')
                                ->implode(', ')
                                ?: '-')
                            ->columnSpanFull(),
                        TextEntry::make('created_at')
                            ->label('Criada em')
                            ->date('d/m/Y')
                            ->placeholder('-'),
                        TextEntry::make('updated_at')
                            ->label('Atualizada em')
                            ->date('d/m/Y')
                            ->placeholder('-'),
                    ]),
            ]);
    }
}
