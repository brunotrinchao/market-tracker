<?php

namespace App\Filament\Resources\Invoices\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class InvoicesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->withCount(['items']))
            ->columns([
                TextColumn::make('market.name')
                    ->label('Mercado')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info'),
                TextColumn::make('access_key')
                    ->label('Chave de acesso')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('-'),
                TextColumn::make('issued_at')
                    ->label('Emissao')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('total_amount')
                    ->label('Valor total')
                    ->money('BRL')
                    ->sortable(),
                TextColumn::make('items_count')
                    ->label('Itens')
                    ->counts('items')
                    ->badge()
                    ->color('gray')
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
                Action::make('editInvoice')
                    ->label('Editar')
                    ->icon('heroicon-o-pencil-square')
                    ->modalHeading('Editar nota')
                    ->fillForm(fn ($record): array => [
                        'market_id' => $record->market_id,
                        'issued_at' => $record->issued_at,
                        'total_amount' => $record->total_amount,
                        'access_key' => $record->access_key,
                    ])
                    ->schema([
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
                            ->numeric()
                            ->required(),
                        TextInput::make('access_key')
                            ->label('Chave de acesso')
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
            ->defaultSort('issued_at', 'desc');
    }
}
