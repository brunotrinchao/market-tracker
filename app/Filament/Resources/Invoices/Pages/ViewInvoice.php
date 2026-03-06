<?php

namespace App\Filament\Resources\Invoices\Pages;

use App\Filament\Resources\Invoices\InvoiceResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\ViewRecord;

class ViewInvoice extends ViewRecord
{
    protected static string $resource = InvoiceResource::class;

    protected static ?string $title = 'Detalhes da nota';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('editInvoice')
                ->label('Editar')
                ->icon('heroicon-o-pencil-square')
                ->slideOver()
                ->modalHeading('Editar nota')
                ->fillForm(fn (): array => [
                    'market_id' => $this->record->market_id,
                    'issued_at' => $this->record->issued_at,
                    'total_amount' => $this->record->total_amount,
                    'access_key' => $this->record->access_key,
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
                ->action(function (array $data): void {
                    $this->record->update($data);
                    $this->record->refresh();
                }),
            DeleteAction::make()
                ->label('Excluir')
                ->requiresConfirmation(),
        ];
    }
}
