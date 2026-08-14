<?php

namespace App\Http\Controllers;

use App\Models\Exercise;
use App\Services\SegmentadorService;
use Illuminate\View\View;

class ExercicioController extends Controller
{
    public function __construct(private SegmentadorService $segmentador) {}

    public function show(Exercise $exercicio): View
    {
        // Enunciado e conteudo ja chegam segmentados para o realce sincronizado
        // com o VLibras, cada segmento com a propria versao sinalizavel.
        return view('exercicio', [
            'exercicio' => $exercicio,
            'segmentos' => $this->segmentador->segmentarParaLibras($exercicio->enunciado, $exercicio->sinais),
            'segmentosConteudo' => $this->segmentador->segmentarParaLibras($exercicio->conteudo ?? '', $exercicio->sinais),
        ]);
    }
}
