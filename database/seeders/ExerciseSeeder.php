<?php

namespace Database\Seeders;

use App\Models\Exercise;
use Illuminate\Database\Seeder;

class ExerciseSeeder extends Seeder
{
    /**
     * O conteudo obedece a tres restricoes vindas da revisao de literatura.
     *
     * 1. Linguagem simples melhora a traducao automatica por avatar [R26]:
     *    uma ideia por frase, no maximo 12 palavras, ordem direta, e sempre o
     *    mesmo termo para o mesmo conceito.
     *
     * 2. O gargalo real para o aluno surdo e o vocabulario tecnico, que muitas
     *    vezes nao tem sinal estabelecido [R19] [R20] [R25]. Sem tratamento, o
     *    VLibras cai na datilologia e soletra INPUT letra por letra. Por isso
     *    as frases com termo em ingles ganham uma parafrase em "sinais": o
     *    aluno le o termo, que ele precisa aprender, e o avatar sinaliza a
     *    ideia. O termo aparece tambem no glossario, com explicacao propria.
     *
     * 3. O termo e apresentado antes na linguagem comum e so depois recebe o
     *    nome tecnico, porque o aluno chega sem nenhuma base de programacao.
     */
    public function run(): void
    {
        $exercicios = [
            [
                'slug' => 'variaveis-soma',
                'titulo' => 'Somar dois numeros',
                'conceito' => 'variaveis',
                'ordem' => 1,
                'conteudo' => implode("\n", [
                    'Um programa e uma lista de ordens para o computador.',
                    'O computador faz uma ordem por vez.',
                    'Ele comeca na primeira linha e desce.',

                    'O computador precisa guardar valores para usar depois.',
                    'Ele guarda cada valor em uma caixa.',
                    'Essa caixa se chama variavel.',
                    'Cada variavel tem um nome.',
                    'Voce escolhe o nome da variavel.',

                    'O sinal de igual guarda um valor na variavel.',
                    'Primeiro o nome, depois o igual, depois o valor.',
                    'Voce pode usar esse nome nas linhas de baixo.',

                    'O programa tambem precisa receber dados da pessoa.',
                    'A ordem input espera a pessoa digitar.',
                    'A pessoa digita e aperta Enter.',
                    'A ordem devolve o que a pessoa digitou.',

                    'Mas ela sempre devolve texto.',
                    'Texto nao serve para fazer conta.',
                    'A ordem int transforma esse texto em numero.',
                    'Agora voce tem um numero de verdade.',

                    'O sinal de mais soma dois numeros.',
                    'A ordem print mostra um valor na tela.',
                    'Sem essa ordem a pessoa nao ve o resultado.',
                ]),
                'sinais' => [
                    'A ordem input espera a pessoa digitar.' => 'Uma ordem especial espera a pessoa digitar.',
                    'A ordem int transforma esse texto em numero.' => 'Outra ordem transforma esse texto em numero.',
                    'A ordem print mostra um valor na tela.' => 'Uma terceira ordem mostra um valor na tela.',
                    'A pessoa digita e aperta Enter.' => 'A pessoa digita e aperta a tecla grande.',
                ],
                'glossario' => [
                    ['termo' => 'input', 'explicacao' => 'Espera a pessoa digitar alguma coisa.'],
                    ['termo' => 'int', 'explicacao' => 'Transforma texto em numero.'],
                    ['termo' => 'print', 'explicacao' => 'Mostra um valor na tela.'],
                    ['termo' => '=', 'explicacao' => 'Guarda um valor dentro da variavel.'],
                ],
                'exemplo' => implode("\n", [
                    '# espera a pessoa digitar e transforma em numero',
                    'idade = int(input())',
                    '',
                    '# faz a conta e guarda em outra caixa',
                    'proximo = idade + 1',
                    '',
                    '# mostra o resultado na tela',
                    'print(proximo)',
                ]),
                'enunciado' => implode("\n", [
                    'Escreva um programa que soma dois numeros.',
                    'Leia o primeiro numero e guarde na caixa a.',
                    'Leia o segundo numero e guarde na caixa b.',
                    'Some as duas caixas.',
                    'Mostre o resultado da soma na tela.',
                    'Mostre apenas o numero, mais nada.',
                ]),
                'exemplo_execucao' => "A pessoa digita:\n2\n3\n\nO programa mostra:\n5",
                'codigo_inicial' => "a = int(input())\nb = int(input())\n\n# some a e b\n# depois mostre o resultado na tela\n",
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
                'conteudo' => implode("\n", [
                    'As vezes o programa precisa escolher um caminho.',
                    'A escolha depende de uma pergunta.',
                    'A pergunta so aceita duas respostas.',
                    'A resposta e verdadeiro ou falso.',
                    'Essa pergunta se chama condicao.',

                    'A ordem if testa a condicao.',
                    'Se a resposta for verdadeiro, o programa faz uma coisa.',
                    'Se a resposta for falso, o programa faz outra coisa.',
                    'A ordem else guarda essa outra coisa.',

                    'Depois da condicao voce escreve dois pontos.',
                    'A linha de baixo comeca com quatro espacos.',
                    'Esse espaco no comeco se chama indentacao.',
                    'A indentacao mostra o que esta dentro da escolha.',
                    'O programa mostra erro se a indentacao faltar.',

                    'Agora veja como descobrir se um numero e par.',
                    'Divida o numero por dois.',
                    'Olhe o que sobra da divisao.',
                    'O que sobra se chama resto.',
                    'O numero quatro dividido por dois tem resto zero.',
                    'O numero sete dividido por dois tem resto um.',
                    'Um numero par sempre tem resto zero.',

                    'Um sinal de igual guarda um valor.',
                    'Dois sinais de igual comparam dois valores.',
                    'Use dois sinais de igual dentro da condicao.',
                ]),
                'sinais' => [
                    'A ordem if testa a condicao.' => 'Uma ordem especial testa a condicao.',
                    'A ordem else guarda essa outra coisa.' => 'Outra ordem guarda essa outra coisa.',
                ],
                'glossario' => [
                    ['termo' => 'if', 'explicacao' => 'Testa a condicao e escolhe o caminho.'],
                    ['termo' => 'else', 'explicacao' => 'Guarda o outro caminho da escolha.'],
                    ['termo' => '%', 'explicacao' => 'Mostra o que sobra da divisao.'],
                    ['termo' => '==', 'explicacao' => 'Compara dois valores e responde verdadeiro ou falso.'],
                ],
                'exemplo' => implode("\n", [
                    'n = int(input())',
                    '',
                    '# a condicao vem antes dos dois pontos',
                    'if n > 10:',
                    '    # esta linha tem quatro espacos, entao esta dentro da escolha',
                    "    print('grande')",
                    'else:',
                    "    print('pequeno')",
                ]),
                'enunciado' => implode("\n", [
                    'Escreva um programa que descobre se um numero e par.',
                    'Leia um numero e guarde na caixa n.',
                    'Veja o que sobra da divisao por dois.',
                    'Se o numero for par, mostre a palavra par.',
                    'Se o numero for impar, mostre a palavra impar.',
                    'Escreva a palavra com letras minusculas.',
                    'Mostre apenas a palavra, mais nada.',
                ]),
                'exemplo_execucao' => "A pessoa digita:\n4\n\nO programa mostra:\npar\n\n---\n\nA pessoa digita:\n7\n\nO programa mostra:\nimpar",
                'codigo_inicial' => "n = int(input())\n\n# veja o que sobra da divisao por dois\n# depois escolha entre par e impar\n",
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
                'conteudo' => implode("\n", [
                    'As vezes o programa repete a mesma acao.',
                    'Escrever a mesma linha muitas vezes e ruim.',
                    'Voce perde tempo e erra mais.',
                    'O laco repete a acao para voce.',

                    'A ordem for cria um laco.',
                    'O laco precisa de uma lista de numeros.',
                    'A ordem range cria essa lista.',
                    'Ela recebe dois numeros.',
                    'O primeiro numero e onde a lista comeca.',
                    'A lista para antes do segundo numero.',
                    'O segundo numero nao entra na lista.',

                    'Veja um exemplo com os numeros um e quatro.',
                    'A lista fica com um, dois e tres.',
                    'O numero quatro fica de fora.',
                    'Para incluir o ultimo numero, some um nele.',
                    'Muitos alunos esquecem esse detalhe.',
                    'Por isso o ultimo numero some do resultado.',

                    'O laco tem uma caixa de controle.',
                    'Essa caixa guarda um numero por vez.',
                    'Na primeira volta ela recebe o primeiro numero.',
                    'Na volta seguinte ela recebe o proximo numero.',

                    'Depois da lista voce escreve dois pontos.',
                    'A linha de baixo comeca com quatro espacos.',
                    'Essa linha se repete a cada volta.',
                ]),
                'sinais' => [
                    'A ordem for cria um laco.' => 'Uma ordem especial cria um laco.',
                    'A ordem range cria essa lista.' => 'Outra ordem cria essa lista.',
                ],
                'glossario' => [
                    ['termo' => 'for', 'explicacao' => 'Repete a mesma acao varias vezes.'],
                    ['termo' => 'range', 'explicacao' => 'Cria uma lista de numeros em ordem.'],
                    ['termo' => 'range(1, 4)', 'explicacao' => 'Cria a lista com um, dois e tres.'],
                ],
                'exemplo' => implode("\n", [
                    '# a lista vai de 1 ate 3, porque o 4 fica de fora',
                    'for i in range(1, 4):',
                    '    # esta linha repete tres vezes',
                    '    print(i)',
                    '',
                    '# a tela mostra 1, depois 2, depois 3',
                ]),
                'enunciado' => implode("\n", [
                    'Escreva um programa que conta de 1 ate n.',
                    'Leia um numero e guarde na caixa n.',
                    'Use um laco para repetir a contagem.',
                    'Mostre todos os numeros de 1 ate n.',
                    'Mostre um numero por linha.',
                    'O proprio numero n tambem deve aparecer.',
                ]),
                'exemplo_execucao' => "A pessoa digita:\n3\n\nO programa mostra:\n1\n2\n3",
                'codigo_inicial' => "n = int(input())\n\n# repita a contagem de 1 ate n\n# lembre que o segundo numero da lista fica de fora\n",
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
