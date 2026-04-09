<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adiciona cpf_exibicao para exibir CPF fictício em ambiente de desenvolvimento/teste.
     * Formato: 111.222.333-44 (mascarado ou completo conforme necessidade).
     */
    public function up(): void
    {
        Schema::table('solicitacoes_cadastro', function (Blueprint $table) {
            $table->string('cpf_exibicao', 14)->nullable()->after('cpf_hash');
        });
    }

    public function down(): void
    {
        Schema::table('solicitacoes_cadastro', function (Blueprint $table) {
            $table->dropColumn('cpf_exibicao');
        });
    }
};
