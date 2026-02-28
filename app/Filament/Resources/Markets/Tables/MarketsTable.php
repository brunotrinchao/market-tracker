<?php

namespace App\Filament\Resources\Markets\Tables;

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
use Illuminate\Validation\Rule;

class MarketsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('addresses')->withCount(['marketProducts', 'invoices']))
            ->columns([
                ImageColumn::make('logo')
                    ->label('Logo')
                    ->circular()
                    ->imageSize(36)
                    ->defaultImageUrl('https://placehold.co/72x72/e5e7eb/6b7280?text=M'),
                TextColumn::make('name')
                    ->label('Mercado')
                    ->searchable()
                    ->sortable()
                    ->weight('semi-bold')
                    ->description(fn ($record) => $record->addresses->first()
                        ? $record->addresses->first()->city . ' - ' . $record->addresses->first()->state
                        : 'Endereco nao informado'),
                TextColumn::make('cnpj')
                    ->label('CNPJ')
                    ->searchable()
                    ->badge()
                    ->placeholder('-'),
                TextColumn::make('market_products_count')
                    ->label('Produtos')
                    ->counts('marketProducts')
                    ->badge()
                    ->color('info')
                    ->sortable(),
                TextColumn::make('invoices_count')
                    ->label('Notas')
                    ->counts('invoices')
                    ->badge()
                    ->color('success')
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
                Action::make('editMarket')
                    ->label('Editar')
                    ->icon('heroicon-o-pencil-square')
                    ->modalHeading('Editar mercado')
                    ->fillForm(fn ($record): array => [
                        'name' => $record->name,
                        'cnpj' => $record->cnpj,
                        'logo' => $record->logo,
                    ])
                    ->schema([
                        TextInput::make('name')
                            ->label('Nome')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('cnpj')
                            ->label('CNPJ')
                            ->maxLength(18)
                            ->rule(fn ($record) => Rule::unique('markets', 'cnpj')->ignore($record?->id)),
                        TextInput::make('logo')
                            ->label('URL da logo')
                            ->url()
                            ->maxLength(255),
                    ])
                    ->action(fn ($record, array $data) => $record->update($data)),
                DeleteAction::make()
                    ->label('Excluir')
                    ->requiresConfirmation(),
            ])
            ->toolbarActions([
                // Mantem o bulk delete para manutencao administrativa.
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('updated_at', 'desc');
    }
}
