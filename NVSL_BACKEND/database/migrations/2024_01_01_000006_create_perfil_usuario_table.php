<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('perfil_usuario', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('perfil_id')->constrained('perfis')->cascadeOnDelete();
            $table->date('data_inicio_vigencia')->nullable();
            $table->date('data_fim_vigencia')->nullable();
            $table->timestamps();

            $table->index(['usuario_id', 'data_inicio_vigencia', 'data_fim_vigencia']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('perfil_usuario');
    }
};
