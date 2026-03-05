<?php

namespace App\Services\Parsers;

use App\Models\Category;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Smalot\PdfParser\Parser;

class GeminiNfceParser
{
    public function parse(string $pdfPath): array
    {
        $text = $this->extractPdfText($pdfPath);
        $preparedText = $this->prepareSourceText($text);
        $aiMeta = [];

        $payload = $this->extractPayloadUsingCloudflareAi($preparedText, null, $aiMeta)
            ?? $this->extractPayloadFromSource($text, null);

        $normalized = $this->normalizePayload($payload);

        if ($aiMeta !== []) {
            $normalized['_ai'] = $aiMeta;
        }

        return $normalized;
    }

    public function extractAccessKeyFromPdf(string $pdfPath): ?string
    {
        $text = $this->extractPdfText($pdfPath);

        return $this->extractAccessKeyFromText($text);
    }

    public function parseFromQrUrl(string $qrUrl): array
    {
        $qrUrl = $this->normalizeQrUrl($qrUrl);

        if (! filter_var($qrUrl, FILTER_VALIDATE_URL)) {
            throw new RuntimeException('QR Code invalido. Nao foi encontrada uma URL valida da NFC-e.');
        }

        $cacheKey = 'nfce:qr:raw:' . sha1($qrUrl);
        $cached = Cache::get($cacheKey);

        if (is_array($cached)) {
            return $cached;
        }

        $responseBody = $this->fetchNfceResponseByQrUrl($qrUrl);
        $cleanText = $this->prepareSourceText($responseBody);
        $aiMeta = [];

        $payload = $this->extractPayloadUsingCloudflareAi($cleanText, $qrUrl, $aiMeta)
            ?? $this->extractPayloadFromSource($responseBody, $qrUrl);

        $normalized = $this->normalizePayload($payload);
        $normalized['invoice']['access_key'] ??= $this->extractAccessKeyFromText($qrUrl . PHP_EOL . $cleanText);
        if ($aiMeta !== []) {
            $normalized['_ai'] = $aiMeta;
        }

        Cache::put($cacheKey, $normalized, now()->addDays(7));

        return $normalized;
    }

    protected function extractPayloadUsingCloudflareAi(string $sourceText, ?string $qrUrl, array &$aiMeta = []): ?array
    {
        $accountId = trim((string) config('services.cloudflare_ai.account_id'));
        $apiToken = trim((string) config('services.cloudflare_ai.api_token'));
        $model = trim((string) config('services.cloudflare_ai.model', '@cf/meta/llama-3-8b-instruct'));
        $timeout = (int) config('services.cloudflare_ai.timeout', 90);
        $maxRetries = (int) config('services.cloudflare_ai.max_retries', 2);
        $initialBackoffMs = (int) config('services.cloudflare_ai.initial_backoff_ms', 1200);
        $maxSourceChars = (int) config('services.cloudflare_ai.max_source_chars', 12000);

        if ($accountId === '' || $apiToken === '') {
            return null;
        }

        $sourceText = trim($sourceText);
        if ($sourceText === '') {
            return null;
        }

        if (mb_strlen($sourceText) > $maxSourceChars) {
            $sourceText = mb_substr($sourceText, 0, $maxSourceChars);
        }

        $systemPrompt = $this->buildCloudflareSystemPrompt();
        $userPrompt = $this->buildCloudflareUserPrompt($sourceText, $qrUrl);
        $endpoint = sprintf('https://api.cloudflare.com/client/v4/accounts/%s/ai/run/%s', $accountId, $model);

        $attempt = 0;
        $lastError = null;

        while ($attempt < max(1, $maxRetries)) {
            $attempt++;

            try {
                $response = Http::timeout($timeout)
                    ->withToken($apiToken)
                    ->acceptJson()
                    ->post($endpoint, [
                        'messages' => [
                            [
                                'role' => 'system',
                                'content' => $systemPrompt,
                            ],
                            [
                                'role' => 'user',
                                'content' => $userPrompt,
                            ],
                        ],
                        'temperature' => 0,
                    ]);

                if (! $response->successful()) {
                    $lastError = new RuntimeException('Cloudflare AI retornou HTTP ' . $response->status());
                    usleep($initialBackoffMs * 1000);
                    continue;
                }

                $responsePayload = $response->json();
                if (! is_array($responsePayload)) {
                    $lastError = new RuntimeException('Cloudflare AI retornou payload invalido.');
                    usleep($initialBackoffMs * 1000);
                    continue;
                }

                if (($responsePayload['success'] ?? true) !== true) {
                    $errors = is_array($responsePayload['errors'] ?? null)
                        ? json_encode($responsePayload['errors'], JSON_UNESCAPED_UNICODE)
                        : 'erro desconhecido';
                    $lastError = new RuntimeException('Cloudflare AI falhou: ' . $errors);
                    usleep($initialBackoffMs * 1000);
                    continue;
                }

                $result = $responsePayload['result'] ?? [];
                $rawOutput = $this->extractModelRawOutput($result);
                if ($rawOutput === null) {
                    $lastError = new RuntimeException('Cloudflare AI nao retornou texto da inferencia.');
                    usleep($initialBackoffMs * 1000);
                    continue;
                }

                $decoded = $this->decodeJsonObjectFromText($rawOutput);
                if (! is_array($decoded)) {
                    $lastError = new RuntimeException('Cloudflare AI retornou resposta sem JSON valido.');
                    usleep($initialBackoffMs * 1000);
                    continue;
                }

                $aiMeta = [
                    'provider' => 'cloudflare',
                    'model' => $model,
                    'payload' => $result,
                    'raw_response' => $rawOutput,
                ];

                return $decoded;
            } catch (\Throwable $e) {
                $lastError = $e;
                usleep($initialBackoffMs * 1000);
            }
        }

        if ((bool) config('services.cloudflare_ai.fallback_to_regex', true) !== true && $lastError !== null) {
            throw new RuntimeException($lastError->getMessage(), previous: $lastError);
        }

        return null;
    }

    protected function buildCloudflareSystemPrompt(): string
    {
        return <<<'PROMPT'
Voce e um extrator de dados de NFC-e (nota fiscal de consumidor eletronica) do Brasil.
Sua tarefa e ler o texto bruto da nota e devolver APENAS um JSON valido, sem markdown e sem texto extra.
Regras:
1) Extraia somente o que estiver no texto. Nunca invente.
2) Campos desconhecidos devem ser null (ou array vazio em items).
3) Valores monetarios e quantidades devem ser numericos (ponto decimal), sem "R$".
4) CNPJ deve conter somente digitos.
5) zip_code deve conter somente digitos (8 quando possivel).
6) invoice.access_key deve conter somente digitos e ter 44 quando encontrado.
7) unit deve ser somente um destes valores em maiusculo: KG, UN ou L.
8) Se nao houver itens confiaveis, retorne items [].
9) Retorne no schema exato abaixo:
{
  "market": {
    "name": "string|null",
    "cnpj": "string|null",
    "zip_code": "string|null",
    "address_details": {
      "street": "string|null",
      "number": "string|null",
      "neighborhood": "string|null",
      "city": "string|null",
      "state": "string|null"
    }
  },
  "invoice": {
    "access_key": "string|null",
    "issued_at": "string|null",
    "total_amount": "number|null"
  },
  "items": [
    {
      "name": "string",
      "original_name": "string|null",
      "code": "string|null",
      "quantity": "number|null",
      "unit": "string|null",
      "category_suggestion": "string|null",
      "unit_price": "number|null",
      "total_price": "number|null"
    }
  ]
}
PROMPT;
    }

    protected function buildCloudflareUserPrompt(string $sourceText, ?string $qrUrl): string
    {
        $qrSection = $qrUrl ? ("URL do QR Code: " . $qrUrl . "\n\n") : '';
        $categories = $this->availableCategoryNamesForPrompt();
        $categoriesSection = $categories !== []
            ? ("Categorias existentes no sistema (use uma delas em items[].category_suggestion quando fizer sentido):\n- " . implode("\n- ", $categories) . "\n\n")
            : '';

        return $qrSection
            . $categoriesSection
            . "Texto bruto da nota (OCR/HTML limpo):\n"
            . $sourceText
            . "\n\nResponda apenas com JSON valido no schema solicitado.";
    }

    protected function availableCategoryNamesForPrompt(): array
    {
        return Category::query()
            ->orderBy('name')
            ->pluck('name')
            ->map(fn (string $name): string => trim($name))
            ->filter(fn (string $name): bool => $name !== '')
            ->unique()
            ->values()
            ->all();
    }

    protected function extractModelRawOutput(array $result): ?string
    {
        $candidates = [
            $result['response'] ?? null,
            data_get($result, 'output_text'),
            data_get($result, 'text'),
        ];

        foreach ($candidates as $candidate) {
            if (is_string($candidate) && trim($candidate) !== '') {
                return trim($candidate);
            }
        }

        return null;
    }

    protected function decodeJsonObjectFromText(string $text): ?array
    {
        $decoded = json_decode($text, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        if (preg_match('/\{.*\}/s', $text, $match)) {
            $decoded = json_decode($match[0], true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return null;
    }

    protected function extractPdfText(string $pdfPath): string
    {
        $parser = new Parser();
        $pdf = $parser->parseFile($pdfPath);
        $text = trim($pdf->getText());

        if ($text === '') {
            throw new RuntimeException('Nao foi possivel extrair texto do PDF.');
        }

        return $text;
    }

    protected function fetchNfceResponseByQrUrl(string $qrUrl): string
    {
        $response = Http::timeout(30)
            ->withHeaders([
                'User-Agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            ])
            ->get($qrUrl);

        if (! $response->successful()) {
            throw new RuntimeException('Nao foi possivel acessar a URL do QR Code. HTTP ' . $response->status());
        }

        $body = trim((string) $response->body());

        if ($body === '') {
            throw new RuntimeException('A URL do QR Code nao retornou conteudo da nota.');
        }

        return $body;
    }

    protected function normalizeQrUrl(string $qrUrl): string
    {
        $qrUrl = trim($qrUrl);

        if ($qrUrl === '') {
            return $qrUrl;
        }

        if (str_starts_with($qrUrl, 'www.')) {
            $qrUrl = 'https://' . $qrUrl;
        }

        if (! preg_match('/^https?:\/\//i', $qrUrl)) {
            $qrUrl = 'https://' . ltrim($qrUrl, '/');
        }

        return $qrUrl;
    }

    protected function extractPayloadFromSource(string $source, ?string $qrUrl): array
    {
        $text = $this->prepareSourceText($source);

        preg_match('/CNPJ\s*[:\-]?\s*([\d\.\/\-]{14,18})/iu', $text, $cnpjMatch);
        preg_match('/CEP\s*[:\-]?\s*([\d\-\.]+)/iu', $text, $zipMatch);

        $marketName = $this->extractMarketNameFromText($text) ?? $this->extractMarketNameFromHtml($source);
        $issuedAt = $this->extractIssuedAtFromText($text);
        $totalAmount = $this->extractInvoiceTotalFromText($text);
        $address = $this->extractAddressDetailsFromText($text);
        $zipCode = $zipMatch[1] ?? $this->extractZipCodeFromAddressLine($text);

        $items = $this->extractItemsFromHtml($source);
        if ($items === []) {
            $items = $this->extractItemsFromText($text, $totalAmount);
        }

        return [
            'market' => [
                'name' => $marketName ?: 'Mercado Desconhecido',
                'cnpj' => $cnpjMatch[1] ?? null,
                'zip_code' => $zipCode,
                'address_details' => $address,
            ],
            'invoice' => [
                'access_key' => $this->extractAccessKeyFromText(($qrUrl ?? '') . PHP_EOL . $text),
                'issued_at' => $issuedAt,
                'total_amount' => $totalAmount,
            ],
            'items' => $items,
        ];
    }

    protected function extractMarketNameFromHtml(string $html): ?string
    {
        $decoded = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        if (preg_match('/(Nome\/Raz[aã]o Social|Raz[aã]o Social|Emitente)\s*[:\-]?\s*([^\n]+)/iu', $decoded, $match)) {
            return $this->sanitizeMarketName($match[2] ?? null);
        }

        return null;
    }

    protected function extractItemsFromHtml(string $html): array
    {
        $items = [];

        if (! preg_match_all('/class="txtTit2"[^>]*>(.*?)<\/span>/is', $html, $nameMatches)) {
            return $items;
        }

        preg_match_all('/\(C[oó]digo[:\s]*(\d+)\)/iu', $html, $codeMatches);
        preg_match_all('/(?:Qtde|Qtd|Quantidade)[^\d]*([\d\.,]+)\s*([A-Za-z]{1,4})?/iu', $html, $qtyMatches, PREG_SET_ORDER);
        preg_match_all('/(?:Vl\.?\s*Unit|Valor\s*unit[aá]rio)[^\d]*([\d\.,]+)/iu', $html, $unitPriceMatches);
        preg_match_all('/(?:Vl\.?\s*Total|Valor\s*total)[^\d]*([\d\.,]+)/iu', $html, $totalMatches);

        foreach ($nameMatches[1] as $index => $rawName) {
            $name = $this->sanitizeProductName(strip_tags(html_entity_decode($rawName, ENT_QUOTES | ENT_HTML5, 'UTF-8')));
            if ($name === null) {
                continue;
            }

            $qty = $this->toFloat($qtyMatches[$index][1] ?? null) ?? 1.0;
            $unit = strtoupper(trim((string) ($qtyMatches[$index][2] ?? 'UN')));
            $unitPrice = $this->toFloat($unitPriceMatches[1][$index] ?? null);
            $totalPrice = $this->toFloat($totalMatches[1][$index] ?? null);

            if ($unitPrice === null && $totalPrice !== null && $qty > 0) {
                $unitPrice = round($totalPrice / $qty, 2);
            }

            if ($totalPrice === null && $unitPrice !== null) {
                $totalPrice = round($unitPrice * $qty, 2);
            }

                $items[] = [
                    'name' => $name,
                'code' => $codeMatches[1][$index] ?? null,
                'quantity' => $qty,
                'unit' => $unit !== '' ? $unit : 'UN',
                'unit_price' => $unitPrice,
                'total_price' => $totalPrice,
            ];
        }

        return $items;
    }

    protected function extractItemsFromText(string $text, ?float $fallbackTotal): array
    {
        $items = [];

        // Bloco comum da SEF/MG:
        // NOME / (Codigo) / Qtde total de itens / UN / Valor total
        $pattern = '/(?P<name>[^\n]+?)\s*\n+\s*\(C[oó]digo:\s*(?P<code>\d+)\)\s*\n+\s*Qtde total de [íi]t[êe]ns:\s*(?P<qty>[\d\.,]+)\s*\n+\s*UN:\s*(?P<unit>[A-Za-z]{1,4})\s*\n+\s*Valor total R\$:\s*R\$\s*(?P<total>[\d\.,]+)/iu';

        if (preg_match_all($pattern, $text, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $name = $this->sanitizeProductName($match['name'] ?? null);
                if ($name === null) {
                    continue;
                }

                $qty = $this->toFloat($match['qty'] ?? null) ?? 1.0;
                $total = $this->toFloat($match['total'] ?? null) ?? 0.0;
                $unitPrice = $qty > 0 ? round($total / $qty, 2) : $total;

                $items[] = [
                    'name' => $name,
                    'code' => $match['code'] ?? null,
                    'quantity' => $qty,
                    'unit' => strtoupper(trim($match['unit'] ?? 'UN')),
                    'unit_price' => $unitPrice,
                    'total_price' => $total,
                ];
            }
        }

        if ($items === []) {
            $fallbackPattern = '/(?P<name>[^\n]+?)\s*\(C[oó]digo[:\s]*(?P<code>\d+)\)\s*(?:Qtde|Quantidade|Qtd)[^\d]*(?P<qty>[\d\.,]+)\s*(?:(?:UN|Unidade|Kg|KG|Lt|LT|Ml|ML|PT)[:\s]*(?P<unit>[A-Za-z]{1,4}))?.*?(?:Valor\s*total|Vl\.?\s*total)[^\d]*(?P<total>[\d\.,]+)/iu';
            if (preg_match_all($fallbackPattern, $text, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $name = $this->sanitizeProductName($match['name'] ?? null);
                    if ($name === null) {
                        continue;
                    }

                    $qty = $this->toFloat($match['qty'] ?? null) ?? 1.0;
                    $total = $this->toFloat($match['total'] ?? null) ?? 0.0;
                    $unitPrice = $qty > 0 ? round($total / $qty, 2) : $total;

                    $items[] = [
                        'name' => $name,
                        'code' => $match['code'] ?? null,
                        'quantity' => $qty,
                        'unit' => strtoupper(trim($match['unit'] ?? 'UN')),
                        'unit_price' => $unitPrice,
                        'total_price' => $total,
                    ];
                }
            }
        }

        if ($items === []) {
            $items[] = [
                'name' => 'Item da NFC-e',
                'code' => null,
                'quantity' => 1,
                'unit' => 'UN',
                'unit_price' => $fallbackTotal ?? 0,
                'total_price' => $fallbackTotal ?? 0,
            ];
        }

        return $items;
    }

    protected function prepareSourceText(string $source): string
    {
        $source = trim($source);
        if ($source === '') {
            return $source;
        }

        $lower = strtolower($source);
        $looksLikeHtml = str_contains($lower, '<html')
            || str_contains($lower, '<body')
            || str_contains($lower, '<div');

        if ($looksLikeHtml) {
            $clean = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', ' ', $source) ?? $source;
            $clean = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is', ' ', $clean) ?? $clean;
            $clean = preg_replace('/<br\s*\/?>/i', "\n", $clean) ?? $clean;
            $clean = preg_replace('/<\/(p|div|tr|li|h[1-6]|td|th)>/i', "\n", $clean) ?? $clean;
            $clean = strip_tags($clean);
            $clean = html_entity_decode($clean, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $clean = preg_replace('/[ \t]+/', ' ', $clean) ?? $clean;
            $clean = preg_replace('/\r\n|\r/', "\n", $clean) ?? $clean;
            $clean = preg_replace('/\n{2,}/', "\n", $clean) ?? $clean;
            $source = trim($clean);
        }

        if (mb_strlen($source) > 20000) {
            $source = mb_substr($source, 0, 20000);
        }

        return $source;
    }

    protected function extractAccessKeyFromText(string $text): ?string
    {
        if (preg_match('/\b(\d{44})\b/', $text, $matches)) {
            return $matches[1];
        }

        if (preg_match('/(chave\s+de\s+acesso|chave)\D{0,40}((?:\d[\s.\-]?){44})/iu', $text, $matches)) {
            $digits = preg_replace('/\D/', '', $matches[2] ?? '') ?? '';

            return strlen($digits) === 44 ? $digits : null;
        }

        return null;
    }

    protected function extractMarketNameFromText(string $text): ?string
    {
        if (preg_match('/Nota Fiscal de Consumidor Eletr[oô]nica\s*\n+\s*([^\n]+)/iu', $text, $matches)) {
            $name = $this->sanitizeMarketName($matches[1] ?? null);
            if ($name !== null) {
                return $name;
            }
        }

        if (preg_match('/(Nome\/Raz[aã]o Social|Raz[aã]o Social|Emitente)\s*\n+\s*([^\n]+)/iu', $text, $matches)) {
            $name = $this->sanitizeMarketName($matches[2] ?? null);
            if ($name !== null) {
                return $name;
            }
        }

        if (preg_match('/([A-Z0-9 \/\-\.\&]{10,})\s*\n+\s*CNPJ\s*[:\-]?/u', $text, $matches)) {
            $name = $this->sanitizeMarketName($matches[1] ?? null);
            if ($name !== null) {
                return $name;
            }
        }

        return null;
    }

    protected function extractIssuedAtFromText(string $text): ?string
    {
        if (preg_match('/Data Emiss[aã]o\s*\n+\s*([0-9]{2}\/[0-9]{2}\/[0-9]{4}\s+[0-9]{2}:[0-9]{2}:[0-9]{2})/iu', $text, $matches)) {
            return trim((string) ($matches[1] ?? ''));
        }

        if (preg_match('/(Data de Emiss[aã]o|Emiss[aã]o)\s*[:\-]?\s*([0-9\/:\-\s]+)/iu', $text, $matches)) {
            return trim((string) ($matches[2] ?? ''));
        }

        return null;
    }

    protected function extractInvoiceTotalFromText(string $text): ?float
    {
        if (preg_match('/Valor total do servi[cç]o.*?R\$\s*([0-9\.,]+)/iu', $text, $matches)) {
            return $this->toFloat($matches[1] ?? null);
        }

        if (preg_match('/Valor pago R\$\s*\n+\s*([0-9\.,]+)/iu', $text, $matches)) {
            return $this->toFloat($matches[1] ?? null);
        }

        if (preg_match_all('/Valor total R\$\s*(?:\n+\s*)*R?\$?\s*([0-9\.,]+)/iu', $text, $matches) && isset($matches[1])) {
            $last = end($matches[1]);
            if ($last !== false) {
                return $this->toFloat($last);
            }
        }

        return null;
    }

    protected function extractAddressDetailsFromText(string $text): array
    {
        $details = [
            'street' => null,
            'number' => null,
            'neighborhood' => null,
            'city' => null,
            'state' => null,
        ];

        $line = $this->extractAddressLineFromText($text);
        if ($line === null) {
            return $details;
        }

        $parts = array_map('trim', explode(',', $line));
        $details['street'] = $parts[0] ?? null;
        $details['number'] = $parts[1] ?? null;
        $details['neighborhood'] = $parts[2] ?? null;

        if (preg_match('/-\s*([^,]+)\s*,\s*([A-Z]{2})/u', $line, $cityState)) {
            $details['city'] = trim((string) ($cityState[1] ?? ''));
            $details['state'] = trim((string) ($cityState[2] ?? ''));
        }

        return $details;
    }

    protected function extractZipCodeFromAddressLine(string $text): ?string
    {
        $line = $this->extractAddressLineFromText($text);
        if ($line === null) {
            return null;
        }

        // Ex.: "... CORACAO EUCARISTICO, 3106200 - BELO HORIZONTE, MG"
        if (preg_match('/,\s*([0-9]{7,8})\s*-\s*[^,\n]+,\s*[A-Z]{2}\b/u', $line, $zipMatch)) {
            return $zipMatch[1];
        }

        return null;
    }

    protected function extractAddressLineFromText(string $text): ?string
    {
        $primaryText = $this->extractPrimaryNfceSection($text);

        $line = $this->pickBestAddressLine($primaryText);
        if ($line !== null) {
            return $line;
        }

        return $this->pickBestAddressLine($text);
    }

    protected function pickBestAddressLine(string $text): ?string
    {
        $lines = preg_split('/\r\n|\r|\n/u', $text) ?: [];
        $candidates = [];

        $cnpjPosition = null;
        if (preg_match('/CNPJ\s*[:\-]?\s*[\d\.\/\-]{14,18}/iu', $text, $cnpjMatch, PREG_OFFSET_CAPTURE)) {
            $cnpjPosition = (int) ($cnpjMatch[0][1] ?? 0);
        }

        foreach ($lines as $line) {
            $line = trim((string) preg_replace('/\s+/', ' ', $line));
            if ($line === '') {
                continue;
            }

            if (! preg_match('/^(RUA|R\.|AVENIDA|AV\.|ALAMEDA|AL\.|ESTRADA|TRAVESSA|TV\.|PRA[ÇC]A|LARGO)\b/iu', $line)) {
                continue;
            }

            if ($this->isLikelyInstitutionalAddress($line)) {
                continue;
            }

            $score = 0;

            if (preg_match('/,\s*\d{7,8}\s*-\s*[^,\n]+,\s*[A-Z]{2}\b/u', $line)) {
                $score += 100;
            }

            if (preg_match('/-\s*[^,\n]+,\s*[A-Z]{2}\b/u', $line)) {
                $score += 40;
            }

            if (substr_count($line, ',') >= 3) {
                $score += 30;
            }

            if ($cnpjPosition !== null) {
                $linePosition = mb_stripos($text, $line);
                if ($linePosition !== false) {
                    $distance = (int) $linePosition - $cnpjPosition;
                    if ($distance >= 0 && $distance <= 800) {
                        $score += 60;
                    }
                }
            }

            $candidates[] = [
                'line' => $line,
                'score' => $score,
            ];
        }

        if ($candidates === []) {
            return null;
        }

        usort($candidates, fn (array $a, array $b): int => $b['score'] <=> $a['score']);
        $best = $candidates[0] ?? null;

        if (! is_array($best) || ($best['score'] ?? 0) <= 0) {
            return null;
        }

        return (string) $best['line'];
    }

    protected function extractPrimaryNfceSection(string $text): string
    {
        if (preg_match('/^(.+?)(?:\bInforma[cç][oõ]es gerais da Nota\b|\bImprimir\b|\bVers[aã]o\b)/isu', $text, $matches)) {
            return trim((string) ($matches[1] ?? $text));
        }

        return $text;
    }

    protected function isLikelyInstitutionalAddress(string $line): bool
    {
        $normalized = mb_strtolower($line);

        $markers = [
            'rodovia papa jo',
            'prédio gerais',
            'predio gerais',
            'bairro serra verde',
            'secretaria de estado',
            'sef/mg',
            'cep 31630-901',
        ];

        foreach ($markers as $marker) {
            if (str_contains($normalized, $marker)) {
                return true;
            }
        }

        return false;
    }

    protected function normalizePayload(array $payload): array
    {
        $market = $payload['market'] ?? [];
        $invoice = $payload['invoice'] ?? [];
        $items = is_array($payload['items'] ?? null) ? $payload['items'] : [];

        $normalizedItems = collect($items)
            ->map(fn (array $item, int $index): array => $this->normalizeItem($item, $index))
            ->filter(fn (array $item): bool => $item['name'] !== '')
            ->values()
            ->all();

        if ($normalizedItems === []) {
            throw new RuntimeException('Nao foi possivel extrair itens validos da nota fiscal.');
        }

        $invoiceTotal = $this->toFloat($invoice['total_amount'] ?? null);
        if ($invoiceTotal === null) {
            $invoiceTotal = round((float) collect($normalizedItems)->sum('total_price'), 2);
        }

        return [
            'market' => [
                'name' => $this->nonEmptyString($market['name'] ?? null) ?? 'Mercado Desconhecido',
                'cnpj' => $this->normalizeDigitsOrNull($market['cnpj'] ?? null),
                'zip_code' => $this->normalizeDigitsOrNull($market['zip_code'] ?? null) ?? '00000000',
                'address_details' => [
                    'street' => $this->nonEmptyString(data_get($market, 'address_details.street')) ?? 'Nao informado',
                    'number' => $this->nonEmptyString(data_get($market, 'address_details.number')) ?? 'S/N',
                    'neighborhood' => $this->nonEmptyString(data_get($market, 'address_details.neighborhood')) ?? 'Nao informado',
                    'city' => $this->nonEmptyString(data_get($market, 'address_details.city')) ?? 'Nao informado',
                    'state' => $this->normalizeState(data_get($market, 'address_details.state')) ?? 'NA',
                ],
            ],
            'invoice' => [
                'access_key' => $this->nonEmptyString($invoice['access_key'] ?? null),
                'issued_at' => $this->normalizeDateTime($invoice['issued_at'] ?? null),
                'total_amount' => $invoiceTotal,
            ],
            'items' => $normalizedItems,
        ];
    }

    protected function normalizeItem(array $item, int $index): array
    {
        $name = $this->sanitizeProductName($item['name'] ?? null) ?? '';
        $quantity = $this->toFloat($item['quantity'] ?? null) ?? 1.0;
        $unitPrice = $this->toFloat($item['unit_price'] ?? null);
        $totalPrice = $this->toFloat($item['total_price'] ?? null);

        if ($unitPrice === null && $totalPrice !== null && $quantity > 0) {
            $unitPrice = round($totalPrice / $quantity, 2);
        }

        if ($totalPrice === null && $unitPrice !== null) {
            $totalPrice = round($unitPrice * $quantity, 2);
        }

        $unitPrice ??= 0.0;
        $totalPrice ??= 0.0;

        return [
            'name' => $name,
            'original_name' => $this->nonEmptyString($item['original_name'] ?? $item['name'] ?? null),
            'code' => $this->nonEmptyString($item['code'] ?? null) ?? ('NFCE-' . ($index + 1)),
            'quantity' => $quantity,
            'unit' => $this->normalizeUnit($item['unit'] ?? null),
            'category_suggestion' => $this->nonEmptyString($item['category_suggestion'] ?? null),
            'total_price' => $totalPrice,
            'unit_price' => $unitPrice,
            'date' => now(),
        ];
    }

    protected function toFloat(mixed $value): ?float
    {
        if ($value === null) {
            return null;
        }

        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        $value = preg_replace('/[^\d,.\-]/', '', $value) ?? $value;

        if (str_contains($value, ',')) {
            $value = str_replace('.', '', $value);
            $value = str_replace(',', '.', $value);
        }

        return is_numeric($value) ? (float) $value : null;
    }

    protected function normalizeDateTime(mixed $value): Carbon
    {
        if ($value instanceof Carbon) {
            return $value;
        }

        if (! is_string($value) || trim($value) === '') {
            return now();
        }

        $formats = [
            'Y-m-d H:i:s',
            'Y-m-d\TH:i:s',
            'd/m/Y H:i:s',
            'd/m/Y H:i',
            'd/m/Y',
        ];

        foreach ($formats as $format) {
            try {
                return Carbon::createFromFormat($format, trim($value));
            } catch (\Throwable) {
                // tenta o próximo formato
            }
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return now();
        }
    }

    protected function normalizeDigitsOrNull(mixed $value): ?string
    {
        $value = preg_replace('/\D/', '', (string) $value) ?? '';

        return $value !== '' ? $value : null;
    }

    protected function normalizeState(mixed $value): ?string
    {
        $value = strtoupper(trim((string) $value));
        if ($value === '') {
            return null;
        }

        return substr($value, 0, 2);
    }

    protected function normalizeUnit(mixed $value): string
    {
        $value = strtoupper(trim((string) $value));
        $value = preg_replace('/\s+/', '', $value) ?? $value;

        return match ($value) {
            'KG', 'KGS', 'KILO', 'KILOGRAMA', 'KILOGRAMAS', 'QUILO', 'QUILOGRAMA', 'QUILOGRAMAS' => 'KG',
            'L', 'LT', 'LTS', 'LITRO', 'LITROS' => 'L',
            'UN', 'UND', 'UNID', 'UNIDADE', 'UNIDADES', 'PCT', 'PC', 'PCA', 'PEC', 'PECA', 'PECAS' => 'UN',
            default => 'UN',
        };
    }

    protected function nonEmptyString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value !== '' ? $value : null;
    }

    protected function sanitizeMarketName(mixed $value): ?string
    {
        $name = $this->nonEmptyString($value);
        if ($name === null) {
            return null;
        }

        $normalized = strtoupper(trim((string) preg_replace('/\s+/', ' ', $name)));
        $invalid = [
            'UF',
            'CNPJ',
            'EMITENTE',
            'CONSUMIDOR',
            'NOME / RAZAO SOCIAL',
            'NOME/RAZAO SOCIAL',
            'CHAVE DE ACESSO',
            'INFORMACOES GERAIS DA NOTA',
            'DESCRICAO',
            'NOTA FISCAL DE CONSUMIDOR ELETRONICA',
        ];

        if (in_array($normalized, $invalid, true)) {
            return null;
        }

        if (mb_strlen($normalized) < 4) {
            return null;
        }

        return $name;
    }

    protected function sanitizeProductName(mixed $value): ?string
    {
        $name = $this->nonEmptyString($value);
        if ($name === null) {
            return null;
        }

        $normalized = strtoupper(trim((string) preg_replace('/\s+/', ' ', $name)));
        $invalid = [
            'UF',
            'CNPJ',
            'EMITENTE',
            'CONSUMIDOR',
            'NOME / RAZAO SOCIAL',
            'NOME/RAZAO SOCIAL',
            'QTD',
            'QTDE',
            'VALOR TOTAL R$',
        ];

        if (in_array($normalized, $invalid, true)) {
            return null;
        }

        if (mb_strlen($normalized) < 3) {
            return null;
        }

        return $name;
    }
}
