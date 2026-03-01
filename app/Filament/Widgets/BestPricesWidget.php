<?php

namespace App\Filament\Widgets;

use App\Models\InvoiceItem;
use Filament\Actions\BulkActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Support\Facades\DB;

class BestPricesWidget extends TableWidget
{
    protected static ?string $heading = 'Melhores oportunidades (último preço por produto/mercado)';

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                InvoiceItem::query()
                    ->select('invoice_items.*')
                    ->join(DB::raw('(SELECT market_product_id, MAX(invoice_id) as max_inv FROM invoice_items GROUP BY market_product_id) as latest'), function ($join) {
                        $join->on('invoice_items.market_product_id', '=', 'latest.market_product_id')
                             ->on('invoice_items.invoice_id', '=', 'latest.max_inv');
                    })
                    ->orderBy('invoice_items.unit_price')
                    ->limit(12)
            )
            ->columns([
                TextColumn::make('marketProduct.product.name')
                    ->label('Produto')
                    ->searchable(),
                TextColumn::make('marketProduct.market.name')
                    ->label('Mercado'),
                TextColumn::make('unit_price')
                    ->label('Preço Unitário')
                    ->money('BRL')
                    ->sortable()
                    ->color(fn ($state) => $state < 10 ? 'success' : 'gray'),
                TextColumn::make('invoice.issued_at')
                    ->label('Data da Nota')
                    ->dateTime('d/m/Y H:i:s'),
            ]);
    }
}
