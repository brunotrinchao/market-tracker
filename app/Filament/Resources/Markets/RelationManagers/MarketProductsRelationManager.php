<?php

namespace App\Filament\Resources\Markets\RelationManagers;

use App\Models\InvoiceItem;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;

class MarketProductsRelationManager extends RelationManager
{
    protected static string $relationship = 'marketProducts';

    protected static ?string $title = 'Produtos';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('product_id')
                    ->relationship('product', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('external_code')
                    ->label('Codigo externo')
                    ->required(),
                TextInput::make('unit')
                    ->label('Unidade')
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query): void {
                $query
                    ->withMin('invoiceItems', 'unit_price')
                    ->withMax('invoiceItems', 'unit_price')
                    ->addSelect([
                        'latest_price' => InvoiceItem::query()
                            ->select('unit_price')
                            ->whereColumn('market_product_id', 'market_products.id')
                            ->orderByDesc('created_at')
                            ->limit(1),
                    ]);
            })
            ->recordTitleAttribute('product.name')
            ->columns([
                TextColumn::make('product.name')
                    ->label('Produto')
                    ->searchable()
                    ->sortable()
                    ->action(
                        Action::make('priceHistory')
                            ->modalHeading(fn ($record): string => 'Historico de preço - ' . ($record->product->name ?? 'Produto'))
                            ->modalSubmitAction(false)
                            ->modalContent(fn ($record): View => view(
                                'filament.resources.markets.relation-managers.market-product-price-history-modal',
                                [
                                    'marketProduct' => $record,
                                    'history' => InvoiceItem::query()
                                        ->where('market_product_id', $record->id)
                                        ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
                                        ->orderBy('invoices.issued_at')
                                        ->get([
                                            'invoice_items.unit_price',
                                            'invoices.issued_at',
                                        ]),
                                ],
                            )),
                    ),
                TextColumn::make('external_code')
                    ->label('Codigo externo')
                    ->searchable(),
                TextColumn::make('unit')
                    ->label('Unidade')
                    ->searchable(),
                TextColumn::make('invoice_items_min_unit_price')
                    ->label('Menor preco')
                    ->money('BRL'),
                TextColumn::make('invoice_items_max_unit_price')
                    ->label('Maior preco')
                    ->money('BRL'),
                TextColumn::make('latest_price')
                    ->label('Ultimo preco')
                    ->money('BRL'),
                TextColumn::make('created_at')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([
                CreateAction::make(),
            ]);
    }
}
