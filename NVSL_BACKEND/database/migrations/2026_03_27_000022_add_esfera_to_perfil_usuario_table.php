<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Esfera do vínculo usuário–perfil alinhada a perfis.esfera (denormalizada para FK direta em esferas).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('perfil_usuario', function (Blueprint $table) {
            $table->string('esfera', 20)->nullable();
        });

        $rows = DB::table('perfil_usuario')
            ->join('perfis', 'perfis.id', '=', 'perfil_usuario.perfil_id')
            ->select('perfil_usuario.id', 'perfis.esfera as esfera_codigo')
            ->get();

        foreach ($rows as $row) {
            DB::table('perfil_usuario')->where('id', $row->id)->update(['esfera' => $row->esfera_codigo]);
        }

        if (DB::table('perfil_usuario')->whereNull('esfera')->exists()) {
            throw new \RuntimeException(
                'perfil_usuario: existem vínculos sem esfera após o backfill (perfil_id inválido ou perfis.esfera vazio).'
            );
        }

        $driver = DB::getDriverName();
        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE perfil_usuario ALTER COLUMN esfera SET NOT NULL');
        } elseif ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement('ALTER TABLE perfil_usuario MODIFY esfera VARCHAR(20) NOT NULL');
        }
        // sqlite (testes): coluna permanece nullable; o modelo sempre preenche esfera.

        Schema::table('perfil_usuario', function (Blueprint $table) {
            $table->foreign('esfera')
                ->references('codigo')
                ->on('esferas')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('perfil_usuario', function (Blueprint $table) {
            $table->dropForeign(['esfera']);
        });

        Schema::table('perfil_usuario', function (Blueprint $table) {
            $table->dropColumn('esfera');
        });
    }
};
