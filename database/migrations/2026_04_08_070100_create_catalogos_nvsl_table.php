<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('esferas', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 50)->unique();
        });

        Schema::create('status_solicitacao', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 50)->unique();
        });

        Schema::create('ufs', function (Blueprint $table) {
            $table->id();
            $table->string('sigla', 2)->unique();
            $table->string('nome', 100);
        });

        Schema::create('municipios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('uf_id')->constrained('ufs')->cascadeOnDelete();
            $table->string('nome', 100);
            $table->unique(['uf_id', 'nome'], 'municipios_uf_id_nome_unique');
        });

        Schema::create('perfis', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 100)->unique();
            $table->string('descricao', 255)->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('perfis');
        Schema::dropIfExists('municipios');
        Schema::dropIfExists('ufs');
        Schema::dropIfExists('status_solicitacao');
        Schema::dropIfExists('esferas');
    }
};
