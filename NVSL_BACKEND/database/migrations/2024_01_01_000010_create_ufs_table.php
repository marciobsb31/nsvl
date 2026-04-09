<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Estrutura baseada no IBGE (servicodados.ibge.gov.br/api/v1/localidades/estados).
     * Dados replicados no banco; não consumimos o serviço do IBGE em tempo de execução.
     */
    public function up(): void
    {
        Schema::create('ufs', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('id_ibge')->unique()->comment('ID do estado na API IBGE');
            $table->char('sigla', 2)->unique();
            $table->string('nome', 100);
            $table->char('regiao_sigla', 2)->nullable()->comment('Sigla da região (N, NE, SE, S, CO)');
            $table->string('regiao_nome', 50)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ufs');
    }
};
