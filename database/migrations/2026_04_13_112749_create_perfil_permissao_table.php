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
        Schema::create('perfil_permissao', function (Blueprint $table) {
            $table->id();
            $table->foreignId('perfil_id')->constrained('perfis');
            $table->foreignId('permissao_id')->constrained('permissoes');
            $table->boolean('ativo')->default(true);
            $table->timestamps();

            $table->unique(['perfil_id', 'permissao_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('perfil_permissao');
    }
};
