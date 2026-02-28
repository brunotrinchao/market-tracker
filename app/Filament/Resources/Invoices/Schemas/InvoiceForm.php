<?php

namespace App\Filament\Resources\Invoices\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class InvoiceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Dados da nota')
                    ->description('Informacoes principais da NFC-e para consolidacao de historico de precos.')
                    ->columns(2)
                    ->components([
                        Select::make('market_id')
                            ->label('Mercado')
                            ->relationship('market', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        DateTimePicker::make('issued_at')
                            ->label('Data de emissao')
                            ->seconds(false)
                            ->required(),
                        TextInput::make('total_amount')
                            ->label('Valor total')
                            ->prefix('R$')
                            ->required()
                            ->numeric(),
                        TextInput::make('access_key')
                            ->label('Chave de acesso')
                            ->placeholder('Opcional')
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
