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
}
