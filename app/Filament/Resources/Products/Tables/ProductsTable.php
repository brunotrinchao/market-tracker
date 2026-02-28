<?php

namespace App\Filament\Resources\Products\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->withCount(['markets']))
            ->columns([
                ImageColumn::make('image')
                    ->label('Imagem')
                    ->defaultImageUrl('https://placehold.co/64x64/e5e7eb/6b7280?text=P')
                    ->imageSize(36)
                    ->square(),
                TextColumn::make('name')
                    ->label('Produto')
                    ->searchable()
                    ->sortable()
                    ->weight('semi-bold'),
                TextColumn::make('category')
                    ->label('Categoria')
                    ->searchable()
                    ->badge()
                    ->placeholder('-'),
                TextColumn::make('markets_count')
                    ->label('Mercados')
                    ->counts('markets')
                    ->badge()
                    ->color('info')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Atualizado em')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make()->label('Detalhes'),
                Action::make('editProduct')
                    ->label('Editar')
                    ->icon('heroicon-o-pencil-square')
                    ->modalHeading('Editar produto')
                    ->fillForm(fn ($record): array => [
                        'name' => $record->name,
                        'category' => $record->category,
                        'image' => $record->image,
                    ])
                    ->schema([
                        TextInput::make('name')
                            ->label('Nome')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('category')
                            ->label('Categoria')
                            ->maxLength(255),
                        TextInput::make('image')
                            ->label('URL da imagem')
                            ->url()
                            ->maxLength(255),
                    ])
                    ->action(fn ($record, array $data) => $record->update($data)),
                DeleteAction::make()
                    ->label('Excluir')
                    ->requiresConfirmation(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('updated_at', 'desc');
    }
}
