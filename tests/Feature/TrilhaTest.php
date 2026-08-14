<?php

namespace Tests\Feature;

use Database\Seeders\ExerciseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrilhaTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_trilha_abre_com_os_exercicios_semeados(): void
    {
        $this->seed(ExerciseSeeder::class);

        $this->get('/')->assertStatus(200);
    }

    public function test_slug_inexistente_devolve_404(): void
    {
        $this->get('/exercicio/nao-existe')->assertStatus(404);
    }
}
