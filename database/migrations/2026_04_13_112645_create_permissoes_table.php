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
        Schema::create('permissoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recurso_id')->constrained('recursos');
            $table->foreignId('acao_id')->constrained('acoes');
            $table->string('codigo', 160)->unique();
            $table->string('nome', 160);
            $table->string('descricao')->nullable();
            $table->boolean('ativo');

            $table->timestamps();

            $table->unique(['recurso_id', 'acao_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('permissoes');
    }
};
