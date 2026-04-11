<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('perfil_usuario', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('usuarios')->cascadeOnDelete();
            $table->foreignId('perfil_id')->constrained('perfis')->restrictOnDelete();
            $table->date('data_inicio_vigencia')->nullable();
            $table->date('data_fim_vigencia')->nullable();
            $table->boolean('ativo')->default(false);
            $table->timestamps();

            $table->unique(['usuario_id', 'perfil_id'], 'perfil_usuario_usuario_perfil_unique');
            $table->index(['usuario_id', 'ativo'], 'perfil_usuario_usuario_ativo_idx');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement(
                'ALTER TABLE perfil_usuario
                 ADD CONSTRAINT perfil_usuario_vigencia_chk
                 CHECK (
                    data_fim_vigencia IS NULL
                    OR data_inicio_vigencia IS NULL
                    OR data_fim_vigencia >= data_inicio_vigencia
                 )'
            );

            DB::statement(
                'CREATE UNIQUE INDEX perfil_usuario_um_ativo_por_usuario_idx
                 ON perfil_usuario (usuario_id)
                 WHERE ativo = true'
            );
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS perfil_usuario_um_ativo_por_usuario_idx');
            DB::statement('ALTER TABLE perfil_usuario DROP CONSTRAINT IF EXISTS perfil_usuario_vigencia_chk');
        }

        Schema::dropIfExists('perfil_usuario');
    }
};
