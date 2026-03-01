<?php

namespace App\Filament\Resources\ShoppingLists\Schemas;

use App\Models\InvoiceItem;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ShoppingListInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Resumo da lista')
                    ->columns(3)
                    ->components([
                        TextEntry::make('name')
                            ->label('Nome')
                            ->weight('bold')
                            ->size('lg'),
                        TextEntry::make('items_count')
                            ->label('Produtos na lista')
                            ->counts('items')
                            ->badge()
                            ->color('info'),
                        TextEntry::make('estimated_best_total')
                            ->label('Total estimado (menores preços)')
                            ->state(function ($record): float {
                                $items = $record->items()->get(['product_id', 'quantity']);
                                $total = 0.0;

                                foreach ($items as $item) {
                                    $best = InvoiceItem::query()
                                        ->join('market_products', 'invoice_items.market_product_id', '=', 'market_products.id')
                                        ->where('market_products.product_id', $item->product_id)
                                        ->min('invoice_items.unit_price');

                                    if ($best !== null) {
                                        $total += ((float) $best) * ((float) $item->quantity);
                                    }
                                }

                                return $total;
                            })
                            ->money('BRL')
                            ->badge()
                            ->color('success'),
                    ]),
                Section::make('Detalhes')
                    ->columns(2)
                    ->components([
                        TextEntry::make('notes')
                            ->label('Observações')
                            ->placeholder('-')
                            ->columnSpanFull(),
                        TextEntry::make('created_at')
                            ->label('Criada em')
                            ->dateTime('d/m/Y H:i:s')
                            ->placeholder('-'),
                        TextEntry::make('updated_at')
                            ->label('Atualizada em')
                            ->dateTime('d/m/Y H:i:s')
                            ->placeholder('-'),
                    ]),
            ]);
    }
}
