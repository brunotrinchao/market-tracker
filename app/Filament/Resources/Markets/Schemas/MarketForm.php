<?php

namespace App\Filament\Resources\Markets\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MarketForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informações do mercado')
                    ->description('Preencha os dados principais para identificacao do supermercado.')
                    ->columns(2)
                    ->components([
                        TextInput::make('name')
                            ->label('Nome')
                            ->placeholder('Ex: Supermercado Centro BH')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        TextInput::make('cnpj')
                            ->label('CNPJ')
                            ->placeholder('00.000.000/0000-00')
                            ->maxLength(18)
                            ->unique(ignoreRecord: true)
                            ->helperText('Opcional, mas deve ser unico quando informado.'),
                        TextInput::make('logo')
                            ->label('URL da logo')
                            ->placeholder('https://...')
                            ->url()
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
