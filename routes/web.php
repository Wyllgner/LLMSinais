<?php

use App\Http\Controllers\DicaController;
use App\Http\Controllers\ExercicioController;
use App\Http\Controllers\MedicaoController;
use App\Http\Controllers\SubmissaoController;
use App\Http\Controllers\TrilhaController;
use Illuminate\Support\Facades\Route;

Route::get('/', [TrilhaController::class, 'index'])->name('trilha');
Route::get('/exercicio/{exercicio}', [ExercicioController::class, 'show'])->name('exercicio');
Route::post('/exercicio/{exercicio}/submeter', [SubmissaoController::class, 'store'])->name('submeter');
Route::post('/tentativa/{tentativa}/dica', [DicaController::class, 'proximaDica'])->name('dica');
Route::post('/tentativa/{tentativa}/erro-simples', [DicaController::class, 'erroSimplificado'])->name('erro-simples');
Route::post('/medicao', [MedicaoController::class, 'store'])->name('medicao');
