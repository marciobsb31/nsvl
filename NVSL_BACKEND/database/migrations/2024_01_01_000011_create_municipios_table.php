<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Estrutura baseada no IBGE (servicodados.ibge.gov.br/api/v1/localidades/estados/{UF}/municipios).
     * Dados replicados no banco; não consumimos o serviço do IBGE em tempo de execução.
     */
    public function up(): void
    {
        Schema::create('municipios', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('id_ibge')->unique()->comment('ID do município na API IBGE');
            $table->string('nome', 150);
            $table->foreignId('uf_id')->constrained('ufs')->cascadeOnDelete();
            $table->timestamps();
            $table->index(['uf_id', 'nome']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('municipios');
    }
};
