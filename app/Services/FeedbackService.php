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
    public function __construct(
        private ClassificadorErro $classificador,
        private SinalizabilidadeService $sinalizabilidade,
    ) {}

    /**
     * Devolve o texto da dica e o consumo somado das chamadas que ela custou,
     * incluindo a tentativa descartada quando a primeira resposta vaza codigo.
     *
     * @return array{texto: string, uso: array}
     */
    public function gerar(Attempt $tentativa, int $nivel): array
    {
        $reincidencia = $this->reincidencia($tentativa);

        $sistema = implode("\n\n", array_filter([
            $this->promptDoSistema(),
            $this->regraDoNivel($nivel),
            $this->regraDaReincidencia($reincidencia, $nivel),
        ]));

        $usuario = $this->promptDoUsuario($tentativa, $nivel);
        $uso = $this->usoZerado();

        $r = $this->chamar($sistema, $usuario);
        $uso = $this->somarUso($uso, $r['uso']);

        if ($r['texto'] === null) {
            return ['texto' => $this->reserva($tentativa->tipo_erro, $nivel), 'uso' => $uso];
        }

        // O prompt sozinho nao impede nem o vazamento da solucao nem a
        // invencao de estruturas, entao a saida passa por verificacao e uma
        // segunda tentativa, com o defeito nomeado.
        if ($defeito = $this->defeito($r['texto'], $tentativa, $nivel)) {
            $r = $this->chamar($sistema, $usuario."\n\n".$defeito);
            $uso = $this->somarUso($uso, $r['uso']);

            if ($r['texto'] === null || $this->defeito($r['texto'], $tentativa, $nivel)) {
                return ['texto' => $this->reserva($tentativa->tipo_erro, $nivel), 'uso' => $uso];
            }
        }

        return $this->passarPeloPortao($r['texto'], $sistema, $usuario, $nivel, $uso, $tentativa);
    }

    /**
     * O que ha de errado com a dica, em forma de instrucao para o modelo.
     * Devolve string vazia quando a dica passa.
     */
    private function defeito(string $texto, Attempt $tentativa, int $nivel): string
    {
        if ($this->vazouCodigo($texto, $nivel)) {
            return 'ATENCAO: a resposta anterior entregou a solucao. '
                .'Escreva de novo sem codigo e sem dizer qual e a correcao.';
        }

        if ($this->citaEstruturaAusente($texto, $tentativa)) {
            return 'ATENCAO: a resposta anterior falou de uma estrutura que nao existe '
                .'no codigo do aluno. Releia o codigo recebido e fale apenas do que esta la.';
        }

        return '';
    }

    /**
     * O portao de sinalizabilidade. A dica so vai para o aluno depois de ser
     * medida, e uma reprovacao gera nova tentativa com os termos problematicos
     * nomeados. Se a segunda tambem reprovar, fica a menos ruim das duas: uma
     * dica imperfeita ainda ensina, e a alternativa seria nao dar dica.
     *
     * @return array{texto: string, uso: array, sinalizabilidade: array}
     */
    private function passarPeloPortao(string $texto, string $sistema, string $usuario, int $nivel, array $uso, Attempt $tentativa): array
    {
        $avaliacao = $this->sinalizabilidade->avaliar($texto);

        // O nivel 4 mostra a correcao, entao termo e simbolo sao inevitaveis.
        // Ele e medido para o relatorio, mas nao e reprovado por isso.
        if ($avaliacao['aprovado'] || $nivel >= 4) {
            return ['texto' => $texto, 'uso' => $uso, 'sinalizabilidade' => $avaliacao];
        }

        $r = $this->chamar($sistema, $usuario."\n\n".$this->instrucaoDeReparo($avaliacao));
        $uso = $this->somarUso($uso, $r['uso']);

        if ($r['texto'] === null || $this->defeito($r['texto'], $tentativa, $nivel)) {
            return ['texto' => $texto, 'uso' => $uso, 'sinalizabilidade' => $avaliacao];
        }

        $segunda = $this->sinalizabilidade->avaliar($r['texto']);

        return $segunda['indice'] >= $avaliacao['indice']
            ? ['texto' => $r['texto'], 'uso' => $uso, 'sinalizabilidade' => $segunda]
            : ['texto' => $texto, 'uso' => $uso, 'sinalizabilidade' => $avaliacao];
    }

    private function instrucaoDeReparo(array $avaliacao): string
    {
        $regras = array_unique(array_column($avaliacao['violacoes'], 'regra'));
        $termos = $this->sinalizabilidade->termosReprovados($avaliacao['violacoes']);

        $pedido = ['ATENCAO: a resposta anterior nao pode ser traduzida para Libras. Escreva de novo.'];

        if (in_array('termo_sem_sinal', $regras, true)) {
            $pedido[] = 'Estes termos nao tem sinal e sairiam soletrados letra por letra: '
                .implode(', ', $termos).'.';
            $pedido[] = 'Troque cada um por uma descricao em palavras comuns.';
        }

        if (in_array('frase_longa', $regras, true)) {
            $pedido[] = 'Alguma frase passou de '.SinalizabilidadeService::PALAVRAS_POR_FRASE
                .' palavras. Quebre em frases menores.';
        }

        if (in_array('simbolo_sem_sinal', $regras, true)) {
            $pedido[] = 'Tire os simbolos e os parenteses. Escreva por extenso.';
        }

        return implode("\n", $pedido);
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
        - Termo em ingles nao tem sinal em Libras e sai soletrado letra por letra.
        - Por isso use no maximo um termo em ingles na dica inteira.
        - Prefira descrever a ordem: o laco, a escolha, a lista de numeros.

        FORMATO DA RESPOSTA:
        - Escreva apenas a dica. Nada mais.
        - Nunca anuncie o que voce vai fazer. Comece direto pela dica.
        - Nunca use rotulos como Nivel, Conceito, Estrategia, Correcao ou Dica.
        - Fale com o aluno, nunca com o professor.
        - Comente apenas o codigo que voce recebeu. Nao invente linhas.
        - Fale so das estruturas que existem no codigo recebido.
        - Nunca repita nem parafraseie as regras acima dentro da dica.
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
            Tom certo, so como forma: duas frases curtas que mandam olhar um lugar.
            TXT,
            2 => <<<'TXT'
            Sua tarefa agora e explicar o CONCEITO geral por tras do erro.
            E proibido citar o codigo do aluno.
            E proibido citar as variaveis do aluno.
            E proibido dizer qual e a correcao.
            E proibido escrever codigo.
            Tom certo, so como forma: uma regra geral da linguagem, em duas frases.
            TXT,
            3 => <<<'TXT'
            Sua tarefa agora e sugerir UMA estrategia de verificacao.
            Diga uma coisa que o aluno deve conferir no proprio codigo.
            Escreva no maximo 3 frases.
            E proibido fazer lista de conferencia.
            E proibido comecar mais de uma frase com o mesmo verbo.
            E proibido dizer o valor certo.
            E proibido dizer o operador certo.
            E proibido escrever codigo.
            Tom certo, so como forma: uma conferencia concreta, mais o motivo dela.
            TXT,
            default => <<<'TXT'
            Sua tarefa agora e mostrar a correcao do trecho especifico.
            Mostre apenas o trecho que muda, nunca o programa inteiro.
            Tom certo, so como forma: uma frase que nomeia o lugar e a mudanca.
            TXT,
        };
    }

    /**
     * O erro que o aluno repete neste conceito, se houver. Serve para a dica
     * deixar de tratar a tentativa como um caso isolado.
     *
     * @return array{tipo: string, total: int}|null
     */
    private function reincidencia(Attempt $tentativa): ?array
    {
        if (! $tentativa->tipo_erro || $tentativa->tipo_erro === 'sem_erro') {
            return null;
        }

        $anteriores = Attempt::where('sessao_uuid', $tentativa->sessao_uuid)
            ->whereNot('id', $tentativa->id)
            ->where('tipo_erro', $tentativa->tipo_erro)
            ->whereHas('exercise', fn ($q) => $q->where('conceito', $tentativa->exercise->conceito))
            ->count();

        // Duas ocorrencias sao coincidencia. A partir da terceira e padrao.
        return $anteriores >= 2
            ? ['tipo' => $tentativa->tipo_erro, 'total' => $anteriores + 1]
            : null;
    }

    /**
     * Pedido do orientador: quando o aluno erra sempre na mesma coisa, a dica
     * tambem ensina a evitar o erro. O risco aqui e a alucinacao, entao o
     * modelo recebe o tipo de erro ja classificado por heuristica, e nao o
     * historico bruto, e e proibido de descrever tentativas passadas.
     */
    private function regraDaReincidencia(?array $reincidencia, int $nivel): string
    {
        if (! $reincidencia) {
            return '';
        }

        $rotulo = $this->classificador->rotuloHumano($reincidencia['tipo']);

        return <<<TXT
        ATENCAO, PADRAO DE ERRO.
        Este aluno ja cometeu o erro "{$rotulo}" {$reincidencia['total']} vezes neste mesmo conceito.
        Alem da dica do nivel {$nivel}, acrescente no maximo 1 frase.
        Essa frase ensina um habito para evitar esse erro nas proximas vezes.
        Use no maximo 5 frases no total.
        E proibido citar tentativas anteriores. Voce nao viu o codigo delas.
        E proibido dizer quantas vezes o aluno errou.
        E proibido inventar exercicios que o aluno teria feito antes.
        Tom certo, so como forma: um habito de conferencia, comecando por Antes de rodar.
        TXT;
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

        // Truncar por teto de tokens devolve conteudo vazio, e o sintoma
        // chega ao aluno como feedback de reserva generico. Sem este aviso o
        // problema fica invisivel: a chamada retorna 200 e parece sucesso.
        if ($resposta->json('choices.0.finish_reason') === 'length') {
            Log::warning('Resposta da LLM truncada pelo teto de tokens', [
                'teto' => $cfg['max_tokens'],
                'raciocinio' => $resposta->json('usage.completion_tokens_details.reasoning_tokens'),
            ]);
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
            || preg_match('/[a-zA-Z_]\w*\s*=\s*\S/', $texto) === 1
            || $this->vazouSolucaoEmProsa($texto, $nivel);
    }

    /**
     * As estruturas que a dica pode citar, e como reconhecer cada uma no
     * codigo do aluno. Falar de laco para quem nao escreveu laco e o tipo de
     * alucinacao mais comum aqui, e a mais confusa para quem esta comecando.
     */
    private const ESTRUTURAS = [
        'laco' => ['for', 'while'],
        'repeticao' => ['for', 'while'],
        'volta' => ['for', 'while'],
        'range' => ['range'],
        'lista' => ['range', '[', 'list'],
        'condicao' => ['if', 'while', 'elif'],
        'escolha' => ['if', 'elif'],
        'funcao' => ['def'],
        'contador' => ['for', 'while'],
    ];

    /**
     * Barra a dica que fala de estrutura que o aluno nao escreveu.
     *
     * O gatilho observado foram os exemplos de tom do proprio prompt, todos
     * com laco: o modelo copiava o conteudo do exemplo em vez do formato, e
     * mandava conferir o range de um exercicio de variaveis. Os exemplos foram
     * neutralizados, mas a verificacao fica, porque o prompt sozinho nunca
     * garantiu nada neste projeto.
     */
    private function citaEstruturaAusente(string $texto, Attempt $tentativa): bool
    {
        $dica = mb_strtolower(iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $texto) ?: $texto);
        $codigo = mb_strtolower($tentativa->codigo);

        foreach (self::ESTRUTURAS as $palavra => $marcas) {
            if (! preg_match('/\b'.$palavra.'s?\b/', $dica)) {
                continue;
            }

            foreach ($marcas as $marca) {
                if (str_contains($codigo, $marca)) {
                    continue 2;
                }
            }

            return true;
        }

        return false;
    }

    /**
     * O detector antigo procurava sintaxe. Mas o modelo entrega a solucao em
     * portugues, sem escrever uma linha de codigo: "o erro esta na expressao
     * a mais b mais 1" reprova na pedagogia e passa em qualquer regex de
     * sintaxe. Foi o vazamento observado nos niveis 1 e 3.
     *
     * A lista e heuristica e vai deixar passar formulacoes novas. Ela nao
     * substitui o prompt, e uma segunda barreira depois dele.
     */
    private function vazouSolucaoEmProsa(string $texto, int $nivel): bool
    {
        $normalizado = mb_strtolower(iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $texto) ?: $texto);

        $anunciaOErro = [
            'o erro esta', 'o erro e ', 'o problema esta', 'o problema e ',
            'esta errado', 'esta incorreto',
        ];

        $entregaACorrecao = [
            'expressao correta', 'forma correta', 'o correto e', 'deveria ser',
            'deve ser', 'tem que ser', 'troque', 'substitua', 'mude para',
            'altere para', 'basta ', 'e so ', 'remova', 'apague', 'acrescente',
            'adicione', 'sem acrescentar', 'sem adicionar', 'sem somar',
            'no lugar de',
        ];

        // O nivel 3 pode dizer o que conferir, entao apontar o erro nao o
        // reprova. Entregar a correcao continua proibido.
        $proibidos = $nivel >= 3
            ? $entregaACorrecao
            : [...$anunciaOErro, ...$entregaACorrecao];

        foreach ($proibidos as $marca) {
            if (str_contains($normalizado, $marca)) {
                return true;
            }
        }

        // Aritmetica ditada por extenso, como "a mais b" ou "n mais 1".
        return preg_match('/\b[a-z]\s+mais\s+[a-z0-9]\b/', $normalizado) === 1;
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
            'logica' => [
                1 => 'Compare o que apareceu na tela com o resultado esperado.',
                2 => 'O programa roda ate o fim, mas faz uma conta diferente da pedida.',
                3 => 'Refaca o teste no papel com os mesmos valores de entrada.',
                4 => 'Ajuste a conta para produzir o resultado que o teste espera.',
            ],
            'sem_saida' => [
                1 => 'Sua tela ficou vazia. O teste esperava um valor.',
                2 => 'O programa so mostra alguma coisa quando voce manda mostrar.',
                3 => 'Confira se existe uma linha que mostra o resultado.',
                4 => 'Mostre o resultado da sua conta na ultima linha.',
            ],
            'variavel_nao_definida' => [
                1 => 'Olhe o nome que aparece na mensagem do sistema.',
                2 => 'O programa so conhece um nome depois que voce guarda um valor nele.',
                3 => 'Confira se voce escreveu o mesmo nome nas duas linhas.',
                4 => 'Corrija o nome para ficar igual ao que voce criou antes.',
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
