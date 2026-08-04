<?php

namespace App\Http\Controllers;

use App\Models\Attempt;
use App\Models\Exercise;
use App\Services\ClassificadorErro;
use App\Services\CorretorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubmissaoController extends Controller
{
    public function __construct(
        private CorretorService $corretor,
        private ClassificadorErro $classificador,
    ) {}

    public function store(Request $request, Exercise $exercicio): JsonResponse
    {
        $dados = $request->validate([
            'codigo' => ['required', 'string', 'max:10000'],
        ]);

        $resultados = $this->corretor->corrigir($exercicio, $dados['codigo']);
        $falhou = collect($resultados)->firstWhere('passou', false);
        $tipoErro = $this->classificador->classificar($resultados);

        // Sem falha nao ha stderr, e o campo do banco guarda null em vez de string vazia.
        $stderr = ($falhou['stderr'] ?? '') ?: null;

        $tentativa = Attempt::create([
            'sessao_uuid' => $request->session()->get('sessao_uuid'),
            'exercise_id' => $exercicio->id,
            'codigo' => $dados['codigo'],
            'passou' => $falhou === null,
            'tipo_erro' => $tipoErro,
            'stderr_bruto' => $stderr,
            'resultado_testes' => $resultados,
        ]);

        return response()->json([
            'tentativa_id' => $tentativa->id,
            'passou' => $tentativa->passou,
            'testes' => collect($resultados)->map(fn ($r) => [
                'indice' => $r['indice'],
                'passou' => $r['passou'],
                'status' => $r['status'],
            ]),
            'total' => count($exercicio->casos_teste),
            'stderr' => $stderr,
            'tipo_erro' => $tentativa->passou ? null : $tipoErro,
            'tipo_erro_rotulo' => $tentativa->passou ? null : $this->classificador->rotuloHumano($tipoErro),
        ]);
    }
}
