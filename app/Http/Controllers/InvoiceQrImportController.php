<?php

namespace App\Http\Controllers;

use App\Filament\Resources\Invoices\InvoiceResource;
use App\Models\Invoice;
use App\Services\InvoiceService;
use App\Services\Parsers\GeminiNfceParser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class InvoiceQrImportController extends Controller
{
    public function __invoke(
        Request $request,
        GeminiNfceParser $parser,
        InvoiceService $service
    ): JsonResponse {
        $data = $request->validate([
            'qr_url' => ['required', 'string', 'max:2048'],
        ]);

        try {
            $qrUrl = trim((string) ($data['qr_url'] ?? ''));
            $parsedData = $parser->parseFromQrUrl($qrUrl);
            $accessKey = $parsedData['invoice']['access_key'] ?? null;

            if ($accessKey && Invoice::query()->where('access_key', $accessKey)->exists()) {
                return response()->json([
                    'message' => 'Ja existe uma nota com esta chave de acesso.',
                ], 422);
            }

            $invoice = $service->storeInvoiceData($parsedData);

            return response()->json([
                'message' => 'Nota importada com sucesso.',
                'redirect_url' => InvoiceResource::getUrl('view', ['record' => $invoice]),
            ]);
        } catch (\Throwable $e) {
            Log::error('Erro ao importar nota via QR global.', [
                'exception' => $e,
                'message' => $e->getMessage(),
                'qr_url' => $data['qr_url'] ?? null,
            ]);

            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}

