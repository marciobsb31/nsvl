<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('solicitacoes_cadastro', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('usuarios')->cascadeOnDelete();
            $table->string('email_institucional');
            $table->string('telefone_institucional', 20);
            $table->string('telefone_pessoal', 20)->nullable();
            $table->foreignId('esfera_id')->constrained('esferas')->restrictOnDelete();
            $table->foreignId('uf_id')->constrained('ufs')->restrictOnDelete();
            $table->foreignId('municipio_id')->nullable()->constrained('municipios')->restrictOnDelete();
            $table->string('orgao');
            $table->string('cargo')->nullable();
            $table->foreignId('perfil_id_solicitado')->nullable()->constrained('perfis')->nullOnDelete();
            $table->date('vigencia_inicio_solicitada')->nullable();
            $table->date('vigencia_fim_solicitada')->nullable();
            $table->foreignId('status_id')->constrained('status_solicitacao')->restrictOnDelete();
            $table->timestamp('aceite_termo_at')->nullable();
            $table->text('justificativa_reprovacao')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status_id'], 'solicitacoes_cadastro_user_status_idx');
            $table->index(['esfera_id', 'uf_id', 'municipio_id'], 'solicitacoes_cadastro_geo_idx');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement(
                'ALTER TABLE solicitacoes_cadastro
                 ADD CONSTRAINT solicitacoes_cadastro_vigencia_chk
                 CHECK (
                    vigencia_fim_solicitada IS NULL
                    OR vigencia_inicio_solicitada IS NULL
                    OR vigencia_fim_solicitada >= vigencia_inicio_solicitada
                 )'
            );
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement(
                'ALTER TABLE solicitacoes_cadastro
                 DROP CONSTRAINT IF EXISTS solicitacoes_cadastro_vigencia_chk'
            );
        }

        Schema::dropIfExists('solicitacoes_cadastro');
    }
};
