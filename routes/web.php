<?php

use App\Http\Controllers\ExercicioController;
use App\Http\Controllers\TrilhaController;
use Illuminate\Support\Facades\Route;

Route::get('/', [TrilhaController::class, 'index'])->name('trilha');
Route::get('/exercicio/{exercicio}', [ExercicioController::class, 'show'])->name('exercicio');
