<?php

namespace App\Filament\Resources\ShoppingLists\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ShoppingListsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->withCount(['items']))
            ->columns([
                TextColumn::make('name')
                    ->label('Lista')
                    ->searchable()
                    ->sortable()
                    ->weight('semi-bold'),
                TextColumn::make('items_count')
                    ->label('Itens')
                    ->counts('items')
                    ->badge()
                    ->color('info')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Criada em')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Atualizada em')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make()->label('Ver'),
                Action::make('editShoppingList')
                    ->label('Editar')
                    ->icon('heroicon-o-pencil-square')
                    ->modalHeading('Editar lista de compra')
                    ->fillForm(fn ($record): array => [
                        'name' => $record->name,
                        'notes' => $record->notes,
                    ])
                    ->schema([
                        TextInput::make('name')
                            ->label('Nome da lista')
                            ->required()
                            ->maxLength(255),
                        Textarea::make('notes')
                            ->label('Observacoes')
                            ->rows(3)
                            ->maxLength(1000),
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
