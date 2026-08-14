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
     *
     * A trilha vai do primeiro programa ate funcao, em nove passos. Cada
     * exercicio so estreia um conceito e reusa os anteriores, para o erro
     * novo ser sempre atribuivel ao conceito novo. As saidas esperadas ficam
     * sem acento de proposito: o aluno digita o texto, e cobrar acento seria
     * cobrar ortografia em vez de logica.
     */
    public function run(): void
    {
        $exercicios = [
            [
                'slug' => 'variaveis-soma',
                'titulo' => 'Somar dois números',
                'conceito' => 'variáveis',
                'ordem' => 1,
                'conteudo' => implode("\n", [
                    'Um programa é uma lista de ordens para o computador.',
                    'O computador faz uma ordem por vez.',
                    'Ele começa na primeira linha e desce.',

                    'O computador precisa guardar valores para usar depois.',
                    'Ele guarda cada valor em uma caixa.',
                    'Essa caixa se chama variável.',
                    'Cada variável tem um nome.',
                    'Você escolhe o nome da variável.',

                    'O sinal de igual guarda um valor na variável.',
                    'Primeiro o nome, depois o igual, depois o valor.',
                    'Você pode usar esse nome nas linhas de baixo.',

                    'O programa também precisa receber dados da pessoa.',
                    'A ordem input espera a pessoa digitar.',
                    'A pessoa digita e aperta Enter.',
                    'A ordem devolve o que a pessoa digitou.',

                    'Mas ela sempre devolve texto.',
                    'Texto não serve para fazer conta.',
                    'A ordem int transforma esse texto em número.',
                    'Agora você tem um número de verdade.',

                    'O sinal de mais soma dois números.',
                    'A ordem print mostra um valor na tela.',
                    'Sem essa ordem a pessoa não vê o resultado.',
                ]),
                'sinais' => [
                    'A ordem input espera a pessoa digitar.' => 'Uma ordem especial espera a pessoa digitar.',
                    'A ordem int transforma esse texto em número.' => 'Outra ordem transforma esse texto em número.',
                    'A ordem print mostra um valor na tela.' => 'Uma terceira ordem mostra um valor na tela.',
                    'A pessoa digita e aperta Enter.' => 'A pessoa digita e aperta a tecla grande.',
                ],
                'glossario' => [
                    ['termo' => 'input', 'explicacao' => 'Espera a pessoa digitar alguma coisa.'],
                    ['termo' => 'int', 'explicacao' => 'Transforma texto em número.'],
                    ['termo' => 'print', 'explicacao' => 'Mostra um valor na tela.'],
                    ['termo' => '=', 'explicacao' => 'Guarda um valor dentro da variável.'],
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
                    'Escreva um programa que soma dois números.',
                    'Leia o primeiro número e guarde na caixa a.',
                    'Leia o segundo número e guarde na caixa b.',
                    'Some as duas caixas.',
                    'Mostre o resultado da soma na tela.',
                    'Mostre apenas o número, mais nada.',
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
                'titulo' => 'Par ou ímpar',
                'conceito' => 'condicionais',
                'ordem' => 2,
                'conteudo' => implode("\n", [
                    'Às vezes o programa precisa escolher um caminho.',
                    'A escolha depende de uma pergunta.',
                    'A pergunta só aceita duas respostas.',
                    'A resposta é verdadeiro ou falso.',
                    'Essa pergunta se chama condição.',

                    'A ordem if testa a condição.',
                    'Se a resposta for verdadeiro, o programa faz uma coisa.',
                    'Se a resposta for falso, o programa faz outra coisa.',
                    'A ordem else guarda essa outra coisa.',

                    'Depois da condição você escreve dois pontos.',
                    'A linha de baixo começa com quatro espaços.',
                    'Esse espaço no começo se chama indentação.',
                    'A indentação mostra o que está dentro da escolha.',
                    'O programa mostra erro se a indentação faltar.',

                    'Agora veja como descobrir se um número é par.',
                    'Divida o número por dois.',
                    'Olhe o que sobra da divisão.',
                    'O que sobra se chama resto.',
                    'O número quatro dividido por dois tem resto zero.',
                    'O número sete dividido por dois tem resto um.',
                    'Um número par sempre tem resto zero.',

                    'Um sinal de igual guarda um valor.',
                    'Dois sinais de igual comparam dois valores.',
                    'Use dois sinais de igual dentro da condição.',
                ]),
                'sinais' => [
                    'A ordem if testa a condição.' => 'Uma ordem especial testa a condição.',
                    'A ordem else guarda essa outra coisa.' => 'Outra ordem guarda essa outra coisa.',
                ],
                'glossario' => [
                    ['termo' => 'if', 'explicacao' => 'Testa a condição e escolhe o caminho.'],
                    ['termo' => 'else', 'explicacao' => 'Guarda o outro caminho da escolha.'],
                    ['termo' => '%', 'explicacao' => 'Mostra o que sobra da divisão.'],
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
                    'Escreva um programa que descobre se um número é par.',
                    'Leia um número e guarde na caixa n.',
                    'Veja o que sobra da divisão por dois.',
                    'Se o número for par, mostre a palavra par.',
                    'Se o número for ímpar, mostre a palavra impar.',
                    'Escreva a palavra com letras minúsculas e sem acento.',
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
                'titulo' => 'Contar de 1 até N',
                'conceito' => 'laços',
                'ordem' => 3,
                'conteudo' => implode("\n", [
                    'Às vezes o programa repete a mesma ação.',
                    'Escrever a mesma linha muitas vezes é ruim.',
                    'Você perde tempo e erra mais.',
                    'O laço repete a ação para você.',

                    'A ordem for cria um laço.',
                    'O laço precisa de uma lista de números.',
                    'A ordem range cria essa lista.',
                    'Ela recebe dois números.',
                    'O primeiro número é onde a lista começa.',
                    'A lista para antes do segundo número.',
                    'O segundo número não entra na lista.',

                    'Veja um exemplo com os números um e quatro.',
                    'A lista fica com um, dois e três.',
                    'O número quatro fica de fora.',
                    'Para incluir o último número, some um nele.',
                    'Muitos alunos esquecem esse detalhe.',
                    'Por isso o último número some do resultado.',

                    'O laço tem uma caixa de controle.',
                    'Essa caixa guarda um número por vez.',
                    'Na primeira volta ela recebe o primeiro número.',
                    'Na volta seguinte ela recebe o próximo número.',

                    'Depois da lista você escreve dois pontos.',
                    'A linha de baixo começa com quatro espaços.',
                    'Essa linha se repete a cada volta.',
                ]),
                'sinais' => [
                    'A ordem for cria um laço.' => 'Uma ordem especial cria um laço.',
                    'A ordem range cria essa lista.' => 'Outra ordem cria essa lista.',
                ],
                'glossario' => [
                    ['termo' => 'for', 'explicacao' => 'Repete a mesma ação várias vezes.'],
                    ['termo' => 'range', 'explicacao' => 'Cria uma lista de números em ordem.'],
                    ['termo' => 'range(1, 4)', 'explicacao' => 'Cria a lista com um, dois e três.'],
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
                    'Escreva um programa que conta de 1 até n.',
                    'Leia um número e guarde na caixa n.',
                    'Use um laço para repetir a contagem.',
                    'Mostre todos os números de 1 até n.',
                    'Mostre um número por linha.',
                    'O próprio número n também deve aparecer.',
                ]),
                'exemplo_execucao' => "A pessoa digita:\n3\n\nO programa mostra:\n1\n2\n3",
                'codigo_inicial' => "n = int(input())\n\n# repita a contagem de 1 ate n\n# lembre que o segundo numero da lista fica de fora\n",
                'casos_teste' => [
                    ['entrada' => "3\n", 'saida' => "1\n2\n3\n"],
                    ['entrada' => "1\n", 'saida' => "1\n"],
                    ['entrada' => "5\n", 'saida' => "1\n2\n3\n4\n5\n"],
                ],
            ],
            [
                'slug' => 'condicional-maior',
                'titulo' => 'O maior de três números',
                'conceito' => 'condicionais',
                'ordem' => 4,
                'conteudo' => implode("\n", [
                    'Você já sabe escolher entre dois caminhos.',
                    'Agora o programa precisa de três caminhos.',
                    'A ordem elif cria um caminho no meio.',
                    'Ela fica entre o if e o else.',

                    'O programa testa uma condição por vez.',
                    'Ele começa pela condição do if.',
                    'Se a resposta for falso, ele desce para o elif.',
                    'Se todas forem falso, ele usa o else.',

                    'O programa para na primeira condição verdadeira.',
                    'As condições de baixo não são testadas.',
                    'Por isso a ordem das condições muda o resultado.',

                    'Você pode ter vários elif no mesmo grupo.',
                    'Mas só um if no começo.',
                    'E só um else no fim.',

                    'Agora veja como comparar números.',
                    'O sinal de maior compara dois valores.',
                    'A resposta é verdadeiro ou falso.',
                    'Para comparar três valores, compare dois por vez.',
                    'Primeiro descubra o maior entre os dois primeiros.',
                    'Depois compare esse maior com o terceiro.',
                ]),
                'sinais' => [
                    'A ordem elif cria um caminho no meio.' => 'Uma ordem especial cria um caminho no meio.',
                    'Ela fica entre o if e o else.' => 'Ela fica entre as duas outras ordens.',
                    'Você pode ter vários elif no mesmo grupo.' => 'Você pode ter vários caminhos do meio no mesmo grupo.',
                    'Mas só um if no começo.' => 'Mas só uma primeira ordem no começo.',
                    'E só um else no fim.' => 'E só uma última ordem no fim.',
                ],
                'glossario' => [
                    ['termo' => 'elif', 'explicacao' => 'Testa outra condição quando a primeira dá falso.'],
                    ['termo' => '>', 'explicacao' => 'Responde verdadeiro quando o primeiro valor é maior.'],
                    ['termo' => '>=', 'explicacao' => 'Responde verdadeiro quando é maior ou igual.'],
                ],
                'exemplo' => implode("\n", [
                    'nota = int(input())',
                    '',
                    '# o programa testa uma condicao por vez, de cima para baixo',
                    'if nota >= 90:',
                    "    print('otimo')",
                    'elif nota >= 60:',
                    "    print('bom')",
                    'else:',
                    "    print('ruim')",
                    '',
                    '# com nota 95 a tela mostra otimo, e o resto nao e testado',
                ]),
                'enunciado' => implode("\n", [
                    'Escreva um programa que acha o maior de três números.',
                    'Leia três números e guarde nas caixas a, b e c.',
                    'Compare os três números.',
                    'Mostre apenas o maior deles.',
                    'Se dois forem iguais e maiores, mostre esse valor.',
                    'Mostre apenas o número, mais nada.',
                ]),
                'exemplo_execucao' => "A pessoa digita:\n3\n9\n5\n\nO programa mostra:\n9",
                'codigo_inicial' => "a = int(input())\nb = int(input())\nc = int(input())\n\n# compare os tres numeros\n# depois mostre o maior deles\n",
                'casos_teste' => [
                    ['entrada' => "3\n9\n5\n", 'saida' => "9\n"],
                    ['entrada' => "10\n2\n4\n", 'saida' => "10\n"],
                    ['entrada' => "1\n1\n7\n", 'saida' => "7\n"],
                ],
            ],
            [
                'slug' => 'laco-while-regressiva',
                'titulo' => 'Contagem regressiva',
                'conceito' => 'laços',
                'ordem' => 5,
                'conteudo' => implode("\n", [
                    'Existe um segundo tipo de laço.',
                    'A ordem while repete enquanto a condição for verdadeira.',
                    'Ela não precisa de lista de números.',
                    'Ela precisa de uma condição.',

                    'O programa testa a condição antes de cada volta.',
                    'Se a resposta for verdadeiro, ele faz a volta.',
                    'Se a resposta for falso, ele sai do laço.',

                    'Aqui mora o erro mais comum.',
                    'Alguma coisa dentro do laço precisa mudar.',
                    'A condição precisa virar falso em algum momento.',
                    'Se nada mudar, o laço nunca termina.',
                    'Esse problema se chama laço infinito.',
                    'O programa trava e não mostra o resultado.',

                    'Veja um exemplo do erro.',
                    'A caixa i vale um e a condição pergunta se i é menor que cinco.',
                    'Se você nunca somar um em i, a resposta é sempre verdadeiro.',
                    'O laço repete para sempre.',

                    'A correção é simples.',
                    'Mude a caixa de controle dentro do laço.',
                    'Some um ou tire um a cada volta.',
                    'Assim a condição vira falso e o laço termina.',
                ]),
                'sinais' => [
                    'A ordem while repete enquanto a condição for verdadeira.' => 'Uma ordem especial repete enquanto a condição for verdadeira.',
                ],
                'glossario' => [
                    ['termo' => 'while', 'explicacao' => 'Repete enquanto a condição for verdadeira.'],
                    ['termo' => 'i = i - 1', 'explicacao' => 'Tira um da caixa e guarda o novo valor.'],
                    ['termo' => '>=', 'explicacao' => 'Responde verdadeiro quando é maior ou igual.'],
                ],
                'exemplo' => implode("\n", [
                    'i = 1',
                    '',
                    '# a condicao e testada antes de cada volta',
                    'while i <= 3:',
                    '    print(i)',
                    '    # esta linha muda a caixa, entao o laco termina',
                    '    i = i + 1',
                    '',
                    '# sem a ultima linha o programa nunca para',
                ]),
                'enunciado' => implode("\n", [
                    'Escreva um programa que conta de n até 1.',
                    'Leia um número e guarde na caixa n.',
                    'Use a ordem while para repetir.',
                    'Mostre os números do maior para o menor.',
                    'Mostre um número por linha.',
                    'O número 1 também deve aparecer.',
                    'Lembre de mudar a caixa dentro do laço.',
                ]),
                'exemplo_execucao' => "A pessoa digita:\n3\n\nO programa mostra:\n3\n2\n1",
                'codigo_inicial' => "n = int(input())\n\n# repita enquanto n for maior ou igual a 1\n# lembre de tirar um de n dentro do laco\n",
                'casos_teste' => [
                    ['entrada' => "3\n", 'saida' => "3\n2\n1\n"],
                    ['entrada' => "1\n", 'saida' => "1\n"],
                    ['entrada' => "5\n", 'saida' => "5\n4\n3\n2\n1\n"],
                ],
            ],
            [
                'slug' => 'laco-acumulador',
                'titulo' => 'Somar vários números',
                'conceito' => 'acumulador',
                'ordem' => 6,
                'conteudo' => implode("\n", [
                    'Agora o programa precisa somar muitos números.',
                    'Você não sabe quantos números virão.',
                    'A pessoa diz a quantidade antes.',

                    'Você precisa de uma caixa para guardar o total.',
                    'Essa caixa se chama acumulador.',
                    'Ela guarda a soma parcial.',

                    'O acumulador começa com zero.',
                    'Zero é o valor que não muda a soma.',
                    'Criar a caixa antes do laço é obrigatório.',
                    'Se você criar dentro do laço, ela volta a zero toda volta.',

                    'A cada volta você soma um número no total.',
                    'O novo total é o total antigo mais o número.',
                    'Você guarda esse novo total na mesma caixa.',

                    'O laço lê um número por volta.',
                    'A ordem input entra dentro do laço.',
                    'Assim ela espera a pessoa digitar a cada volta.',

                    'A ordem print fica fora do laço.',
                    'Ela vem depois, sem os quatro espaços.',
                    'Assim a tela mostra só o total final.',
                    'Se ela ficar dentro, a tela mostra um total por volta.',
                ]),
                'sinais' => [
                    'A ordem input entra dentro do laço.' => 'A ordem que espera a pessoa digitar entra dentro do laço.',
                    'A ordem print fica fora do laço.' => 'A ordem que mostra na tela fica fora do laço.',
                ],
                'glossario' => [
                    ['termo' => 'acumulador', 'explicacao' => 'Caixa que guarda a soma parcial.'],
                    ['termo' => 'total = 0', 'explicacao' => 'Cria a caixa do total começando em zero.'],
                    ['termo' => 'total = total + x', 'explicacao' => 'Soma x no total e guarda de volta.'],
                ],
                'exemplo' => implode("\n", [
                    '# a caixa do total nasce antes do laco',
                    'total = 0',
                    '',
                    'for i in range(1, 4):',
                    '    # o total antigo mais o numero da vez',
                    '    total = total + i',
                    '',
                    '# esta linha esta fora do laco, entao mostra so o total final',
                    'print(total)',
                    '',
                    '# a tela mostra 6, que e 1 mais 2 mais 3',
                ]),
                'enunciado' => implode("\n", [
                    'Escreva um programa que soma vários números.',
                    'Leia a quantidade de números e guarde na caixa n.',
                    'Depois leia n números, um por linha.',
                    'Some todos os números lidos.',
                    'Mostre apenas o total no fim.',
                    'Mostre o total uma única vez.',
                ]),
                'exemplo_execucao' => "A pessoa digita:\n3\n10\n20\n30\n\nO programa mostra:\n60",
                'codigo_inicial' => "n = int(input())\n\n# crie a caixa do total antes do laco\n# leia um numero por volta e some no total\n# mostre o total depois do laco\n",
                'casos_teste' => [
                    ['entrada' => "3\n10\n20\n30\n", 'saida' => "60\n"],
                    ['entrada' => "1\n7\n", 'saida' => "7\n"],
                    ['entrada' => "4\n1\n2\n3\n4\n", 'saida' => "10\n"],
                ],
            ],
            [
                'slug' => 'lista-media',
                'titulo' => 'Média das notas',
                'conceito' => 'listas',
                'ordem' => 7,
                'conteudo' => implode("\n", [
                    'Uma variável guarda um valor por vez.',
                    'Às vezes você precisa guardar muitos valores.',
                    'A lista guarda vários valores em uma caixa só.',

                    'A lista vazia se escreve com dois colchetes.',
                    'A ordem append coloca um valor no fim da lista.',
                    'Cada valor novo entra depois do anterior.',

                    'A ordem len diz quantos valores a lista tem.',
                    'A ordem sum soma todos os valores da lista.',

                    'A média é a soma dividida pela quantidade.',
                    'Você já sabe somar com o acumulador.',
                    'Agora a lista faz esse trabalho para você.',

                    'Existem dois tipos de divisão.',
                    'Uma barra faz a divisão com vírgula.',
                    'Duas barras fazem a divisão sem vírgula.',
                    'A média de notas usa uma barra só.',

                    'A ordem round arredonda um número com vírgula.',
                    'Ela recebe o número e a quantidade de casas.',
                    'Duas casas bastam para uma média de notas.',
                ]),
                'sinais' => [
                    'A ordem append coloca um valor no fim da lista.' => 'Uma ordem especial coloca um valor no fim da lista.',
                    'A ordem len diz quantos valores a lista tem.' => 'Outra ordem diz quantos valores a lista tem.',
                    'A ordem sum soma todos os valores da lista.' => 'Uma terceira ordem soma todos os valores da lista.',
                    'A ordem round arredonda um número com vírgula.' => 'Uma quarta ordem arredonda o número com vírgula.',
                ],
                'glossario' => [
                    ['termo' => 'lista', 'explicacao' => 'Caixa que guarda vários valores em ordem.'],
                    ['termo' => 'append', 'explicacao' => 'Coloca um valor no fim da lista.'],
                    ['termo' => 'len', 'explicacao' => 'Diz quantos valores a lista tem.'],
                    ['termo' => 'sum', 'explicacao' => 'Soma todos os valores da lista.'],
                    ['termo' => 'round', 'explicacao' => 'Arredonda um número com vírgula.'],
                ],
                'exemplo' => implode("\n", [
                    '# a lista nasce vazia',
                    'notas = []',
                    '',
                    'for i in range(0, 3):',
                    '    nota = int(input())',
                    '    # cada nota entra no fim da lista',
                    '    notas.append(nota)',
                    '',
                    '# a soma dividida pela quantidade',
                    'media = sum(notas) / len(notas)',
                    'print(round(media, 2))',
                ]),
                'enunciado' => implode("\n", [
                    'Escreva um programa que calcula a média de notas.',
                    'Leia a quantidade de notas e guarde na caixa n.',
                    'Depois leia n notas, uma por linha.',
                    'Guarde as notas em uma lista.',
                    'Calcule a média das notas.',
                    'Arredonde a média com duas casas.',
                    'Mostre apenas a média, mais nada.',
                ]),
                'exemplo_execucao' => "A pessoa digita:\n3\n8\n6\n10\n\nO programa mostra:\n8.0",
                'codigo_inicial' => "n = int(input())\nnotas = []\n\n# leia n notas e coloque cada uma na lista\n# depois calcule a media e arredonde com duas casas\n",
                'casos_teste' => [
                    ['entrada' => "3\n8\n6\n10\n", 'saida' => "8.0\n"],
                    ['entrada' => "2\n7\n8\n", 'saida' => "7.5\n"],
                    ['entrada' => "3\n10\n10\n9\n", 'saida' => "9.67\n"],
                ],
            ],
            [
                'slug' => 'texto-contar-letra',
                'titulo' => 'Contar uma letra',
                'conceito' => 'texto',
                'ordem' => 8,
                'conteudo' => implode("\n", [
                    'Nem todo dado é número.',
                    'O texto também é um dado.',
                    'Uma palavra é um texto.',

                    'A ordem input já devolve texto.',
                    'Aqui você não precisa da ordem int.',
                    'Você usa o texto do jeito que ele veio.',

                    'O texto é uma sequência de letras.',
                    'O laço for percorre o texto letra por letra.',
                    'A cada volta a caixa recebe uma letra.',

                    'Você já sabe contar com o acumulador.',
                    'Aqui o acumulador conta letras, não soma valores.',
                    'Ele começa em zero e cresce de um em um.',

                    'Dentro do laço vem uma escolha.',
                    'A condição pergunta se a letra é a letra procurada.',
                    'Use dois sinais de igual para comparar.',
                    'Se a resposta for verdadeiro, some um no total.',

                    'O texto vem entre aspas no código.',
                    'A letra também vem entre aspas.',
                    'Letra maiúscula e minúscula são diferentes para o computador.',
                ]),
                'sinais' => [
                    'A ordem input já devolve texto.' => 'A ordem que espera a pessoa digitar já devolve texto.',
                    'Aqui você não precisa da ordem int.' => 'Aqui você não precisa da ordem que faz número.',
                    'O laço for percorre o texto letra por letra.' => 'O laço percorre o texto letra por letra.',
                ],
                'glossario' => [
                    ['termo' => 'texto', 'explicacao' => 'Dado feito de letras, não de números.'],
                    ['termo' => "'a'", 'explicacao' => 'Uma letra escrita entre aspas.'],
                    ['termo' => 'for letra in palavra', 'explicacao' => 'Pega uma letra por vez da palavra.'],
                ],
                'exemplo' => implode("\n", [
                    '# aqui nao usamos int, porque queremos o texto',
                    'palavra = input()',
                    'total = 0',
                    '',
                    '# a cada volta a caixa recebe uma letra',
                    'for letra in palavra:',
                    "    if letra == 'o':",
                    '        total = total + 1',
                    '',
                    'print(total)',
                    '',
                    '# com a palavra ovo a tela mostra 2',
                ]),
                'enunciado' => implode("\n", [
                    'Escreva um programa que conta uma letra na palavra.',
                    'Leia uma palavra e guarde na caixa palavra.',
                    'Leia uma letra e guarde na caixa letra.',
                    'Conte quantas vezes a letra aparece na palavra.',
                    'Mostre apenas a quantidade, mais nada.',
                    'Se a letra não aparecer, mostre zero.',
                ]),
                'exemplo_execucao' => "A pessoa digita:\nbanana\na\n\nO programa mostra:\n3",
                'codigo_inicial' => "palavra = input()\nletra = input()\n\n# crie a caixa do total antes do laco\n# percorra a palavra letra por letra\n# some um cada vez que a letra aparecer\n",
                'casos_teste' => [
                    ['entrada' => "banana\na\n", 'saida' => "3\n"],
                    ['entrada' => "casa\nz\n", 'saida' => "0\n"],
                    ['entrada' => "ovo\no\n", 'saida' => "2\n"],
                ],
            ],
            [
                'slug' => 'funcao-dobro',
                'titulo' => 'Criar uma função',
                'conceito' => 'funções',
                'ordem' => 9,
                'conteudo' => implode("\n", [
                    'Você já usou ordens prontas do Python.',
                    'A ordem print é uma delas.',
                    'Agora você vai criar a sua própria ordem.',
                    'Essa ordem nova se chama função.',

                    'A ordem def cria a função.',
                    'Depois do def vem o nome que você escolhe.',
                    'Depois do nome vêm dois parênteses.',
                    'Dentro dos parênteses vem o que a função recebe.',
                    'Esse valor recebido se chama parâmetro.',

                    'Depois dos parênteses você escreve dois pontos.',
                    'As linhas de dentro começam com quatro espaços.',
                    'Essas linhas são o corpo da função.',

                    'A ordem return devolve o resultado.',
                    'Ela também encerra a função na hora.',
                    'As linhas depois do return não rodam.',

                    'Criar a função não roda a função.',
                    'Ela só roda quando você chama pelo nome.',
                    'Você chama escrevendo o nome e o valor nos parênteses.',
                    'O valor que você passa entra no parâmetro.',

                    'A mesma função serve para muitos valores.',
                    'Você escreve a regra uma vez só.',
                    'Depois usa quantas vezes precisar.',
                ]),
                'sinais' => [
                    'A ordem print é uma delas.' => 'A ordem que mostra na tela é uma delas.',
                    'A ordem def cria a função.' => 'Uma ordem especial cria a função.',
                    'Depois do def vem o nome que você escolhe.' => 'Depois dessa ordem vem o nome que você escolhe.',
                    'A ordem return devolve o resultado.' => 'Outra ordem devolve o resultado.',
                    'As linhas depois do return não rodam.' => 'As linhas depois dessa ordem não rodam.',
                ],
                'glossario' => [
                    ['termo' => 'def', 'explicacao' => 'Cria uma ordem nova, com nome escolhido por você.'],
                    ['termo' => 'return', 'explicacao' => 'Devolve o resultado e encerra a função.'],
                    ['termo' => 'parâmetro', 'explicacao' => 'Valor que a função recebe para trabalhar.'],
                ],
                'exemplo' => implode("\n", [
                    '# esta funcao recebe um numero e devolve o numero mais um',
                    'def proximo(x):',
                    '    return x + 1',
                    '',
                    '# criar a funcao nao roda a funcao',
                    'n = int(input())',
                    '',
                    '# aqui ela roda, com o valor de n no lugar do x',
                    'print(proximo(n))',
                ]),
                'enunciado' => implode("\n", [
                    'Escreva uma função que devolve o dobro de um número.',
                    'A função deve se chamar dobro.',
                    'Ela recebe um número e devolve o dobro dele.',
                    'Use a ordem return para devolver.',
                    'Depois leia um número e guarde na caixa n.',
                    'Chame a função dobro com esse número.',
                    'Mostre apenas o resultado, mais nada.',
                ]),
                'exemplo_execucao' => "A pessoa digita:\n5\n\nO programa mostra:\n10",
                'codigo_inicial' => "# crie aqui a funcao dobro\n\nn = int(input())\n\n# chame a funcao e mostre o resultado\n",
                'casos_teste' => [
                    ['entrada' => "5\n", 'saida' => "10\n"],
                    ['entrada' => "0\n", 'saida' => "0\n"],
                    ['entrada' => "13\n", 'saida' => "26\n"],
                ],
            ],
        ];

        foreach ($exercicios as $dados) {
            Exercise::updateOrCreate(['slug' => $dados['slug']], $dados);
        }
    }
}
