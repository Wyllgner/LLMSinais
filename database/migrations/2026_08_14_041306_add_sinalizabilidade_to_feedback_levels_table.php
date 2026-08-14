<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('feedback_levels', function (Blueprint $table) {
            // O veredito do portao, guardado com a dica. Sem isto o relatorio
            // teria de reavaliar tudo depois, contra um lexico ja mudado.
            $table->float('sinalizabilidade')->nullable()->after('segmentos');
            $table->json('violacoes')->nullable()->after('sinalizabilidade');
        });
    }

    public function down(): void
    {
        Schema::table('feedback_levels', function (Blueprint $table) {
            $table->dropColumn(['sinalizabilidade', 'violacoes']);
        });
    }
};
