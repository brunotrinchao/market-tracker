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
        // Alimentos e mercearia
        'ACUC' => 'ACUCAR',
        'ACUCR' => 'ACUCAR',
        'ACUCAR' => 'ACUCAR',
        'ACHOC' => 'ACHOCOLATADO',
        'ACHOCOL' => 'ACHOCOLATADO',
        'ADOC' => 'ADOCANTE',
        'ADOCA' => 'ADOCANTE',
        'ADOCANTE' => 'ADOCANTE',
        'CHOC' => 'CHOCOLATE',
        'CHOCOL' => 'CHOCOLATE',
        'BISC' => 'BISCOITO',
        'BISCO' => 'BISCOITO',
        'BOLAC' => 'BOLACHA',
        'REFR' => 'REFRIGERANTE',
        'REFRI' => 'REFRIGERANTE',
        'REFRIG' => 'REFRIGERANTE',
        'SUC' => 'SUCO',
        'SUCO' => 'SUCO',
        'NECT' => 'NECTAR',
        'AG' => 'AGUA',
        'AGUA' => 'AGUA',
        'CERV' => 'CERVEJA',
        'CERVEJ' => 'CERVEJA',
        'VIN' => 'VINHO',
        'VINHO' => 'VINHO',
        'ENERG' => 'ENERGETICO',
        'ISOT' => 'ISOTONICO',
        'LEIT' => 'LEITE',
        'LEITE' => 'LEITE',
        'MANTEIG' => 'MANTEIGA',
        'MARG' => 'MARGARINA',
        'IOG' => 'IOGURTE',
        'QUEIJ' => 'QUEIJO',
        'QUEIJO' => 'QUEIJO',
        'REQUEI' => 'REQUEIJAO',
        'PRES' => 'PRESUNTO',
        'PRESUNTO' => 'PRESUNTO',
        'MORT' => 'MORTADELA',
        'MORTADELA' => 'MORTADELA',
        'SALS' => 'SALSICHA',
        'FRANG' => 'FRANGO',
        'FRANGO' => 'FRANGO',
        'CAR' => 'CARNE',
        'CARNE' => 'CARNE',
        'BOV' => 'BOVINO',
        'SUIN' => 'SUINO',
        'PEIX' => 'PEIXE',
        'PEIXE' => 'PEIXE',
        'OVOS' => 'OVO',
        'OVO' => 'OVO',
        'ARROZ' => 'ARROZ',
        'FEIJAO' => 'FEIJAO',
        'MACAR' => 'MACARRAO',
        'MASSA' => 'MACARRAO',
        'FAR' => 'FARINHA',
        'FARINH' => 'FARINHA',
        'TRIG' => 'TRIGO',
        'MILH' => 'MILHO',
        'AMID' => 'AMIDO',
        'OLEO' => 'OLEO',
        'AZEIT' => 'AZEITE',
        'SAL' => 'SAL',
        'TEMP' => 'TEMPERO',
        'MOLH' => 'MOLHO',
        'EXTR' => 'EXTRATO',
        'TOM' => 'TOMATE',
        'MAION' => 'MAIONESE',
        'KETCH' => 'KETCHUP',
        'MOST' => 'MOSTARDA',
        'PAO' => 'PAO',
        'TORR' => 'TORRADA',
        'BOLO' => 'BOLO',
        'PIP' => 'PIPOCA',
        'CAF' => 'CAFE',
        'CAFE' => 'CAFE',
        'CHA' => 'CHA',
        'BOMB' => 'BOMBOM',
        'BAL' => 'BALA',
        'GOMA' => 'GOMA',
        'BCOS' => 'BRANCO',
        'SEMEN' => 'SEMENTE',
        'COEN' => 'COENTRO',
        'MOL' => 'MOLHO',

        // Limpeza e casa
        'DET' => 'DETERGENTE',
        'DETERG' => 'DETERGENTE',
        'DESINF' => 'DESINFETANTE',
        'SABAO' => 'SABAO',
        'SABPO' => 'SABAO PO',
        'SABLIQ' => 'SABAO LIQUIDO',
        'ALV' => 'ALVEJANTE',
        'AGSAN' => 'AGUA SANITARIA',
        'LIMP' => 'LIMPADOR',
        'MULTIUSO' => 'MULTIUSO',
        'ESPONJ' => 'ESPONJA',
        'PALH' => 'PALHA ACO',
        'PANO' => 'PANO',
        'PAPEL' => 'PAPEL',
        'PAPELHIG' => 'PAPEL HIGIENICO',
        'GUARD' => 'GUARDANAPO',
        'TOALH' => 'TOALHA',
        'AMAC' => 'AMACIANTE',
        'CLORO' => 'CLORO',

        // Higiene e perfumaria
        'SAB' => 'SABONETE',
        'SABON' => 'SABONETE',
        'SHAMP' => 'SHAMPOO',
        'SHAM' => 'SHAMPOO',
        'COND' => 'CONDICIONADOR',
        'DENT' => 'DENTAL',
        'CREMED' => 'CREME DENTAL',
        'DESOD' => 'DESODORANTE',
        'APAR' => 'APARELHO',
        'LAM' => 'LAMINA',
        'ABS' => 'ABSORVENTE',
        'FRALD' => 'FRALDA',
        'LENCO' => 'LENCO',
        'UMED' => 'UMEDECIDO',
        'HIDR' => 'HIDRATANTE',
        'PERF' => 'PERFUME',
        'GEL' => 'GEL',
        'CREME' => 'CREME',

        // Embalagem e unidade
        'PCT' => 'PACOTE',
        'PAC' => 'PACOTE',
        'CX' => 'CAIXA',
        'UN' => 'UNIDADE',
        'UND' => 'UNIDADE',
        'UNID' => 'UNIDADE',
        'KG' => 'QUILO',
        'G' => 'GRAMA',
        'GR' => 'GRAMA',
        'MG' => 'MIGRAMA',
        'ML' => 'MILILITRO',
        'LIT' => 'LITRO',
        'LT' => 'LITRO',
        'FD' => 'FARDO',
        'FDO' => 'FARDO',
        'BDJ' => 'BANDEJA',
        'POTE' => 'POTE',
        'LTN' => 'LATA',
        'LATA' => 'LATA',
        'GFA' => 'GARRAFA',
        'GF' => 'GARRAFA',
        'SACH' => 'SACHE',
        'FR' => 'FRASCO',

        // Genéricos
        'BEBIDA' => 'BEBIDA',
        'MIC' => 'MICROONDAS',
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
