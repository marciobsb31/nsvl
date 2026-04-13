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
            $table->foreignId('perfil_id_solicitado')->nullable()->constrained('perfis');
            $table->foreignId('esfera_id_solicitada')->constrained('esferas');
            $table->foreignId('uf_id_solicitada')->nullable()->constrained('ufs');
            $table->foreignId('municipio_id_solicitada')->nullable()->constrained('municipios');

            $table->foreignId('status_id')->constrained('status_solicitacao');

            $table->string('email_institucional');
            $table->string('telefone_institucional');
            $table->string('telefone_pessoal')->nullable();

            $table->string('orgao');
            $table->string('cargo')->nullable();

            $table->date('vigencia_inicio_solicitada')->nullable();
            $table->date('vigencia_fim_solicitada')->nullable();

            $table->timestamp('aceite_termo_at')->nullable();
            $table->text('justificativa_reprovacao')->nullable();

            $table->timestamps();

            $table->index(['esfera_id_solicitada', 'uf_id_solicitada', 'municipio_id_solicitada']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('solicitacoes_cadastro');
    }
};
