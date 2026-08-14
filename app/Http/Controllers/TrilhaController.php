<?php

namespace App\Http\Controllers;

use App\Models\Exercise;
use App\Services\ProgressoService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TrilhaController extends Controller
{
    public function __construct(private ProgressoService $progresso) {}

    public function index(Request $request): View
    {
        $sessao = $request->session()->get('sessao_uuid');

        $exercicios = Exercise::orderBy('ordem')->get();

        // Um nivel so libera depois que o anterior for concluido.
        $liberado = true;
        foreach ($exercicios as $exercicio) {
            $exercicio->concluido = $exercicio->concluidoPor($sessao);
            $exercicio->liberado = $liberado;
            $liberado = $exercicio->concluido;
        }

        return view('trilha', [
            'exercicios' => $exercicios,
            'progresso' => $this->progresso->resumo($sessao),
        ]);
    }
}
