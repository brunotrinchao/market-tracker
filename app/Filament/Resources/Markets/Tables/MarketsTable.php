<?php

namespace App\Filament\Resources\Markets\Tables;

use App\Filament\Resources\Markets\MarketResource;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MarketsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('addresses')->withCount(['marketProducts', 'invoices']))
            ->recordUrl(fn ($record): string => MarketResource::getUrl('view', ['record' => $record]))
            ->columns([
                ViewColumn::make('mobile_card')
                    ->label('')
                    ->view('filament.tables.columns.market-mobile-card')
                    ->hiddenFrom('md'),
                ImageColumn::make('logo')
                    ->label('Logo')
                    ->circular()
                    ->imageSize(36)
                    ->defaultImageUrl('https://placehold.co/72x72/e5e7eb/6b7280?text=M')
                    ->visibleFrom('md'),
                TextColumn::make('name')
                    ->label('Mercado')
                    ->searchable()
                    ->sortable()
                    ->weight('semi-bold')
                    ->description(fn ($record) => $record->addresses->first()
                        ? $record->addresses->first()->city . ' - ' . $record->addresses->first()->state
                        : 'Endereço não informado')
                    ->visibleFrom('md'),
                TextColumn::make('cnpj')
                    ->label('CNPJ')
                    ->searchable()
                    ->badge()
                    ->placeholder('-')
                    ->visibleFrom('md'),
                TextColumn::make('market_products_count')
                    ->label('Produtos')
                    ->counts('marketProducts')
                    ->badge()
                    ->color('info')
                    ->sortable()
                    ->visibleFrom('md'),
                TextColumn::make('invoices_count')
                    ->label('Notas')
                    ->counts('invoices')
                    ->badge()
                    ->color('success')
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
                Filter::make('updated_at')
                    ->label('Atualização')
                    ->form([
                        DatePicker::make('updated_from')->label('De'),
                        DatePicker::make('updated_until')->label('Até'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['updated_from'] ?? null, fn (Builder $q, $date): Builder => $q->whereDate('updated_at', '>=', $date))
                            ->when($data['updated_until'] ?? null, fn (Builder $q, $date): Builder => $q->whereDate('updated_at', '<=', $date));
                    }),
                Filter::make('with_cnpj')
                    ->label('Com CNPJ')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('cnpj')->where('cnpj', '!=', ''))
                    ->toggle(),
                Filter::make('with_invoices')
                    ->label('Com notas')
                    ->query(fn (Builder $query): Builder => $query->has('invoices'))
                    ->toggle(),
            ])
            ->defaultSort('updated_at', 'desc');
    }
}
