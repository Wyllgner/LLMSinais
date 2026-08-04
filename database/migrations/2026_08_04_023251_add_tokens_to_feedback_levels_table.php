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
        Schema::table('feedback_levels', function (Blueprint $table) {
            $table->unsignedInteger('tokens_entrada')->nullable()->after('segmentos');
            $table->unsignedInteger('tokens_saida')->nullable()->after('tokens_entrada');
            $table->unsignedInteger('tokens_raciocinio')->nullable()->after('tokens_saida');
            $table->unsignedTinyInteger('chamadas')->default(0)->after('tokens_raciocinio');
        });
    }

    public function down(): void
    {
        Schema::table('feedback_levels', function (Blueprint $table) {
            $table->dropColumn(['tokens_entrada', 'tokens_saida', 'tokens_raciocinio', 'chamadas']);
        });
    }
};
