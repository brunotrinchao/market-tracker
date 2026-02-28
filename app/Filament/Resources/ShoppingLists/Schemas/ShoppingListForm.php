<?php

namespace App\Filament\Resources\ShoppingLists\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ShoppingListForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Dados da lista')
                    ->description('Cadastre os produtos que deseja comprar e compare os menores preços por mercado.')
                    ->components([
                        TextInput::make('name')
                            ->label('Nome da lista')
                            ->placeholder('Ex: Compra do mês')
                            ->required()
                            ->maxLength(255),
                        Textarea::make('notes')
                            ->label('Observações')
                            ->rows(3)
                            ->maxLength(1000),
                    ]),
            ]);
    }
}
