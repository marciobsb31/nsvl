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
        Schema::create('usuario_contato', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->index()->constrained('usuarios');
            $table->foreignId('usuario_abrangencia_id')->nullable()->index()->constrained('usuario_abrangencia');
            $table->string('tipo_contato', 20);
            $table->string('classificacao_contato', 20);
            $table->string('valor', 255);
            $table->boolean('principal');
            $table->string('origem_tipo', 30);
            $table->foreignId('solicitacao_cadastro_origem_id')->nullable()->index('usuario_contato_solicitacao_origem_idx')->constrained('solicitacoes_cadastro');
            $table->boolean('ativo');
            $table->timestamps();

            $table->index(['tipo_contato', 'classificacao_contato'], 'usuario_contato_tipo_classificacao_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('usuario_contato');
    }
};
