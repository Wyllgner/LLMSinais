<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exercises', function (Blueprint $table) {
            // O material que ensina o conceito antes do aluno programar.
            $table->text('conteudo')->nullable()->after('enunciado');
            $table->text('exemplo')->nullable()->after('conteudo');
        });
    }

    public function down(): void
    {
        Schema::table('exercises', function (Blueprint $table) {
            $table->dropColumn(['conteudo', 'exemplo']);
        });
    }
};
