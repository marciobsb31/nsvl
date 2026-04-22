<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plano_acao_anexos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('usuarios')->onDelete('cascade');
            $table->string('nome_original', 255);
            $table->string('nome_armazenado', 255);
            $table->string('tipo_mime', 100);
            $table->unsignedBigInteger('tamanho_bytes');
            $table->string('caminho', 500);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plano_acao_anexos');
    }
};
