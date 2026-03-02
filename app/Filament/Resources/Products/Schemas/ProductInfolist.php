<?php

namespace App\Filament\Resources\Products\Schemas;

use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ProductInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Resumo')
                    ->columns(3)
                    ->components([
                        ImageEntry::make('image')
                            ->label('Imagem')
                            ->defaultImageUrl('https://placehold.co/80x80/e5e7eb/6b7280?text=P')
                            ->imageSize(64)
                            ->square(),
                        TextEntry::make('name')
                            ->label('Nome')
                            ->size('lg')
                            ->weight('bold'),
                        TextEntry::make('category')
                            ->label('Categoria')
                            ->badge()
                            ->placeholder('Nao informada'),
                        TextEntry::make('original_name')
                            ->label('Nome original')
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ]),
                Section::make('Indicadores')
                    ->columns(2)
                    ->components([
                        TextEntry::make('market_products_count')
                            ->label('Mercados com este produto')
                            ->counts('marketProducts')
                            ->badge()
                            ->color('info'),
                        TextEntry::make('markets_count')
                            ->label('Mercados vinculados')
                            ->counts('markets')
                            ->badge()
                            ->color('success'),
                    ]),
                Section::make('Controle')
                    ->columns(2)
                    ->components([
                        TextEntry::make('created_at')
                            ->label('Criado em')
                            ->dateTime('d/m/Y H:i:s')
                            ->placeholder('-'),
                        TextEntry::make('updated_at')
                            ->label('Atualizado em')
                            ->dateTime('d/m/Y H:i:s')
                            ->placeholder('-'),
                    ]),
            ]);
    }
}
