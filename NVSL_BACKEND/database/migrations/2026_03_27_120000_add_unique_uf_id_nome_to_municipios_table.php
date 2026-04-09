<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Unicidade (uf_id, nome) para importação IBGE via upsert.
     */
    public function up(): void
    {
        if (!Schema::hasTable('municipios')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('
                DELETE FROM municipios a
                USING municipios b
                WHERE a.id > b.id
                  AND a.uf_id = b.uf_id
                  AND a.nome IS NOT DISTINCT FROM b.nome
            ');
        }

        Schema::table('municipios', function (Blueprint $table) {
            $table->unique(['uf_id', 'nome'], 'municipios_uf_id_nome_unique');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('municipios')) {
            return;
        }

        Schema::table('municipios', function (Blueprint $table) {
            $table->dropUnique('municipios_uf_id_nome_unique');
        });
    }
};
