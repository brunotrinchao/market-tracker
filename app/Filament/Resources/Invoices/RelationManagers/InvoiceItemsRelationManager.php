<?php

namespace App\Filament\Resources\Invoices\RelationManagers;

use Filament\Actions\AssociateAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DissociateAction;
use Filament\Actions\DissociateBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class InvoiceItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('unit_price')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('unit_price'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
        ->recordTitleAttribute('unit_price')
        ->columns([
            // Nome do Mercado (via Invoice -> Market)
            TextColumn::make('invoice.market.name')
                ->label('Mercado')
                ->sortable(),

            // Data da Compra
            TextColumn::make('invoice.issued_at')
                ->label('Data')
                ->dateTime('d/m/Y')
                ->sortable(),

            // Quantidade (Ex: 0.815 kg para Batata)
            TextColumn::make('quantity')
                ->label('Qtd')
                ->numeric(decimalPlaces: 3),

            // Preço Unitário (Ex: R$ 27,90 para o Café)
            TextColumn::make('unit_price')
                ->label('Preço Unitário')
                ->money('BRL')
                ->sortable(),

            // Valor Total do Item na Nota
            TextColumn::make('total_price')
                ->label('Total Item')
                ->money('BRL'),
        ])
        ->filters([
            // Filtro por mercado para comparar preços na região de BH
            SelectFilter::make('market')
                ->relationship('invoice.market', 'name')
        ])
        ->defaultSort('invoice.issued_at', 'desc');
    }
}
