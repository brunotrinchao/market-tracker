<?php

namespace App\Filament\Resources\Categories\Schemas;

use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informacoes da categoria')
                    ->description('Use palavras-chave para melhorar a categorizacao automatica dos produtos.')
                    ->columns(2)
                    ->components([
                        TextInput::make('name')
                            ->label('Nome')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        TextInput::make('slug')
                            ->label('Slug')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->helperText('Identificador unico da categoria.'),
                        TagsInput::make('keywords')
                            ->label('Palavras-chave')
                            ->placeholder('Ex: arroz, feijao, cafe')
                            ->helperText('A classificacao automatica usa essas palavras para sugerir categoria.')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}

