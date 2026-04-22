<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plano_acao_identificacoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('usuarios')->cascadeOnDelete();
            $table->string('orgao_gestor');
            $table->json('secretarias_envolvidas')->nullable();
            $table->date('vigencia_inicio');
            $table->date('vigencia_fim');
            $table->timestamps();

            $table->unique('usuario_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plano_acao_identificacoes');
    }
};
