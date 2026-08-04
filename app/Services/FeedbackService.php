<?php

namespace App\Services;

use App\Models\Attempt;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Gera o feedback pedagogico. O texto sai sob duas restricoes simultaneas:
 * a pedagogica, que limita o que cada nivel pode revelar, e a de
 * tradutibilidade, que prepara o texto para a traducao em Libras.
 */
class FeedbackService
{
    public function __construct(private ClassificadorErro $classificador) {}

    /**
     * Devolve o texto da dica e o consumo somado das chamadas que ela custou,
     * incluindo a tentativa descartada quando a primeira resposta vaza codigo.
     *
     * @return array{texto: string, uso: array}
     */
    public function gerar(Attempt $tentativa, int $nivel): array
    {
        $sistema = $this->promptDoSistema()."\n\n".$this->regraDoNivel($nivel);
        $usuario = $this->promptDoUsuario($tentativa, $nivel);
        $uso = $this->usoZerado();

        $r = $this->chamar($sistema, $usuario);
        $uso = $this->somarUso($uso, $r['uso']);

        if ($r['texto'] === null) {
            return ['texto' => $this->reserva($tentativa->tipo_erro, $nivel), 'uso' => $uso];
        }

        // O prompt sozinho nao impede o vazamento da solucao, entao a saida
        // passa por uma verificacao sintatica e uma segunda tentativa.
        if ($this->vazouCodigo($r['texto'], $nivel)) {
            $r = $this->chamar(
                $sistema,
                $usuario."\n\nATENCAO: a resposta anterior mostrou codigo. Escreva sem nenhum codigo."
            );
            $uso = $this->somarUso($uso, $r['uso']);

            if ($r['texto'] === null || $this->vazouCodigo($r['texto'], $nivel)) {
                return ['texto' => $this->reserva($tentativa->tipo_erro, $nivel), 'uso' => $uso];
            }
        }

        return ['texto' => $r['texto'], 'uso' => $uso];
    }

    private function usoZerado(): array
    {
        return ['entrada' => 0, 'saida' => 0, 'raciocinio' => 0, 'chamadas' => 0];
    }

    private function somarUso(array $total, array $novo): array
    {
        foreach ($total as $chave => $valor) {
            $total[$chave] = $valor + ($novo[$chave] ?? 0);
        }

        return $total;
    }

    /**
     * Reescreve a mensagem do interpretador em portugues simples. O cache e por
     * conteudo porque as mesmas mensagens se repetem muito entre alunos.
     */
    public function simplificarErro(string $stderr): string
    {
        return Cache::rememberForever('erro:'.sha1($stderr), function () use ($stderr) {
            $r = $this->chamar(
                "Voce reescreve mensagens de erro do Python para estudantes surdos que leem portugues como segunda lingua.\n".
                "Escreva no maximo 2 frases.\n".
                "Cada frase tem no maximo 12 palavras.\n".
                "Use ordem direta e palavras simples.\n".
                "Diga apenas o que aconteceu. Nao diga como corrigir.\n".
                "Nao use travessao. Nao use parenteses.",
                "Mensagem original:\n".$this->limitar($stderr)
            );

            return $r['texto'] ?? 'O programa parou por causa de um erro.';
        });
    }

    private function promptDoSistema(): string
    {
        return <<<'TXT'
        Voce e um tutor de Programacao I. Seu aluno e surdo e le portugues como
        segunda lingua. Ele esta aprendendo Python.

        REGRAS DE ESCRITA, obrigatorias:
        - Escreva no maximo 4 frases.
        - Uma ideia por frase. No maximo 12 palavras por frase.
        - Use ordem direta: sujeito, verbo, objeto.
        - Nao use voz passiva. Nao use metaforas. Nao use ironia.
        - Repita sempre o mesmo termo para o mesmo conceito. Nunca use sinonimos.
        - Prefira a palavra concreta quando ela existir.
        - Nao use ponto e virgula. Nao use travessao. Nao use parenteses.
        - Nao use palavras em ingles, exceto for, while, if, print, input e range.

        FORMATO DA RESPOSTA:
        - Escreva apenas a dica. Nada mais.
        - Nunca anuncie o que voce vai fazer. Comece direto pela dica.
        - Nunca use rotulos como Nivel, Conceito, Estrategia, Correcao ou Dica.
        - Fale com o aluno, nunca com o professor.
        - Comente apenas o codigo que voce recebeu. Nao invente linhas.
        TXT;
    }

    /**
     * Cada nivel recebe so a propria regra. Descrever os quatro de uma vez fazia
     * o modelo antecipar a correcao ja na primeira dica.
     */
    private function regraDoNivel(int $nivel): string
    {
        return match ($nivel) {
            1 => <<<'TXT'
            Sua tarefa agora e apontar ONDE o aluno deve olhar.
            Cite a linha ou o trecho do codigo dele.
            E proibido explicar o motivo do erro.
            E proibido dizer o que mudar.
            E proibido escrever codigo.
            Exemplo do tom certo: Olhe a condicao do seu laco. Olhe tambem o que muda dentro dele.
            TXT,
            2 => <<<'TXT'
            Sua tarefa agora e explicar o CONCEITO geral por tras do erro.
            E proibido citar o codigo do aluno.
            E proibido citar as variaveis do aluno.
            E proibido dizer qual e a correcao.
            E proibido escrever codigo.
            Exemplo do tom certo: Um laco repete enquanto a condicao for verdadeira. Se nada muda, a condicao continua verdadeira para sempre.
            TXT,
            3 => <<<'TXT'
            Sua tarefa agora e sugerir uma ESTRATEGIA de verificacao.
            Diga o que o aluno deve conferir no proprio codigo.
            E proibido dizer o valor certo.
            E proibido dizer o operador certo.
            E proibido escrever codigo.
            Exemplo do tom certo: Verifique se algum valor da condicao muda a cada repeticao. Sem essa mudanca o laco nao para.
            TXT,
            default => <<<'TXT'
            Sua tarefa agora e mostrar a correcao do trecho especifico.
            Mostre apenas o trecho que muda, nunca o programa inteiro.
            Exemplo do tom certo: Dentro do laco, aumente o contador em 1 com i = i + 1.
            TXT,
        };
    }

    private function promptDoUsuario(Attempt $tentativa, int $nivel): string
    {
        $falhou = $tentativa->primeiroTesteFalho() ?? [];
        $historico = $this->errosRecorrentes($tentativa);

        return implode("\n", [
            "NIVEL DA DICA: {$nivel}",
            '',
            'ENUNCIADO:',
            $tentativa->exercise->enunciado,
            '',
            'CODIGO DO ALUNO:',
            $tentativa->codigo,
            '',
            'TIPO DE ERRO DETECTADO: '.$tentativa->tipo_erro,
            'MENSAGEM DO SISTEMA: '.$this->limitar($tentativa->stderr_bruto ?: 'nenhuma'),
            'ENTRADA DO TESTE: '.$this->limitar($falhou['entrada'] ?? ''),
            'SAIDA ESPERADA: '.$this->limitar($falhou['esperado'] ?? ''),
            'SAIDA OBTIDA: '.$this->limitar($falhou['obtido'] ?? ''),
            '',
            'HISTORICO DO ALUNO: '.$historico,
        ]);
    }

    /**
     * Segunda barreira contra saida gigante, caso o teto do sandbox mude.
     */
    private function limitar(string $texto, int $maximo = 600): string
    {
        return strlen($texto) <= $maximo
            ? $texto
            : substr($texto, 0, $maximo)."\n[texto cortado]";
    }

    /**
     * Os erros mais repetidos do aluno neste conceito, para a LLM levar em conta.
     */
    private function errosRecorrentes(Attempt $tentativa): string
    {
        $erros = Attempt::where('sessao_uuid', $tentativa->sessao_uuid)
            ->whereNot('id', $tentativa->id)
            ->whereNotNull('tipo_erro')
            ->whereNot('tipo_erro', 'sem_erro')
            ->whereHas('exercise', fn ($q) => $q->where('conceito', $tentativa->exercise->conceito))
            ->latest()
            ->limit(5)
            ->pluck('tipo_erro')
            ->countBy()
            ->sortDesc();

        if ($erros->isEmpty()) {
            return 'primeira tentativa neste conceito';
        }

        return $erros->map(fn ($n, $tipo) => "{$tipo} ({$n}x)")->implode(', ');
    }

    /**
     * @return array{texto: ?string, uso: array}
     */
    private function chamar(string $sistema, string $usuario): array
    {
        $cfg = config('llmsinais.openai');
        $vazio = ['texto' => null, 'uso' => $this->usoZerado()];

        try {
            $resposta = Http::withToken($cfg['key'])
                ->timeout(45)
                ->retry(2, 500, throw: false)
                ->post($cfg['base'].'/chat/completions', [
                    'model' => $cfg['model'],
                    'max_completion_tokens' => $cfg['max_tokens'],
                    'reasoning_effort' => $cfg['reasoning_effort'],
                    'messages' => [
                        ['role' => 'system', 'content' => $sistema],
                        ['role' => 'user', 'content' => $usuario],
                    ],
                ]);
        } catch (\Throwable $e) {
            Log::warning('Falha ao chamar a LLM', ['erro' => $e->getMessage()]);

            return $vazio;
        }

        if ($resposta->failed()) {
            Log::warning('LLM respondeu com erro', ['status' => $resposta->status(), 'corpo' => $resposta->body()]);

            return $vazio;
        }

        // Os tokens de raciocinio ja vem somados em completion_tokens.
        $uso = [
            'entrada' => (int) $resposta->json('usage.prompt_tokens', 0),
            'saida' => (int) $resposta->json('usage.completion_tokens', 0),
            'raciocinio' => (int) $resposta->json('usage.completion_tokens_details.reasoning_tokens', 0),
            'chamadas' => 1,
        ];

        $texto = $this->limpar((string) $resposta->json('choices.0.message.content'));

        return ['texto' => $texto !== '' ? $texto : null, 'uso' => $uso];
    }

    /**
     * O modelo as vezes ecoa o rotulo do nivel antes da dica. Rede de seguranca
     * para o caso de o prompt nao dar conta sozinho.
     */
    private function limpar(string $texto): string
    {
        $rotulos = 'nivel|n[ií]vel|dica|conceito|estrat[eé]gia|corre[cç][aã]o|resposta';

        $texto = trim($texto);

        // Remove um rotulo curto no comeco, com ou sem quebra de linha depois.
        $texto = preg_replace('/^\s*(voc[eê] est[aá] no\s+)?('.$rotulos.')[^.!?\n]{0,30}:\s*/iu', '', $texto);

        // O modelo tambem repete o rotulo antes de cada frase.
        $texto = preg_replace('/(^|\n)\s*('.$rotulos.')\s*\d*\s*:\s*/iu', '$1', $texto);

        return trim($texto);
    }

    /**
     * Detecta codigo na resposta dos niveis que o proibem.
     */
    private function vazouCodigo(string $texto, int $nivel): bool
    {
        if ($nivel >= 4) {
            return false;
        }

        return str_contains($texto, '```')
            || preg_match('/\b(for|while|if|def|range|print|input|int)\s*[\(:]/', $texto) === 1
            || preg_match('/[a-zA-Z_]\w*\s*=\s*\S/', $texto) === 1;
    }

    /**
     * Texto fixo para quando a API falha, para a demonstracao nao quebrar.
     */
    private function reserva(?string $tipoErro, int $nivel): string
    {
        $porTipo = [
            'laco_infinito' => [
                1 => 'Olhe para o seu laco. Ele nunca para de repetir.',
                2 => 'Um laco precisa de uma condicao que muda. Se a condicao nunca muda, o laco nao termina.',
                3 => 'Verifique se alguma variavel da condicao muda dentro do laco. Ela precisa mudar a cada repeticao.',
                4 => 'Some 1 na variavel de controle dentro do laco. Assim a condicao fica falsa em algum momento.',
            ],
            'off_by_one' => [
                1 => 'Conte quantas linhas voce mostrou. Compare com o resultado esperado.',
                2 => 'O range comeca no primeiro numero e para antes do segundo. O ultimo numero nao entra.',
                3 => 'Ajuste o limite do seu range. Pense em qual numero precisa aparecer por ultimo.',
                4 => 'Use range de 1 ate n mais 1. Assim o numero n tambem aparece.',
            ],
            'sintaxe' => [
                1 => 'Olhe a linha indicada na mensagem do sistema.',
                2 => 'O Python precisa que cada abertura tenha um fechamento. Isso vale para parenteses e aspas.',
                3 => 'Confira os parenteses e as aspas da linha com erro.',
                4 => 'Feche o parenteses que ficou aberto na linha indicada.',
            ],
        ];

        return $porTipo[$tipoErro][$nivel]
            ?? 'Olhe de novo o seu codigo. Compare o resultado esperado com o resultado que apareceu.';
    }
}
