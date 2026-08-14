<?php

namespace App\Services;

use App\Models\MedicaoTraducao;
use Illuminate\Support\Facades\Cache;

/**
 * Os termos que o avatar nao consegue sinalizar.
 *
 * Duas fontes. A semente cobre o vocabulario de Programacao I, para o sistema
 * ja nascer sabendo o obvio. O resto vem da medicao: tudo que o avatar
 * soletrou em execucao entra no lexico e passa a ser barrado na geracao
 * seguinte. O sistema aprende com o proprio tradutor.
 */
class LexicoSinais
{
    private const CHAVE = 'lexico:sinais';

    /**
     * Termos de programacao sem sinal estabelecido em Libras, confirmados na
     * medicao de 13/08 ou previstos por [R19] [R20] [R25].
     */
    private const SEMENTE = [
        'INPUT', 'PRINT', 'INT', 'STR', 'FLOAT', 'BOOL', 'RANGE', 'FOR', 'WHILE',
        'IF', 'ELSE', 'ELIF', 'DEF', 'RETURN', 'IMPORT', 'LEN', 'LIST', 'DICT',
        'PYTHON', 'TRACEBACK', 'SYNTAXERROR', 'NAMEERROR', 'TYPEERROR',
        'INDEXERROR', 'VALUEERROR', 'INDENTATIONERROR', 'ZERODIVISIONERROR',
        'EOFERROR', 'KEYERROR', 'ATTRIBUTEERROR', 'LOOP', 'ARRAY', 'STRING',
        'DEBUG', 'BUG', 'SOFTWARE', 'HARDWARE', 'ENTER',
    ];

    /**
     * @return array<int, string> termos em caixa alta, sem acento
     */
    public function termos(): array
    {
        return Cache::remember(self::CHAVE, 3600, function () {
            $medidos = MedicaoTraducao::whereNotNull('soletrados')
                ->pluck('soletrados')
                ->flatten()
                ->filter()
                ->map(fn ($t) => $this->normalizar($t))
                ->all();

            return array_values(array_unique([...self::SEMENTE, ...$medidos]));
        });
    }

    /**
     * @param  array<int, string>  $soletrados
     */
    public function aprender(array $soletrados): void
    {
        if ($soletrados) {
            Cache::forget(self::CHAVE);
        }
    }

    public function contem(string $palavra): bool
    {
        return in_array($this->normalizar($palavra), $this->termos(), true);
    }

    public function normalizar(string $texto): string
    {
        $semAcento = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $texto) ?: $texto;

        return preg_replace('/[^A-Z0-9]/', '', mb_strtoupper($semAcento)) ?? '';
    }
}
