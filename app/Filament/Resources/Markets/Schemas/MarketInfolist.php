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
                Section::make('Endereco do mercado')
                    ->columns(2)
                    ->components([
                        TextEntry::make('address_street')
                            ->label('Rua')
                            ->state(function ($record): string {
                                $address = $record->addresses()->latest('id')->first();
                                $street = trim(implode(', ', array_filter([$address?->street, $address?->number])));

                                return $street !== '' ? $street : '-';
                            }),
                        TextEntry::make('address_neighborhood')
                            ->label('Bairro')
                            ->state(fn ($record): string => $record->addresses()->latest('id')->first()?->neighborhood ?? '-'),
                        TextEntry::make('address_city_state')
                            ->label('Cidade / UF')
                            ->state(function ($record): string {
                                $address = $record->addresses()->latest('id')->first();
                                $cityState = trim(implode(' - ', array_filter([$address?->city, $address?->state])));

                                return $cityState !== '' ? $cityState : '-';
                            }),
                        TextEntry::make('address_zip')
                            ->label('CEP')
                            ->state(fn ($record): string => $record->addresses()->latest('id')->first()?->zip_code ?? '-'),
                        TextEntry::make('address_latitude')
                            ->label('Latitude')
                            ->state(fn ($record): string => (string) ($record->addresses()->latest('id')->first()?->latitude ?? '-')),
                        TextEntry::make('address_longitude')
                            ->label('Longitude')
                            ->state(fn ($record): string => (string) ($record->addresses()->latest('id')->first()?->longitude ?? '-')),
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
