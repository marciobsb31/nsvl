<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::rename('audit_logs', 'auditoria_log');

        Schema::table('auditoria_log', function (Blueprint $table) {
            $table->string('tipo_operacao', 50)->default('view')->after('action');
            $table->string('tabela_afetada', 100)->nullable()->after('tipo_operacao');
            $table->unsignedBigInteger('registro_id')->nullable()->after('tabela_afetada');
        });
    }

    public function down(): void
    {
        Schema::table('auditoria_log', function (Blueprint $table) {
            $table->dropColumn(['tipo_operacao', 'tabela_afetada', 'registro_id']);
        });

        Schema::rename('auditoria_log', 'audit_logs');
    }
};
