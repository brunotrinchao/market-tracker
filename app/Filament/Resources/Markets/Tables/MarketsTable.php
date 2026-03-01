<?php

namespace App\Filament\Resources\Markets\Tables;

use App\Filament\Resources\Markets\MarketResource;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
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
                ImageColumn::make('logo')
                    ->label('Logo')
                    ->circular()
                    ->imageSize(36)
                    ->defaultImageUrl('https://placehold.co/72x72/e5e7eb/6b7280?text=M'),
                TextColumn::make('name')
                    ->label('Mercado')
                    ->searchable()
                    ->sortable()
                    ->weight('semi-bold')
                    ->description(fn ($record) => $record->addresses->first()
                        ? $record->addresses->first()->city . ' - ' . $record->addresses->first()->state
                        : 'Endereco nao informado'),
                TextColumn::make('cnpj')
                    ->label('CNPJ')
                    ->searchable()
                    ->badge()
                    ->placeholder('-'),
                TextColumn::make('market_products_count')
                    ->label('Produtos')
                    ->counts('marketProducts')
                    ->badge()
                    ->color('info')
                    ->sortable(),
                TextColumn::make('invoices_count')
                    ->label('Notas')
                    ->counts('invoices')
                    ->badge()
                    ->color('success')
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
