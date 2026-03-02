<?php

namespace App\Filament\Resources\Products\Schemas;

use Filament\Forms\Components\Select;
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
                        TextInput::make('original_name')
                            ->label('Nome original')
                            ->placeholder('Ex: CAFE TORR 500G')
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Select::make('category_id')
                            ->label('Categoria')
                            ->relationship('category', 'name')
                            ->searchable()
                            ->preload()
                            ->placeholder('Sem categoria')
                            ->helperText('Se nao informar, o sistema tenta identificar automaticamente.'),
                        TextInput::make('image')
                            ->label('URL da imagem')
                            ->placeholder('https://...')
                            ->url()
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
