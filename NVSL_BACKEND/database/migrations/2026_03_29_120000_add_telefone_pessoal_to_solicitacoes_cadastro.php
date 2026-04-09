<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('solicitacoes_cadastro', function (Blueprint $table) {
            if (! Schema::hasColumn('solicitacoes_cadastro', 'telefone_pessoal')) {
                $table->string('telefone_pessoal', 20)->nullable()->after('telefone_institucional');
            }
        });
    }

    public function down(): void
    {
        Schema::table('solicitacoes_cadastro', function (Blueprint $table) {
            if (Schema::hasColumn('solicitacoes_cadastro', 'telefone_pessoal')) {
                $table->dropColumn('telefone_pessoal');
            }
        });
    }
};
