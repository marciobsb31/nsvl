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
        Schema::create('usuario_contexto', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->unique('usuario_contexto_usuario_unique')->constrained('usuarios');
            $table->foreignId('perfil_usuario_id')->nullable()->index()->constrained('perfil_usuario');
            $table->foreignId('usuario_abrangencia_id')->nullable()->index()->constrained('usuario_abrangencia');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('usuario_contexto');
    }
};
