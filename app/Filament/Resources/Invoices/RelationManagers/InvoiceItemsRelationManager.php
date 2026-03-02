<?php

namespace App\Filament\Resources\Invoices\RelationManagers;

use App\Filament\Resources\Products\ProductResource;
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
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Support\Enums\IconPosition;
use Illuminate\Support\HtmlString;

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
            ImageColumn::make('marketProduct.product.image')
                ->label('Imagem')
                ->defaultImageUrl('https://placehold.co/64x64/e5e7eb/6b7280?text=P')
                ->imageSize(36)
                ->square(),

            // Nome do Produto (via InvoiceItem -> MarketProduct -> Product)
            TextColumn::make('marketProduct.product.name')
                ->label('Produto')
                ->color('primary')
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->iconPosition(IconPosition::After)
                ->formatStateUsing(fn (?string $state): HtmlString => new HtmlString(
                    '<span style="text-decoration: underline;">' . e($state ?? '') . '</span>'
                ))
                ->url(fn ($record): ?string => $record->marketProduct?->product
                    ? ProductResource::getUrl('view', ['record' => $record->marketProduct->product])
                    : null)
                ->sortable(),

            TextColumn::make('marketProduct.product.category.name')
                ->label('Categoria')
                ->badge()
                ->placeholder('-')
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
        ->defaultSort('id', 'desc');
    }
}
