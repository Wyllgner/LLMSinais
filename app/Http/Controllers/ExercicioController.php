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
        // O enunciado ja chega segmentado para o realce sincronizado com o VLibras.
        $segmentos = $this->segmentador->segmentar($exercicio->enunciado);

        return view('exercicio', compact('exercicio', 'segmentos'));
    }
}
