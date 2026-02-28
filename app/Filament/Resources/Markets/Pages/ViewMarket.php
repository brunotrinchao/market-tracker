<?php

namespace App\Filament\Resources\Markets\Pages;

use App\Filament\Resources\Markets\MarketResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Validation\Rule;

class ViewMarket extends ViewRecord
{
    protected static string $resource = MarketResource::class;

    protected static ?string $title = 'Detalhes do mercado';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('editMarket')
                ->label('Editar')
                ->icon('heroicon-o-pencil-square')
                ->modalHeading('Editar mercado')
                ->fillForm(fn (): array => [
                    'name' => $this->record->name,
                    'cnpj' => $this->record->cnpj,
                    'logo' => $this->record->logo,
                ])
                ->schema([
                    TextInput::make('name')
                        ->label('Nome')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('cnpj')
                        ->label('CNPJ')
                        ->maxLength(18)
                        ->rule(fn () => Rule::unique('markets', 'cnpj')->ignore($this->record->id)),
                    TextInput::make('logo')
                        ->label('URL da logo')
                        ->url()
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
