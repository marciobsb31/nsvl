<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permissoes', function (Blueprint $table) {
            $table->id();
            $table->string('modulo', 100);
            $table->string('acao', 100);
            $table->string('descricao', 255)->nullable();
            $table->timestamps();

            $table->unique(['modulo', 'acao']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permissoes');
    }
};
