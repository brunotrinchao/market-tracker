<?php

namespace App\Filament\Resources\ShoppingLists\Tables;

use App\Filament\Resources\ShoppingLists\ShoppingListResource;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ShoppingListsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->withCount(['items']))
            ->recordUrl(fn ($record): string => ShoppingListResource::getUrl('view', ['record' => $record]))
            ->columns([
                ViewColumn::make('mobile_card')
                    ->label('')
                    ->view('filament.tables.columns.shopping-list-mobile-card')
                    ->hiddenFrom('md'),
                TextColumn::make('name')
                    ->label('Lista')
                    ->searchable()
                    ->sortable()
                    ->weight('semi-bold')
                    ->visibleFrom('md'),
                TextColumn::make('shopping_date')
                    ->label('Data da compra')
                    ->date('d/m/Y')
                    ->placeholder('-')
                    ->sortable()
                    ->visibleFrom('md'),
                TextColumn::make('items_count')
                    ->label('Itens')
                    ->counts('items')
                    ->badge()
                    ->color('info')
                    ->sortable()
                    ->visibleFrom('md'),
                TextColumn::make('created_at')
                    ->label('Criada em')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->visibleFrom('md'),
                TextColumn::make('updated_at')
                    ->label('Atualizada em')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable()
                    ->toggleable()
                    ->visibleFrom('md'),
            ])
            ->filters([
                Filter::make('shopping_date')
                    ->label('Data da compra')
                    ->form([
                        DatePicker::make('shopping_from')->label('De'),
                        DatePicker::make('shopping_until')->label('Até'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['shopping_from'] ?? null, fn (Builder $q, $date): Builder => $q->whereDate('shopping_date', '>=', $date))
                            ->when($data['shopping_until'] ?? null, fn (Builder $q, $date): Builder => $q->whereDate('shopping_date', '<=', $date));
                    }),
                Filter::make('with_items')
                    ->label('Com itens')
                    ->query(fn (Builder $query): Builder => $query->has('items'))
                    ->toggle(),
            ])
            ->defaultSort('updated_at', 'desc');
    }
}
