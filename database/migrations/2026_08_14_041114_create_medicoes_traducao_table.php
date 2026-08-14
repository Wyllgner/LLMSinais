<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * O que o avatar fez com cada trecho enviado. E a base empirica do indice
     * de sinalizabilidade: sem medir, o indice seria so uma heuristica.
     */
    public function up(): void
    {
        Schema::create('medicoes_traducao', function (Blueprint $table) {
            $table->id();
            $table->string('sessao_uuid')->index();

            // De onde veio o texto: conteudo, enunciado, glossario, erro
            // simplificado, dica ou stderr bruto. Permite comparar a
            // sinalizabilidade ao longo da cadeia.
            $table->string('origem')->index();

            $table->text('texto');
            $table->text('glosa')->nullable();
            $table->unsignedSmallInteger('sinais')->default(0);

            // Os sinais que o avatar nao encontrou e soletrou letra por letra.
            $table->json('soletrados')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medicoes_traducao');
    }
};
