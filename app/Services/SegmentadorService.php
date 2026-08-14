<?php

namespace App\Services;

/**
 * Quebra o texto nas unidades que o VLibras traduz uma de cada vez.
 * A granularidade e a frase, nao a palavra: o widget do VLibras nao expoe
 * eventos por sinal, entao o realce acompanha o segmento em traducao.
 */
class SegmentadorService
{
    public function segmentar(string $texto): array
    {
        $texto = trim($texto);

        if ($texto === '') {
            return [];
        }

        // Quebra em fim de frase ou quebra de linha, o que vier primeiro.
        $partes = preg_split('/(?<=[.!?])\s+|\n+/u', $texto, -1, PREG_SPLIT_NO_EMPTY);

        return array_values(array_filter(array_map('trim', $partes ?: [])));
    }

    /**
     * Segmenta separando o que o aluno le do que o avatar sinaliza.
     *
     * Termo tecnico em ingles nao tem sinal estabelecido, entao o VLibras cai
     * na datilologia e soletra letra por letra. A frase exibida mantem o termo,
     * porque o aluno precisa aprende-lo, e a frase sinalizada usa uma parafrase
     * em vocabulario comum. Sem parafrase cadastrada, as duas sao iguais.
     *
     * @param  array<string, string>  $sinais  frase exibida => frase sinalizada
     * @return array<int, array{texto: string, sinal: string}>
     */
    public function segmentarParaLibras(string $texto, ?array $sinais = null): array
    {
        return array_map(
            fn (string $frase) => ['texto' => $frase, 'sinal' => $sinais[$frase] ?? $frase],
            $this->segmentar($texto)
        );
    }
}
