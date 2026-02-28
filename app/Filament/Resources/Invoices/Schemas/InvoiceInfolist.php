<?php

namespace App\Filament\Resources\Invoices\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class InvoiceInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Resumo')
                    ->columns(3)
                    ->components([
                        TextEntry::make('market.name')
                            ->label('Mercado')
                            ->badge()
                            ->color('info')
                            ->placeholder('-'),
                        TextEntry::make('issued_at')
                            ->label('Emissao')
                            ->dateTime()
                            ->placeholder('-'),
                        TextEntry::make('total_amount')
                            ->label('Valor total')
                            ->money('BRL')
                            ->badge()
                            ->color('success')
                            ->placeholder('-'),
                    ]),
                Section::make('Controle')
                    ->columns(2)
                    ->components([
                        TextEntry::make('access_key')
                            ->label('Chave de acesso')
                            ->placeholder('-'),
                        TextEntry::make('items_count')
                            ->label('Itens na nota')
                            ->counts('items')
                            ->badge()
                            ->color('gray'),
                        TextEntry::make('created_at')
                            ->label('Criado em')
                            ->dateTime()
                            ->placeholder('-'),
                        TextEntry::make('updated_at')
                            ->label('Atualizado em')
                            ->dateTime()
                            ->placeholder('-'),
                    ]),
            ]);
    }
}
