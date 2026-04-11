<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('perfil_usuario')) {
            return;
        }

        Schema::table('perfil_usuario', function (Blueprint $table) {
            if (!Schema::hasColumn('perfil_usuario', 'esfera')) {
                $table->string('esfera', 20)->nullable()->after('data_fim_vigencia');
            }
            if (!Schema::hasColumn('perfil_usuario', 'uf')) {
                $table->string('uf', 2)->nullable()->after('esfera');
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
        if (!Schema::hasTable('perfil_usuario')) {
            return;
        }

        Schema::table('perfil_usuario', function (Blueprint $table) {
            $drops = [];
            foreach (['esfera', 'uf', 'municipio', 'orgao'] as $column) {
                if (Schema::hasColumn('perfil_usuario', $column)) {
                    $drops[] = $column;
                }
            }

            if (!empty($drops)) {
                $table->dropColumn($drops);
            }
        });
    }
};
