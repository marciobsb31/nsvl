<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plano_acao_envios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->unique()->constrained('usuarios')->onDelete('cascade');
            $table->string('responsavel_nome', 255)->nullable();
            $table->string('responsavel_cargo', 255)->nullable();
            $table->string('responsavel_orgao', 255)->nullable();
            $table->string('responsavel_contato', 255)->nullable();
            $table->text('justificativa_eixo_1')->nullable();
            $table->text('justificativa_eixo_2')->nullable();
            $table->text('justificativa_eixo_3')->nullable();
            $table->text('justificativa_eixo_4')->nullable();
            $table->string('status', 50)->default('rascunho');
            $table->timestamp('enviado_em')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plano_acao_envios');
    }
};
