<?php

namespace App\Filament\Resources\Invoices\Tables;

use App\Filament\Resources\Invoices\InvoiceResource;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class InvoicesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['market'])->withCount(['items']))
            ->recordUrl(fn ($record): string => InvoiceResource::getUrl('view', ['record' => $record]))
            ->columns([
                ViewColumn::make('mobile_card')
                    ->label('')
                    ->view('filament.tables.columns.invoice-mobile-card')
                    ->hiddenFrom('md'),
                TextColumn::make('market.name')
                    ->label('Mercado')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info')
                    ->visibleFrom('md'),
                TextColumn::make('access_key')
                    ->label('Chave de acesso')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('-')
                    ->visibleFrom('md'),
                TextColumn::make('issued_at')
                    ->label('Emissao')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable()
                    ->visibleFrom('md'),
                TextColumn::make('total_amount')
                    ->label('Valor total')
                    ->money('BRL')
                    ->sortable()
                    ->visibleFrom('md'),
                TextColumn::make('items_count')
                    ->label('Itens')
                    ->counts('items')
                    ->badge()
                    ->color('gray')
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
                SelectFilter::make('market_id')
                    ->label('Mercado')
                    ->relationship('market', 'name')
                    ->searchable(),
                Filter::make('issued_at')
                    ->label('Data de emissão')
                    ->form([
                        DatePicker::make('issued_from')->label('De'),
                        DatePicker::make('issued_until')->label('Até'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['issued_from'] ?? null, fn (Builder $q, $date): Builder => $q->whereDate('issued_at', '>=', $date))
                            ->when($data['issued_until'] ?? null, fn (Builder $q, $date): Builder => $q->whereDate('issued_at', '<=', $date));
                    }),
                Filter::make('value_min')
                    ->label('Valor mínimo R$ 100')
                    ->query(fn (Builder $query): Builder => $query->where('total_amount', '>=', 100))
                    ->toggle(),
            ])
            ->defaultSort('issued_at', 'desc');
    }
}
