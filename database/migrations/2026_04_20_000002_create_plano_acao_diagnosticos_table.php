<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plano_acao_diagnosticos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->unique()->constrained('usuarios')->onDelete('cascade');
            $table->text('caracterizacao_populacao')->nullable();
            $table->json('barreiras_urbanisticas')->nullable();
            $table->json('barreiras_transportes')->nullable();
            $table->json('barreiras_atitudinais')->nullable();
            $table->json('barreiras_arquitetonicas')->nullable();
            $table->json('barreiras_comunicacoes')->nullable();
            $table->json('barreiras_tecnologicas')->nullable();
            $table->text('outras_barreiras')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plano_acao_diagnosticos');
    }
};
