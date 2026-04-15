<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('perfil_usuario', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('usuarios');
            $table->foreignId('perfil_id')->index()->constrained('perfis');
            $table->foreignId('usuario_abrangencia_id')->nullable();
            $table->date('data_inicio_vigencia')->nullable();
            $table->date('data_fim_vigencia')->nullable();
            $table->string('origem_tipo', 30);
            $table->foreignId('solicitacao_cadastro_origem_id')->nullable()->index()->constrained('solicitacoes_cadastro');
            $table->unsignedBigInteger('atribuido_por_usuario_id')->nullable();
            $table->boolean('ativo');
            $table->timestamps();

            $table->foreign('atribuido_por_usuario_id')->references('id')->on('usuarios');
            $table->unique(['id', 'usuario_id'], 'perfil_usuario_id_usuario_unique');
            $table->unique(['usuario_id', 'perfil_id'], 'perfil_usuario_usuario_perfil_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('perfil_usuario');
    }
};
