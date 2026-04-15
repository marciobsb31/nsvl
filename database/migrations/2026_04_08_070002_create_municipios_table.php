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
        Schema::create('municipios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('uf_id')->constrained('ufs');
            $table->string('nome', 100);
            $table->string('codigo_ibge', 7)->unique();
            $table->timestamps();
            $table->unique(['id', 'uf_id'], 'municipios_id_uf_unique');
            $table->unique(['uf_id', 'nome'], 'municipios_uf_nome_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('municipios');
    }
};
