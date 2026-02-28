<?php

namespace App\Filament\Resources\Products\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informacoes do produto')
                    ->description('Dados utilizados para padronizar comparacao de precos entre mercados.')
                    ->columns(2)
                    ->components([
                        TextInput::make('name')
                            ->label('Nome')
                            ->placeholder('Ex: Cafe Torrado 500g')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        TextInput::make('category')
                            ->label('Categoria')
                            ->placeholder('Ex: Mercearia')
                            ->maxLength(255),
                        TextInput::make('image')
                            ->label('URL da imagem')
                            ->placeholder('https://...')
                            ->url()
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
