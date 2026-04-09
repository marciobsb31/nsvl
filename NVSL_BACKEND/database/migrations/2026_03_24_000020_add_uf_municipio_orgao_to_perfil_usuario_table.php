<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('perfil_usuario', function (Blueprint $table) {
            if (!Schema::hasColumn('perfil_usuario', 'uf')) {
                $table->string('uf', 2)->nullable()->after('data_fim_vigencia');
            }
            if (!Schema::hasColumn('perfil_usuario', 'municipio')) {
                $table->string('municipio', 100)->nullable()->after('uf');
            }
            if (!Schema::hasColumn('perfil_usuario', 'orgao')) {
                $table->string('orgao', 150)->nullable()->after('municipio');
            }
        });
    }

    public function down(): void
    {
        Schema::table('perfil_usuario', function (Blueprint $table) {
            $table->dropColumn(['uf', 'municipio', 'orgao']);
        });
    }
};
