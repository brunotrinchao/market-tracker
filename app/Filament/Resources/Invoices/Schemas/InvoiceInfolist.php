<?php

namespace App\Filament\Resources\Invoices\Schemas;

use Filament\Actions\Action;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class InvoiceInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Resumo')
                    ->columns(4)
                    ->components([
                        ImageEntry::make('market.logo')
                            ->label('Logo')
                            ->defaultImageUrl(function ($record): string {
                                $name = trim((string) ($record?->market?->name ?? 'Mercado'));
                                $parts = array_values(array_filter(explode(' ', $name)));
                                $initials = '';

                                if (count($parts) >= 2) {
                                    $initials = strtoupper(Str::substr($parts[0], 0, 1) . Str::substr($parts[1], 0, 1));
                                } else {
                                    $initials = strtoupper(Str::substr(Str::replace(' ', '', $name), 0, 2));
                                }

                                $initials = $initials !== '' ? $initials : 'MC';

                                return 'https://ui-avatars.com/api/?name=' . urlencode($initials) . '&size=128&background=E5E7EB&color=374151&bold=true';
                            })
                            ->imageSize(52)
                            ->circular(),
                        TextEntry::make('market.name')
                            ->label('Mercado')
                            ->badge()
                            ->color('info')
                            ->action(
                                Action::make('viewMarketDetails')
                                    ->modalHeading('Informacoes do mercado')
                                    ->modalSubmitAction(false)
                                    ->modalCancelActionLabel('Fechar')
                                    ->modalContent(function ($record): HtmlString {
                                        $market = $record->market;
                                        $address = $market?->addresses()->first();

                                        $street = trim(implode(', ', array_filter([
                                            $address?->street,
                                            $address?->number,
                                        ])));
                                        $cityState = trim(implode(' - ', array_filter([
                                            $address?->city,
                                            $address?->state,
                                        ])));

                                        $html = '<div style="display:grid;gap:8px;">';
                                        $html .= '<div><strong>Nome:</strong> ' . e($market?->name ?? '-') . '</div>';
                                        $html .= '<div><strong>CNPJ:</strong> ' . e($market?->cnpj ?? '-') . '</div>';
                                        $html .= '<div><strong>Rua:</strong> ' . e($street !== '' ? $street : '-') . '</div>';
                                        $html .= '<div><strong>Bairro:</strong> ' . e($address?->neighborhood ?? '-') . '</div>';
                                        $html .= '<div><strong>Cidade/UF:</strong> ' . e($cityState !== '' ? $cityState : '-') . '</div>';
                                        $html .= '<div><strong>CEP:</strong> ' . e($address?->zip_code ?? '-') . '</div>';
                                        $html .= '<div><strong>Latitude:</strong> ' . e($address?->latitude ?? '-') . '</div>';
                                        $html .= '<div><strong>Longitude:</strong> ' . e($address?->longitude ?? '-') . '</div>';
                                        $html .= '</div>';

                                        return new HtmlString($html);
                                    })
                                    ->visible(fn ($record): bool => (bool) $record?->market),
                            )
                            ->placeholder('-'),
                        TextEntry::make('issued_at')
                            ->label('Emissao')
                            ->dateTime('d/m/Y H:i:s')
                            ->placeholder('-'),
                        TextEntry::make('total_amount')
                            ->label('Valor total')
                            ->money('BRL')
                            ->badge()
                            ->color('success')
                            ->placeholder('-'),
                    ]),
                Section::make('Controle')
                    ->columns(2)
                    ->components([
                        TextEntry::make('access_key')
                            ->label('Chave de acesso')
                            ->placeholder('-'),
                        TextEntry::make('items_count')
                            ->label('Itens na nota')
                            ->counts('items')
                            ->badge()
                            ->color('gray'),
                        TextEntry::make('created_at')
                            ->label('Criado em')
                            ->dateTime('d/m/Y H:i:s')
                            ->placeholder('-'),
                        TextEntry::make('updated_at')
                            ->label('Atualizado em')
                            ->dateTime('d/m/Y H:i:s')
                            ->placeholder('-'),
                    ]),
            ]);
    }
}
