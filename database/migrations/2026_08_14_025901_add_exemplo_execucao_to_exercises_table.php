<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exercises', function (Blueprint $table) {
            // O que a pessoa digita e o que o programa mostra. Fica fora do
            // enunciado porque nao e texto para traduzir, e sim demonstracao.
            $table->text('exemplo_execucao')->nullable()->after('enunciado');
        });
    }

    public function down(): void
    {
        Schema::table('exercises', function (Blueprint $table) {
            $table->dropColumn('exemplo_execucao');
        });
    }
};
