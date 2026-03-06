<?php

namespace App\Filament\Resources\Markets\Pages;

use App\Filament\Resources\Markets\MarketResource;
use Filament\Actions\Action as HeaderAction;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rule;

class ViewMarket extends ViewRecord
{
    protected static string $resource = MarketResource::class;

    protected static ?string $title = 'Detalhes do mercado';

    protected function getHeaderActions(): array
    {
        return [
            HeaderAction::make('editMarket')
                ->label('Editar')
                ->icon('heroicon-o-pencil-square')
                ->slideOver()
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
            HeaderAction::make('editAddress')
                ->label('Editar endereco')
                ->icon('heroicon-o-map-pin')
                ->slideOver()
                ->modalHeading('Editar endereco do mercado')
                ->fillForm(function (): array {
                    $address = $this->record->addresses()->latest('id')->first();

                    return [
                        'street' => $address?->street,
                        'number' => $address?->number,
                        'neighborhood' => $address?->neighborhood,
                        'city' => $address?->city,
                        'state' => $address?->state,
                        'zip_code' => $address?->zip_code,
                        'latitude' => $address?->latitude,
                        'longitude' => $address?->longitude,
                    ];
                })
                ->schema([
                    TextInput::make('street')
                        ->label('Rua')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('number')
                        ->label('Numero')
                        ->required()
                        ->maxLength(30),
                    TextInput::make('neighborhood')
                        ->label('Bairro')
                        ->required()
                        ->maxLength(120),
                    TextInput::make('city')
                        ->label('Cidade')
                        ->required()
                        ->maxLength(120),
                    TextInput::make('state')
                        ->label('UF')
                        ->required()
                        ->maxLength(2),
                    TextInput::make('zip_code')
                        ->label('CEP')
                        ->required()
                        ->maxLength(20)
                        ->suffixAction(
                            HeaderAction::make('buscarCep')
                                ->icon('heroicon-m-map-pin')
                                ->tooltip('Buscar endereco')
                                ->action(function (): void {
                                    $this->fillAddressFromCurrentModalCep();
                                })
                        )
                        ->reactive(),
                    TextInput::make('latitude')
                        ->label('Latitude'),
                    TextInput::make('longitude')
                        ->label('Longitude'),
                ])
                ->action(function (array $data): void {
                    $address = $this->record->addresses()->latest('id')->first();

                    if ($address) {
                        $address->update($data);
                    } else {
                        $this->record->addresses()->create($data);
                    }

                    $this->record->refresh();
                }),
            DeleteAction::make()
                ->label('Excluir')
                ->requiresConfirmation(),
        ];
    }

    private function fillAddressFromCurrentModalCep(): void
    {
        $actions = $this->mountedActions ?? [];
        if ($actions === []) {
            return;
        }

        $index = null;
        for ($i = count($actions) - 1; $i >= 0; $i--) {
            if (($actions[$i]['name'] ?? null) === 'editAddress' && isset($actions[$i]['data']) && is_array($actions[$i]['data'])) {
                $index = $i;
                break;
            }
        }

        if ($index === null) {
            return;
        }

        $state = (string) ($this->mountedActions[$index]['data']['zip_code'] ?? '');
        $cep = preg_replace('/\D/', '', (string) $state) ?? '';

        if (strlen($cep) !== 8) {
            Notification::make()
                ->title('Informe um CEP válido com 8 dígitos.')
                ->warning()
                ->send();
            return;
        }

        try {
            $response = Http::timeout(8)->get("https://brasilapi.com.br/api/cep/v2/{$cep}");
    
            if (! $response->successful()) {
                Notification::make()
                    ->title('Não foi possível consultar o CEP.')
                    ->danger()
                    ->send();
                return;
            }

            $payload = $response->json();
            if (! is_array($payload) || ($payload['erro'] ?? false)) {
                Notification::make()
                    ->title('CEP não encontrado.')
                    ->warning()
                    ->send();
                return;
            }

            $this->mountedActions[$index]['data'] = [];

            $this->mountedActions[$index]['data']['street'] = trim((string) ($payload['street'] ?? '')) ?: null;
            $this->mountedActions[$index]['data']['neighborhood'] = trim((string) ($payload['neighborhood'] ?? '')) ?: null;
            $this->mountedActions[$index]['data']['city'] = trim((string) ($payload['city'] ?? '')) ?: null;
            $this->mountedActions[$index]['data']['state'] = strtoupper(trim((string) ($payload['state'] ?? ''))) ?: null;
            $this->mountedActions[$index]['data']['zip_code'] = strtoupper(trim((string) ($payload['cep'] ?? ''))) ?: null;

            if($payload['location']['coordinates']['longitude'] ?? null) {
                $this->mountedActions[$index]['data']['latitude'] = $payload['location']['coordinates']['latitude'];
            }
            if($payload['location']['coordinates']['longitude'] ?? null) {
                $this->mountedActions[$index]['data']['longitude'] = $payload['location']['coordinates']['longitude'];
            }
  

            Notification::make()
                ->title('Endereço carregado pelo CEP.')
                ->success()
                ->send();
        } catch (\Throwable) {
            Notification::make()
                ->title('Falha ao buscar CEP no momento.')
                ->danger()
                ->send();
        }
    }
}
