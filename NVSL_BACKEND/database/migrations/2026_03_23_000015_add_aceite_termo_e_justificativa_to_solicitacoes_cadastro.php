<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('solicitacoes_cadastro', function (Blueprint $table) {
            $table->timestamp('aceite_termo_at')->nullable()->after('status');
            $table->text('justificativa_reprovacao')->nullable()->after('aceite_termo_at');
        });
    }

    public function down(): void
    {
        Schema::table('solicitacoes_cadastro', function (Blueprint $table) {
            $table->dropColumn(['aceite_termo_at', 'justificativa_reprovacao']);
        });
    }
};
