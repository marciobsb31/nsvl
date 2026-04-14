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
        Schema::create('usuario_abrangencia', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('usuarios');
            $table->foreignId('esfera_id')->constrained('esferas');
            $table->foreignId('uf_id')->nullable()->constrained('ufs');
            $table->foreignId('municipio_id')->nullable()->constrained('municipios');
            $table->string('nome', 150);
            $table->string('origem_tipo', 30);
            $table->foreignId('solicitacao_cadastro_origem_id')->nullable()->index('usuario_abrangencia_solicitacao_origem_idx')->constrained('solicitacoes_cadastro');
            $table->unsignedBigInteger('criado_por_usuario_id')->nullable();
            $table->boolean('ativo');
            $table->timestamps();

            $table->foreign('criado_por_usuario_id')->references('id')->on('usuarios');
            $table->index(['esfera_id', 'uf_id', 'municipio_id'], 'usuario_abrangencia_geo_idx');
            $table->index(['usuario_id', 'ativo'], 'usuario_abrangencia_usuario_ativo_idx');
            $table->unique(['id', 'usuario_id'], 'usuario_abrangencia_id_usuario_unique');
            $table->unique(['usuario_id', 'esfera_id', 'uf_id', 'municipio_id'], 'usuario_abrangencia_usuario_geo_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('usuario_abrangencia');
    }
};
