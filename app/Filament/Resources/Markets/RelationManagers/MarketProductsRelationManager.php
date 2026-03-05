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
                Select::make('unit')
                    ->label('Unidade')
                    ->options([
                        'KG' => 'KG (quilo)',
                        'UN' => 'UN (unidade)',
                        'L' => 'L (litro)',
                    ])
                    ->native(false)
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        $normalizedUnitPriceSql = "CASE
            WHEN invoice_items.quantity > 0 AND invoice_items.total_price IS NOT NULL
                THEN invoice_items.total_price / invoice_items.quantity
            ELSE invoice_items.unit_price
        END";

        return $table
            ->modifyQueryUsing(function (Builder $query) use ($normalizedUnitPriceSql): void {
                $query
                    ->withMin('invoiceItems', 'unit_price')
                    ->withMax('invoiceItems', 'unit_price')
                    ->addSelect([
                        'latest_price' => InvoiceItem::query()
                            ->select('unit_price')
                            ->whereColumn('market_product_id', 'market_products.id')
                            ->orderByDesc('created_at')
                            ->limit(1),
                        'latest_price_per_kg' => InvoiceItem::query()
                            ->selectRaw($normalizedUnitPriceSql)
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
                                            'invoice_items.quantity',
                                            'invoice_items.total_price',
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
                TextColumn::make('latest_price_per_kg')
                    ->label('Ultimo preco/kg')
                    ->formatStateUsing(function ($state, $record): string {
                        if (! $this->isKgUnit($record->unit ?? null) || $state === null) {
                            return '-';
                        }

                        return 'R$ ' . number_format((float) $state, 2, ',', '.') . '/kg';
                    }),
                TextColumn::make('created_at')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([
                CreateAction::make(),
            ]);
    }

    private function isKgUnit(?string $unit): bool
    {
        $normalized = strtoupper(trim((string) $unit));

        return in_array($normalized, ['KG', 'KILO', 'QUILO'], true);
    }
}
