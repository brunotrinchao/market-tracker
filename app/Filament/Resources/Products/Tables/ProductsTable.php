<?php

namespace App\Filament\Resources\Products\Tables;

use App\Filament\Resources\Products\ProductResource;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('category')->withCount(['markets']))
            ->recordUrl(fn ($record): string => ProductResource::getUrl('view', ['record' => $record]))
            ->columns([
                ViewColumn::make('mobile_card')
                    ->label('')
                    ->view('filament.tables.columns.product-mobile-card')
                    ->hiddenFrom('md'),
                ImageColumn::make('image')
                    ->label('Imagem')
                    ->defaultImageUrl('https://placehold.co/64x64/e5e7eb/6b7280?text=P')
                    ->imageSize(36)
                    ->square()
                    ->visibleFrom('md'),
                TextColumn::make('name')
                    ->label('Produto')
                    ->searchable()
                    ->sortable()
                    ->weight('semi-bold')
                    ->visibleFrom('md'),
                TextColumn::make('original_name')
                    ->label('Nome original')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('-')
                    ->visibleFrom('md'),
                TextColumn::make('category.name')
                    ->label('Categoria')
                    ->searchable()
                    ->badge()
                    ->placeholder('-')
                    ->visibleFrom('md'),
                TextColumn::make('markets_count')
                    ->label('Mercados')
                    ->counts('markets')
                    ->badge()
                    ->color('info')
                    ->sortable()
                    ->visibleFrom('md'),
                TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->visibleFrom('md'),
                TextColumn::make('updated_at')
                    ->label('Atualizado em')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable()
                    ->toggleable()
                    ->visibleFrom('md'),
            ])
            ->filters([
                SelectFilter::make('category_id')
                    ->label('Categoria')
                    ->relationship('category', 'name')
                    ->searchable(),
                Filter::make('created_at')
                    ->label('Período de criação')
                    ->form([
                        DatePicker::make('created_from')->label('De'),
                        DatePicker::make('created_until')->label('Até'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['created_from'] ?? null, fn (Builder $q, $date): Builder => $q->whereDate('created_at', '>=', $date))
                            ->when($data['created_until'] ?? null, fn (Builder $q, $date): Builder => $q->whereDate('created_at', '<=', $date));
                    }),
                Filter::make('with_barcode')
                    ->label('Com código de barras')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('barcode')->where('barcode', '!=', ''))
                    ->toggle(),
            ])
            ->defaultSort('updated_at', 'desc');
    }
}
