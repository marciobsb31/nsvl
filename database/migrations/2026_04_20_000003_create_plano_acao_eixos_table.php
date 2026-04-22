<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plano_acao_eixos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('usuarios')->onDelete('cascade');
            $table->tinyInteger('eixo_numero');
            $table->json('acoes')->nullable();
            $table->timestamps();

            $table->unique(['usuario_id', 'eixo_numero']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plano_acao_eixos');
    }
};
