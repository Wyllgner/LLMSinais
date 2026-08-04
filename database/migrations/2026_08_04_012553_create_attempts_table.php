<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('attempts', function (Blueprint $table) {
            $table->id();
            $table->string('sessao_uuid')->index();
            $table->foreignId('exercise_id')->constrained()->cascadeOnDelete();
            $table->longText('codigo');
            $table->boolean('passou')->default(false);
            $table->string('tipo_erro')->nullable();
            $table->text('stderr_bruto')->nullable();
            $table->text('erro_simplificado')->nullable();
            $table->json('resultado_testes')->nullable();
            $table->unsignedTinyInteger('nivel_dica')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attempts');
    }
};
