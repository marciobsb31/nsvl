<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();

            // Usuário pode ser null (ações pré-autenticação, ex: callback falho)
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            // Ação registrada (ex: auth.login, auth.logout, auth.callback_failed)
            $table->string('action', 100);

            $table->ipAddress('ip_address')->nullable();
            $table->string('user_agent', 500)->nullable();

            // Contexto adicional em JSON (ex: reason de falha — sem dados sensíveis)
            $table->json('context')->nullable();

            // Sem updated_at — registros de auditoria são imutáveis
            $table->timestamp('created_at')->useCurrent();

            // Índices para consultas de auditoria
            $table->index(['action', 'created_at']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
