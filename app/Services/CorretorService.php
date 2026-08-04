<?php

namespace App\Services;

use App\Models\Exercise;

class CorretorService
{
    public function __construct(private SandboxService $sandbox) {}

    public function corrigir(Exercise $exercicio, string $codigo): array
    {
        $resultados = [];

        foreach ($exercicio->casos_teste as $i => $caso) {
            $r = $this->sandbox->executar($codigo, $caso['entrada']);

            $resultados[] = [
                'indice' => $i + 1,
                'passou' => $r['status'] === 'ok'
                    && $this->normalizar($r['stdout']) === $this->normalizar($caso['saida']),
                'status' => $r['status'],
                'entrada' => $caso['entrada'],
                'esperado' => $caso['saida'],
                'obtido' => $r['stdout'],
                'stderr' => $r['stderr'],
                'exit' => $r['exit'],
            ];

            // Timeout ou excecao se repetem em todos os casos, nao vale gastar container.
            if ($r['status'] !== 'ok') {
                break;
            }
        }

        return $resultados;
    }

    /**
     * Diferenca de quebra de linha no fim e a causa mais comum de falso negativo.
     */
    private function normalizar(string $texto): string
    {
        return rtrim(str_replace("\r\n", "\n", $texto));
    }
}
