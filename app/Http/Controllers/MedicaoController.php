<?php

namespace App\Http\Controllers;

use App\Models\MedicaoTraducao;
use App\Services\LexicoSinais;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MedicaoController extends Controller
{
    public function store(Request $request, LexicoSinais $lexico): JsonResponse
    {
        $dados = $request->validate([
            'origem' => ['required', 'string', 'max:40'],
            'texto' => ['required', 'string', 'max:2000'],
            'glosa' => ['nullable', 'string', 'max:2000'],
            'sinais' => ['nullable', 'integer', 'min:0', 'max:500'],
            'soletrados' => ['nullable', 'array', 'max:100'],
            'soletrados.*' => ['string', 'max:80'],
        ]);

        MedicaoTraducao::create([
            ...$dados,
            'sessao_uuid' => $request->session()->get('sessao_uuid'),
        ]);

        // O que o avatar soletrou hoje e o que o verificador vai barrar
        // amanha. O lexico se alimenta sozinho.
        $lexico->aprender($dados['soletrados'] ?? []);

        return response()->json(['ok' => true]);
    }
}
