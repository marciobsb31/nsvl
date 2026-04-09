<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('solicitacoes_cadastro', function (Blueprint $table) {
            $table->foreignId('perfil_id_solicitado')
                ->nullable()
                ->after('cargo')
                ->constrained('perfis')
                ->nullOnDelete();
            $table->date('vigencia_inicio_solicitada')->nullable()->after('perfil_id_solicitado');
            $table->date('vigencia_fim_solicitada')->nullable()->after('vigencia_inicio_solicitada');
        });
    }

    public function down(): void
    {
        Schema::table('solicitacoes_cadastro', function (Blueprint $table) {
            $table->dropConstrainedForeignId('perfil_id_solicitado');
            $table->dropColumn(['vigencia_inicio_solicitada', 'vigencia_fim_solicitada']);
        });
    }
};

