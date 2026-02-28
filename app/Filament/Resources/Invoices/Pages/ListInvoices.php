<?php

namespace App\Filament\Resources\Invoices\Pages;

use App\Filament\Resources\Invoices\InvoiceResource;
use App\Models\Invoice;
use App\Models\Market;
use App\Services\InvoiceService;
use App\Services\Parsers\NfceMgParser;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ListInvoices extends ListRecords
{
    protected static string $resource = InvoiceResource::class;

    protected static ?string $title = 'Notas fiscais';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('createInvoice')
                ->label('Nova nota')
                ->icon('heroicon-o-plus')
                ->modalHeading('Adicionar nota')
                ->schema([
                    Select::make('market_id')
                        ->label('Mercado')
                        ->options(fn () => Market::query()->orderBy('name')->pluck('name', 'id')->all())
                        ->searchable()
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
                        ->maxLength(255)
                        ->unique(table: Invoice::class, column: 'access_key'),
                ])
                ->action(function (array $data): void {
                    Invoice::query()->create($data);

                    Notification::make()
                        ->title('Nota criada com sucesso.')
                        ->success()
                        ->send();
                }),
            
            // Nossa Action customizada para o PDF
            Action::make('importPdf')
                ->label('Importar Nota (PDF)')
                ->icon('heroicon-o-document-arrow-up')
                ->color('success')
                ->schema([
                    FileUpload::make('invoice_pdf')
                        ->label('Selecione o PDF da Nota')
                        ->disk('public')
                        ->acceptedFileTypes(['application/pdf'])
                        ->directory('invoices-temp')
                        ->maxSize(10240)
                        ->helperText('Apenas PDF. Tamanho máximo: 10 MB.')
                        ->required(),
                ])
                ->action(function (array $data, NfceMgParser $parser, InvoiceService $service) {
                    try {
                        // 1. Extrai o caminho do arquivo
                        $relativePath = $data['invoice_pdf'];

                        if (! Storage::disk('public')->exists($relativePath)) {
                            throw new \RuntimeException('Arquivo de nota não encontrado no disco público.');
                        }

                        $path = Storage::disk('public')->path($relativePath);
                        
                        // 2. Roda o Parser (Etapa 1 refinada)
                        $parsedData = $parser->parse($path);
                        
                        // 3. Salva via Service (Enriquecimento + DB)
                        $invoice = $service->storeInvoiceData($parsedData);

                        Notification::make()
                            ->title('Nota importada com sucesso!')
                            ->body("Mercado: {$invoice->market->name}")
                            ->success()
                            ->send();

                    } catch (\Exception $e) {
                        Log::error('Erro ao importar nota via PDF.', [
                            'exception' => $e,
                            'message' => $e->getMessage(),
                            'invoice_pdf' => $data['invoice_pdf'] ?? null,
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
