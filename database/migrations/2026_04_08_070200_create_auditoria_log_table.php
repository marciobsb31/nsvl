<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auditoria_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->string('acao', 150);
            $table->string('tipo_operacao', 30);
            $table->string('tabela_afetada', 100)->nullable();
            $table->unsignedBigInteger('registro_id')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('contexto')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['tabela_afetada', 'registro_id'], 'auditoria_log_tabela_registro_idx');
            $table->index(['user_id', 'created_at'], 'auditoria_log_user_created_idx');
            $table->index(['tipo_operacao', 'created_at'], 'auditoria_log_tipo_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auditoria_log');
    }
};
