<?php

namespace App\Services\Invoices;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class NfceAccessKeyLookupService
{
    public function lookup(string $accessKey): array
    {
        $accessKey = $this->normalizeAccessKey($accessKey);

        if (strlen($accessKey) !== 44) {
            throw new RuntimeException('Chave de acesso invalida. Informe 44 digitos.');
        }

        $urlTemplate = (string) config('services.nfce_lookup.url_template');

        if ($urlTemplate === '') {
            throw new RuntimeException('NFCE_LOOKUP_URL_TEMPLATE nao configurada.');
        }

        $url = str_replace('{access_key}', $accessKey, $urlTemplate);
        $timeout = (int) config('services.nfce_lookup.timeout', 30);
        $token = (string) config('services.nfce_lookup.token');
        $tokenHeader = (string) config('services.nfce_lookup.token_header', 'Authorization');
        $tokenPrefix = (string) config('services.nfce_lookup.token_prefix', 'Bearer ');

        $request = Http::timeout($timeout)->acceptJson();

        if ($token !== '') {
            $request = $request->withHeaders([
                $tokenHeader => $tokenPrefix . $token,
            ]);
        }

        $response = $request->get($url);

        if (! $response->successful()) {
            throw new RuntimeException('Falha ao consultar nota pela chave: HTTP ' . $response->status());
        }

        $payload = $response->json();

        if (! is_array($payload)) {
            throw new RuntimeException('Resposta invalida na consulta da chave de acesso.');
        }

        return $this->normalizePayload($payload, $accessKey);
    }

    public function normalizeAccessKey(string $value): string
    {
        return preg_replace('/\D/', '', $value) ?? '';
    }

    private function normalizePayload(array $payload, string $accessKey): array
    {
        $market = $payload['market'] ?? [];
        $invoice = $payload['invoice'] ?? [];
        $items = is_array($payload['items'] ?? null) ? $payload['items'] : [];

        if ($items === []) {
            throw new RuntimeException('Consulta por chave sem itens da nota.');
        }

        return [
            'market' => [
                'name' => trim((string) ($market['name'] ?? 'Mercado Desconhecido')),
                'cnpj' => preg_replace('/\D/', '', (string) ($market['cnpj'] ?? '')) ?: null,
                'zip_code' => preg_replace('/\D/', '', (string) ($market['zip_code'] ?? '')) ?: null,
                'address_details' => [
                    'street' => trim((string) ($market['address_details']['street'] ?? 'Nao informado')),
                    'number' => trim((string) ($market['address_details']['number'] ?? 'S/N')),
                    'neighborhood' => trim((string) ($market['address_details']['neighborhood'] ?? 'Nao informado')),
                    'city' => trim((string) ($market['address_details']['city'] ?? 'Nao informado')),
                    'state' => strtoupper(substr(trim((string) ($market['address_details']['state'] ?? 'NA')), 0, 2)),
                ],
            ],
            'invoice' => [
                'access_key' => $accessKey,
                'issued_at' => $invoice['issued_at'] ?? now(),
                'total_amount' => $invoice['total_amount'] ?? 0,
            ],
            'items' => collect($items)->map(function ($item, int $index): array {
                return [
                    'name' => trim((string) ($item['name'] ?? '')),
                    'code' => trim((string) ($item['code'] ?? ('KEY-' . ($index + 1)))),
                    'quantity' => (float) ($item['quantity'] ?? 1),
                    'unit' => strtoupper(trim((string) ($item['unit'] ?? 'UN'))),
                    'unit_price' => (float) ($item['unit_price'] ?? 0),
                    'total_price' => (float) ($item['total_price'] ?? 0),
                    'date' => now(),
                ];
            })->filter(fn (array $item): bool => $item['name'] !== '')->values()->all(),
        ];
    }
}

