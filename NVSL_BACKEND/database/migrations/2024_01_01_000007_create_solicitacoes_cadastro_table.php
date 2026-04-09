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
            $table->string('cpf_hash', 64)->index();
            $table->string('nome', 255);
            $table->string('email_institucional', 255);
            $table->string('telefone_institucional', 20)->nullable();
            $table->string('telefone_pessoal', 20)->nullable();
            $table->string('esfera_atuacao', 20);
            $table->string('uf', 2);
            $table->string('municipio', 100);
            $table->string('orgao', 255);
            $table->string('cargo', 255)->nullable();
            $table->string('status', 30)->default('em_analise');
            $table->timestamps();

            $table->index(['esfera_atuacao', 'uf', 'municipio']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('solicitacoes_cadastro');
    }
};
