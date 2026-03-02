<?php

namespace App\Filament\Resources\Invoices\Pages;

use App\Filament\Resources\Invoices\InvoiceResource;
use App\Models\Invoice;
use App\Services\InvoiceService;
use App\Services\Parsers\GeminiNfceParser;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\Log;

class ListInvoices extends ListRecords
{
    protected static string $resource = InvoiceResource::class;

    protected static ?string $title = 'Notas fiscais';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('importInvoice')
                ->label('Importar nota')
                ->icon('heroicon-o-document-arrow-up')
                ->color('success')
                ->modalHeading('Importar nota fiscal')
                ->modalSubmitAction(false)
                ->modalCancelAction(false)
                ->schema([
                    TextInput::make('qr_url')
                        ->label('URL da NFC-e (teste manual)')
                        ->helperText('Cole a URL da nota para testar sem usar a câmera.')
                        ->required()
                        ->extraInputAttributes(['id' => 'invoice-qr-url']),
                    Placeholder::make('qr_scanner')
                        ->hiddenLabel()
                        ->content(new HtmlString(view('filament.resources.invoices.actions.qr-code-scanner')->render())),
                ])
                ->action(function (array $data, GeminiNfceParser $parser, InvoiceService $service) {
                    try {
                        $qrUrl = trim((string) ($data['qr_url'] ?? ''));

                        if ($qrUrl === '') {
                            throw new \RuntimeException('Leia o QR Code da nota antes de importar.');
                        }

                        $parsedData = $parser->parseFromQrUrl($qrUrl);
                        $accessKey = $parsedData['invoice']['access_key'] ?? null;

                        if ($accessKey && Invoice::query()->where('access_key', $accessKey)->exists()) {
                            throw new \RuntimeException('Ja existe uma nota com esta chave de acesso.');
                        }

                        $invoice = $service->storeInvoiceData($parsedData);

                        Notification::make()
                            ->title('Nota importada com sucesso')
                            ->body("Mercado: {$invoice->market->name}")
                            ->success()
                            ->send();

                    } catch (\Exception $e) {
                        Log::error('Erro ao importar nota via QR Code.', [
                            'exception' => $e,
                            'message' => $e->getMessage(),
                            'qr_url' => $data['qr_url'] ?? null,
                        ]);

                        Notification::make()
                            ->title('Erro na importação')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }
}
