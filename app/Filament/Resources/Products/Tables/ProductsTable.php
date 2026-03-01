<?php

namespace App\Filament\Resources\Products\Tables;

use App\Filament\Resources\Products\ProductResource;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->withCount(['markets']))
            ->recordUrl(fn ($record): string => ProductResource::getUrl('view', ['record' => $record]))
            ->columns([
                ImageColumn::make('image')
                    ->label('Imagem')
                    ->defaultImageUrl('https://placehold.co/64x64/e5e7eb/6b7280?text=P')
                    ->imageSize(36)
                    ->square(),
                TextColumn::make('name')
                    ->label('Produto')
                    ->searchable()
                    ->sortable()
                    ->weight('semi-bold'),
                TextColumn::make('category')
                    ->label('Categoria')
                    ->searchable()
                    ->badge()
                    ->placeholder('-'),
                TextColumn::make('markets_count')
                    ->label('Mercados')
                    ->counts('markets')
                    ->badge()
                    ->color('info')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Atualizado em')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                //
            ])
            ->defaultSort('updated_at', 'desc');
    }
}
