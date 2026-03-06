<?php

namespace App\Filament\Resources\Categories\Tables;

use App\Filament\Resources\Categories\CategoryResource;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->recordUrl(fn ($record): string => CategoryResource::getUrl('view', ['record' => $record]))
            ->columns([
                ViewColumn::make('mobile_card')
                    ->label('')
                    ->view('filament.tables.columns.category-mobile-card')
                    ->hiddenFrom('md'),
                TextColumn::make('name')
                    ->label('Categoria')
                    ->searchable()
                    ->sortable()
                    ->weight('semi-bold')
                    ->visibleFrom('md'),
                TextColumn::make('slug')
                    ->label('Slug')
                    ->badge()
                    ->searchable()
                    ->visibleFrom('md'),
                TextColumn::make('products_count')
                    ->label('Produtos')
                    ->counts('products')
                    ->badge()
                    ->color('info')
                    ->sortable()
                    ->visibleFrom('md'),
                TextColumn::make('created_at')
                    ->label('Criada em')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->visibleFrom('md'),
                TextColumn::make('updated_at')
                    ->label('Atualizada em')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable()
                    ->visibleFrom('md'),
            ])
            ->filters([
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
                Filter::make('has_products')
                    ->label('Com produtos')
                    ->query(fn (Builder $query): Builder => $query->has('products'))
                    ->toggle(),
            ])
            ->defaultSort('name');
    }
}
