<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Mercearia',
                'keywords' => ['arroz', 'feijao', 'acucar', 'sal', 'farinha', 'macarrao', 'molho', 'oleo', 'azeite', 'cafe', 'biscoito', 'bolacha', 'grao', 'milho', 'ervilha'],
            ],
            [
                'name' => 'Hortifruti',
                'keywords' => ['banana', 'maca', 'laranja', 'uva', 'mamao', 'abacaxi', 'tomate', 'batata', 'cebola', 'alface', 'couve', 'cenoura', 'pepino', 'hortifruti'],
            ],
            [
                'name' => 'Laticinios',
                'keywords' => ['leite', 'iogurte', 'queijo', 'manteiga', 'requeijao', 'creme de leite', 'leite condensado', 'lacticinio'],
            ],
            [
                'name' => 'Carnes e Aves',
                'keywords' => ['carne', 'frango', 'bovina', 'suina', 'linguica', 'peito', 'coxa', 'file', 'hamburguer', 'bacon', 'ave'],
            ],
            [
                'name' => 'Padaria',
                'keywords' => ['pao', 'pao de forma', 'torrada', 'bolo', 'rosca', 'broa', 'padaria'],
            ],
            [
                'name' => 'Bebidas',
                'keywords' => ['agua', 'refrigerante', 'suco', 'cerveja', 'vinho', 'energetico', 'cha', 'bebida', 'isotonico', 'coco'],
            ],
            [
                'name' => 'Limpeza',
                'keywords' => ['detergente', 'sabao', 'amaciante', 'desinfetante', 'agua sanitaria', 'multiuso', 'limpeza', 'esponja', 'alvejante', 'limpador'],
            ],
            [
                'name' => 'Higiene Pessoal',
                'keywords' => ['sabonete', 'shampoo', 'condicionador', 'creme dental', 'escova', 'desodorante', 'papel higienico', 'higiene', 'absorvente'],
            ],
            [
                'name' => 'Congelados',
                'keywords' => ['congelado', 'pizza', 'lasanha', 'nuggets', 'sorvete', 'polpa', 'empanado'],
            ],
            [
                'name' => 'Pet Shop',
                'keywords' => ['racao', 'petisco', 'areia', 'gato', 'cachorro', 'pet'],
            ],
        ];

        foreach ($categories as $data) {
            Category::query()->updateOrCreate(
                ['slug' => Str::slug($data['name'])],
                [
                    'name' => $data['name'],
                    'keywords' => $data['keywords'],
                ]
            );
        }
    }
}

