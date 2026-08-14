<?php

namespace App\Services;

/**
 * Mede se um texto esta pronto para ser traduzido por avatar.
 *
 * A literatura [R26] mostra que linguagem simples melhora a traducao
 * automatica para Libras, mas trata isso como recomendacao de escrita para
 * autores humanos. Aqui a recomendacao vira restricao verificada: o texto e
 * gerado, entao pode ser reprovado e gerado de novo antes de chegar ao aluno.
 *
 * As regras nao sao estilo. Cada uma corresponde a uma falha observada na
 * medicao: termo sem sinal cai em datilologia ou, pior, e traduzido para um
 * sinal errado sem aviso; frase longa perde o casamento com o realce; simbolo
 * nao tem sinal nenhum.
 */
class SinalizabilidadeService
{
    public const PALAVRAS_POR_FRASE = 12;

    private const SIMBOLOS = ['(', ')', '[', ']', '{', '}', ';', '—', '–', '/', '%', '*', '_'];

    public function __construct(
        private SegmentadorService $segmentador,
        private LexicoSinais $lexico,
    ) {}

    /**
     * @return array{
     *     aprovado: bool,
     *     indice: float,
     *     frases: int,
     *     violacoes: array<int, array{regra: string, trecho: string}>
     * }
     */
    public function avaliar(string $texto): array
    {
        $frases = $this->segmentador->segmentar($texto);
        $violacoes = [];

        foreach ($frases as $frase) {
            foreach ($this->violacoesDaFrase($frase) as $violacao) {
                $violacoes[] = $violacao;
            }
        }

        $total = max(count($frases), 1);

        // Uma frase pode violar mais de uma regra. O indice e a fracao de
        // frases limpas, entao ele nao passa de 1 nem fica negativo.
        $sujas = count(array_unique(array_column($violacoes, 'trecho')));

        return [
            'aprovado' => $violacoes === [],
            'indice' => round(($total - min($sujas, $total)) / $total, 2),
            'frases' => count($frases),
            'violacoes' => $violacoes,
        ];
    }

    /**
     * @return array<int, array{regra: string, trecho: string}>
     */
    private function violacoesDaFrase(string $frase): array
    {
        $violacoes = [];

        $palavras = preg_split('/\s+/u', trim($frase), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        if (count($palavras) > self::PALAVRAS_POR_FRASE) {
            $violacoes[] = ['regra' => 'frase_longa', 'trecho' => $frase];
        }

        foreach (self::SIMBOLOS as $simbolo) {
            if (str_contains($frase, $simbolo)) {
                $violacoes[] = ['regra' => 'simbolo_sem_sinal', 'trecho' => $frase];
                break;
            }
        }

        foreach ($palavras as $palavra) {
            if ($this->lexico->contem($palavra)) {
                $violacoes[] = ['regra' => 'termo_sem_sinal', 'trecho' => $frase, 'termo' => $palavra];
            }
        }

        return $violacoes;
    }

    /**
     * Os termos sem sinal encontrados, para o modelo saber o que trocar.
     *
     * @param  array<int, array<string, string>>  $violacoes
     * @return array<int, string>
     */
    public function termosReprovados(array $violacoes): array
    {
        return array_values(array_unique(array_filter(array_column($violacoes, 'termo'))));
    }
}
