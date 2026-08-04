<?php

namespace Database\Seeders;

use App\Models\Exercise;
use Illuminate\Database\Seeder;

class ExerciseSeeder extends Seeder
{
    /**
     * Os enunciados seguem a mesma restricao de tradutibilidade aplicada ao feedback:
     * frases curtas, uma ideia por linha, vocabulario constante.
     */
    public function run(): void
    {
        $exercicios = [
            [
                'slug' => 'variaveis-soma',
                'titulo' => 'Somar dois numeros',
                'conceito' => 'variaveis',
                'ordem' => 1,
                'enunciado' => "Leia dois numeros inteiros.\nSome os dois numeros.\nMostre o resultado da soma.",
                'codigo_inicial' => "a = int(input())\nb = int(input())\n\n# escreva sua resposta aqui\n",
                'casos_teste' => [
                    ['entrada' => "2\n3\n", 'saida' => "5\n"],
                    ['entrada' => "10\n7\n", 'saida' => "17\n"],
                    ['entrada' => "0\n0\n", 'saida' => "0\n"],
                ],
            ],
            [
                'slug' => 'condicional-par-impar',
                'titulo' => 'Par ou impar',
                'conceito' => 'condicionais',
                'ordem' => 2,
                'enunciado' => "Leia um numero inteiro.\nMostre a palavra par se o numero for par.\nMostre a palavra impar se o numero for impar.",
                'codigo_inicial' => "n = int(input())\n\n# escreva seu if aqui\n",
                'casos_teste' => [
                    ['entrada' => "4\n", 'saida' => "par\n"],
                    ['entrada' => "7\n", 'saida' => "impar\n"],
                    ['entrada' => "0\n", 'saida' => "par\n"],
                ],
            ],
            [
                'slug' => 'laco-contagem',
                'titulo' => 'Contar de 1 ate N',
                'conceito' => 'lacos',
                'ordem' => 3,
                'enunciado' => "Leia um numero N.\nMostre todos os numeros de 1 ate N.\nMostre um numero por linha.",
                'codigo_inicial' => "n = int(input())\n\n# escreva seu laco aqui\n",
                'casos_teste' => [
                    ['entrada' => "3\n", 'saida' => "1\n2\n3\n"],
                    ['entrada' => "1\n", 'saida' => "1\n"],
                    ['entrada' => "5\n", 'saida' => "1\n2\n3\n4\n5\n"],
                ],
            ],
        ];

        foreach ($exercicios as $dados) {
            Exercise::updateOrCreate(['slug' => $dados['slug']], $dados);
        }
    }
}
