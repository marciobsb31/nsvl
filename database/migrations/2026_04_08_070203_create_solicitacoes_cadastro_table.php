<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('solicitacoes_cadastro', function (Blueprint $table) {
            $table->id();

            $table->foreignId('usuario_id')->constrained('usuarios');
            $table->foreignId('perfil_id')->nullable()->constrained('perfis');
            $table->foreignId('esfera_id')->constrained('esferas');
            $table->foreignId('uf_id')->nullable()->constrained('ufs');
            $table->foreignId('municipio_id')->nullable()->constrained('municipios');

            $table->foreignId('status_id')->constrained('status_solicitacao');

            $table->string('email_institucional');
            $table->string('telefone_institucional');
            $table->string('telefone_pessoal')->nullable();

            $table->string('orgao');
            $table->string('cargo')->nullable();

            $table->date('vigencia_inicio')->nullable();
            $table->date('vigencia_fim')->nullable();

            $table->timestamp('aceite_termo_at')->nullable();
            $table->text('justificativa_reprovacao')->nullable();

            $table->timestamps();

            $table->index(['esfera_id', 'uf_id', 'municipio_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('solicitacoes_cadastro');
    }
};
