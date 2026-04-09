<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS perfil_usuario_um_ativo_por_usuario_idx');
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement(
                'CREATE UNIQUE INDEX perfil_usuario_um_ativo_por_usuario_idx
                 ON perfil_usuario (usuario_id)
                 WHERE ativo = true'
            );
        }
    }
};
