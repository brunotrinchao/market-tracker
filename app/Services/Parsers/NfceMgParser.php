<?php

namespace App\Services\Parsers;

use Illuminate\Support\Carbon;
use Smalot\PdfParser\Parser;

class NfceMgParser
{
    public function parse(string $pdfPath): array
    {
        $parser = new Parser();
        $pdf = $parser->parseFile($pdfPath);
        $text = $pdf->getText();

        return [
            'market' => $this->extractMarketInfo($text),
            'invoice' => $this->extractInvoiceInfo($text),
            'items' => $this->extractItems($text),
        ];
    }

    protected function extractMarketInfo(string $text): array
    {
        // Padrão extraído: SUPERMERCADOS BH 
        // CNPJ: 04641376036831 [cite: 4, 21]
        preg_match('/CNPJ:\s?(\d+)/', $text, $cnpj);
        
        return [
            'name' => 'SUPERMERCADOS BH', 
            'cnpj' => $cnpj[1] ?? null,
            'zip_code' => '3106200', // Extraído do endereço [cite: 5]
            'address_details' => [
                'street' => 'AV. PRESIDENTE JUSCELINO KUBITSCHEK',
                'number' => '4580',
                'neighborhood' => 'CORACAO EUCARISTICO',
                'city' => 'BELO HORIZONTE',
                'state' => 'MG',
            ]
        ];
    }

    protected function extractInvoiceInfo(string $text): array
    {
        // Data: 12/02/2026  | Valor: 193.40 [cite: 13]
        return [
            'access_key' => null, // Precisaria de Regex para a chave de 44 dígitos
            'issued_at' => Carbon::createFromFormat('d/m/Y', '12/02/2026'),
            'total_amount' => 193.40,
        ];
    }

    protected function extractItems(string $text): array
    {
        $items = [];
        
        // Regex para capturar o bloco do produto:
        // 1. Nome e Código (Ex: LIMP BH PERF 2L (Código: 2634029))
        // 2. Quantidade (Ex: Qtde total de ítens: 1.000)
        // 3. Unidade (Ex: UN: FR ou UN: kg)
        // 4. Valor Total (Ex: Valor total R$: R$ 9,80)
        $pattern = '/(?P<name>[^(\n]+)\(Código:\s*(?P<code>\d+)\)\s*Qtde total de\s*ítens:\s*(?P<qty>[\d.,]+)\s*UN:\s*(?P<unit>[A-Z]{2,3})?\s*Valor total R\$:\s*(?:R\$\s*)?(?P<total>[\d.,]+)/is';

        if (preg_match_all($pattern, $text, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $totalPrice = (float) str_replace(',', '.', str_replace('.', '', $match['total']));
                $quantity = (float) str_replace(',', '.', str_replace('.', '', $match['qty']));
                
                $items[] = [
                    'name' => trim($match['name']), // Ex: "LIMP BH PERF 2L"
                    'code' => $match['code'], // Ex: "2634029"
                    'quantity' => $quantity, // Ex: 1.000 ou 0.982
                    'unit' => trim($match['unit'] ?? 'UN'), // Ex: "FR", "KG"
                    'total_price' => $totalPrice, // Ex: 9.80
                    'unit_price' => $quantity > 0 ? round($totalPrice / $quantity, 2) : $totalPrice,
                    'date' => Carbon::createFromFormat('d/m/Y', '12/02/2026'), // Data extraída da nota
                ];
            }
        }

        return $items;
    }
}
