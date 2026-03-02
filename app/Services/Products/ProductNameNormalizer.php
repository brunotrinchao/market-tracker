<?php

namespace App\Services\Products;

use Illuminate\Support\Str;

class ProductNameNormalizer
{
    /**
     * Dicionario de abreviacoes comuns em cupons fiscais.
     *
     * @var array<string, string>
     */
    private array $dictionary = [
        'ACUC' => 'ACUCAR',
        'ACUCAR' => 'ACUCAR',
        'CHOC' => 'CHOCOLATE',
        'BISC' => 'BISCOITO',
        'REFRI' => 'REFRIGERANTE',
        'DET' => 'DETERGENTE',
        'SAB' => 'SABONETE',
        'MARG' => 'MARGARINA',
        'COND' => 'CONDICIONADOR',
        'SHAMP' => 'SHAMPOO',
        'PCT' => 'PACOTE',
        'CX' => 'CAIXA',
        'UN' => 'UNIDADE',
    ];

    /**
     * @return array{normalized:string,original:string}
     */
    public function normalizeWithOriginal(?string $name): array
    {
        $original = $this->sanitizeOriginal($name);

        return [
            'normalized' => $this->normalize($original),
            'original' => $original,
        ];
    }

    public function normalize(?string $name): string
    {
        $value = $this->sanitizeOriginal($name);
        $ascii = Str::upper(Str::ascii($value));
        $ascii = preg_replace('/[^A-Z0-9]+/u', ' ', $ascii) ?? $ascii;
        $ascii = preg_replace('/\s+/', ' ', $ascii) ?? $ascii;
        $ascii = trim($ascii);

        if ($ascii === '') {
            return 'PRODUTO SEM NOME';
        }

        $tokens = explode(' ', $ascii);
        $expanded = array_map(function (string $token): string {
            $token = trim($token);
            if ($token === '') {
                return $token;
            }

            return $this->dictionary[$token] ?? $token;
        }, $tokens);

        return trim(implode(' ', array_filter($expanded, fn (string $token): bool => $token !== '')));
    }

    private function sanitizeOriginal(?string $name): string
    {
        $value = trim((string) $name);
        $value = preg_replace('/\s+/', ' ', $value) ?? $value;

        return trim($value);
    }
}
