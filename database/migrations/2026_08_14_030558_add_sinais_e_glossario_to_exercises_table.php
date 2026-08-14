<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exercises', function (Blueprint $table) {
            // O texto que vai para o avatar nem sempre e o texto exibido. Uma
            // frase com termo em ingles cai em datilologia, entao ela ganha
            // aqui uma parafrase sinalizavel. A chave e a frase exibida.
            $table->json('sinais')->nullable()->after('exemplo_execucao');

            // Os termos tecnicos do exercicio, explicados um a um.
            $table->json('glossario')->nullable()->after('sinais');
        });
    }

    public function down(): void
    {
        Schema::table('exercises', function (Blueprint $table) {
            $table->dropColumn(['sinais', 'glossario']);
        });
    }
};
