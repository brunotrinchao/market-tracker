<?php

namespace App\Filament\Resources\Markets\Schemas;

use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MarketInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Resumo')
                    ->columns(3)
                    ->components([
                        ImageEntry::make('logo')
                            ->label('Logo')
                            ->circular()
                            ->imageSize(64)
                            ->placeholder('-'),
                        TextEntry::make('name')
                            ->label('Nome')
                            ->size('lg')
                            ->weight('bold'),
                        TextEntry::make('cnpj')
                            ->label('CNPJ')
                            ->badge()
                            ->placeholder('Nao informado'),
                    ]),
                Section::make('Indicadores')
                    ->columns(3)
                    ->components([
                        TextEntry::make('market_products_count')
                            ->label('Produtos vinculados')
                            ->counts('marketProducts')
                            ->badge()
                            ->color('info'),
                        TextEntry::make('invoices_count')
                            ->label('Notas cadastradas')
                            ->counts('invoices')
                            ->badge()
                            ->color('success'),
                        TextEntry::make('addresses_count')
                            ->label('Enderecos')
                            ->counts('addresses')
                            ->badge()
                            ->color('gray'),
                    ]),
                Section::make('Controle')
                    ->columns(2)
                    ->components([
                        TextEntry::make('created_at')
                            ->label('Criado em')
                            ->dateTime('d/m/Y H:i:s')
                            ->placeholder('-'),
                        TextEntry::make('updated_at')
                            ->label('Atualizado em')
                            ->dateTime('d/m/Y H:i:s')
                            ->placeholder('-'),
                    ]),
            ]);
    }
}
