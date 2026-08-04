<?php

namespace App\Services;

/**
 * Rotula o tipo de falha a partir da saida do sandbox, por heuristica
 * deterministica. E este rotulo que ancora a LLM na fase seguinte: ela
 * verbaliza um diagnostico pronto em vez de adivinhar o proprio.
 */
class ClassificadorErro
{
    /**
     * Assinaturas de excecao do Python, na ordem em que sao testadas.
     */
    private const EXCECOES = [
        'SyntaxError' => 'sintaxe',
        'IndentationError' => 'indentacao',
        'TabError' => 'indentacao',
        'NameError' => 'variavel_nao_definida',
        'ZeroDivisionError' => 'divisao_por_zero',
        'IndexError' => 'indice_invalido',
        'KeyError' => 'chave_invalida',
        'ValueError' => 'conversao_invalida',
        'TypeError' => 'tipo_incompativel',
        'AttributeError' => 'atributo_invalido',
        'EOFError' => 'entrada_insuficiente',
    ];

    /**
     * Nome curto de cada rotulo, ja em portugues simplificado, para a tela.
     */
    private const ROTULOS = [
        'sem_erro' => 'Sem erro',
        'laco_infinito' => 'Laco infinito',
        'sintaxe' => 'Erro de escrita',
        'indentacao' => 'Erro de espacos',
        'variavel_nao_definida' => 'Variavel nao criada',
        'divisao_por_zero' => 'Divisao por zero',
        'indice_invalido' => 'Posicao que nao existe',
        'chave_invalida' => 'Chave que nao existe',
        'conversao_invalida' => 'Valor de tipo errado',
        'tipo_incompativel' => 'Tipos que nao combinam',
        'atributo_invalido' => 'Comando que nao existe',
        'entrada_insuficiente' => 'Falta ler um valor',
        'sem_saida' => 'Nao mostrou nada',
        'off_by_one' => 'Falta ou sobra um numero',
        'logica' => 'Resultado diferente',
        'desconhecido' => 'Erro nao identificado',
    ];

    public function rotuloHumano(string $tipo): string
    {
        return self::ROTULOS[$tipo] ?? self::ROTULOS['desconhecido'];
    }

    public function classificar(array $resultados): string
    {
        $falho = collect($resultados)->firstWhere('passou', false);

        if ($falho === null) {
            return 'sem_erro';
        }

        if ($falho['status'] === 'timeout') {
            return 'laco_infinito';
        }

        $stderr = $falho['stderr'] ?? '';

        foreach (self::EXCECOES as $assinatura => $rotulo) {
            if (str_contains($stderr, $assinatura)) {
                return $rotulo;
            }
        }

        // Rodou ate o fim sem excecao, entao a falha esta na saida produzida.
        if ($falho['status'] === 'ok') {
            if (trim($falho['obtido']) === '') {
                return 'sem_saida';
            }

            if ($this->pareceOffByOne($falho)) {
                return 'off_by_one';
            }

            return 'logica';
        }

        return 'desconhecido';
    }

    /**
     * Detecta o classico range(1, n) no lugar de range(1, n+1): a saida tem
     * exatamente uma linha a menos ou a mais, e o resto casa na ordem.
     */
    private function pareceOffByOne(array $falho): bool
    {
        $esperado = $this->linhas($falho['esperado']);
        $obtido = $this->linhas($falho['obtido']);

        if (abs(count($esperado) - count($obtido)) !== 1) {
            return false;
        }

        [$menor, $maior] = count($esperado) < count($obtido)
            ? [$esperado, $obtido]
            : [$obtido, $esperado];

        return array_slice($maior, 0, count($menor)) === $menor
            || array_slice($maior, 1) === $menor;
    }

    private function linhas(string $texto): array
    {
        return array_values(array_filter(
            array_map('trim', explode("\n", trim($texto))),
            fn ($linha) => $linha !== ''
        ));
    }
}
