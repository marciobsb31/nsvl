<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adiciona colunas de perfil institucional e lotação ao usuário.
     * Usado para regras de visibilidade na tela Gerenciar Solicitações.
     *
     * esfera_atuacao: federal | estadual | municipal
     * uf_lotacao: UF de lotação (obrigatório para estadual e municipal)
     * municipio_lotacao: Município de lotação (obrigatório para municipal)
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('esfera_atuacao', 20)->default('federal')->after('role');
            $table->string('uf_lotacao', 2)->nullable()->after('esfera_atuacao');
            $table->string('municipio_lotacao', 100)->nullable()->after('uf_lotacao');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['esfera_atuacao', 'uf_lotacao', 'municipio_lotacao']);
        });
    }
};
