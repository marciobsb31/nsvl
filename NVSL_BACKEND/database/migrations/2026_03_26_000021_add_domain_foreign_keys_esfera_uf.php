<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Database\Seeders\EsferaSeeder;

/**
 * Relacionamentos de domínio alinhados às funcionalidades (esfera de negócio, UF cadastral).
 *
 * - esfera / esfera_atuacao → esferas.codigo
 * - uf / uf_lotacao → ufs.sigla (somente se ufs estiver populada e sem valores órfãos — ex.: após localidades:importar-ibge)
 */
return new class extends Migration
{
    public function up(): void
    {
        (new EsferaSeeder)->run();

        Schema::table('perfis', function (Blueprint $table) {
            $table->foreign('esfera')
                ->references('codigo')
                ->on('esferas')
                ->restrictOnDelete();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreign('esfera_atuacao')
                ->references('codigo')
                ->on('esferas')
                ->restrictOnDelete();
        });

        Schema::table('solicitacoes_cadastro', function (Blueprint $table) {
            $table->foreign('esfera_atuacao')
                ->references('codigo')
                ->on('esferas')
                ->restrictOnDelete();
        });

        if (!$this->podeCriarForeignKeysUf()) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->foreign('uf_lotacao')
                ->references('sigla')
                ->on('ufs')
                ->nullOnDelete();
        });

        Schema::table('perfil_usuario', function (Blueprint $table) {
            $table->foreign('uf')
                ->references('sigla')
                ->on('ufs')
                ->nullOnDelete();
        });

        Schema::table('solicitacoes_cadastro', function (Blueprint $table) {
            $table->foreign('uf')
                ->references('sigla')
                ->on('ufs')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        $this->safeDropForeign('solicitacoes_cadastro', ['uf']);
        $this->safeDropForeign('solicitacoes_cadastro', ['esfera_atuacao']);
        $this->safeDropForeign('perfil_usuario', ['uf']);
        $this->safeDropForeign('users', ['uf_lotacao']);
        $this->safeDropForeign('users', ['esfera_atuacao']);
        $this->safeDropForeign('perfis', ['esfera']);
    }

    private function safeDropForeign(string $table, array $columns): void
    {
        if (!Schema::hasTable($table)) {
            return;
        }
        try {
            Schema::table($table, function (Blueprint $blueprint) use ($columns) {
                $blueprint->dropForeign($columns);
            });
        } catch (\Throwable) {
            // FK não criada (ex.: UF condicional) ou ambiente sem constraint com esse nome
        }
    }

    private function podeCriarForeignKeysUf(): bool
    {
        if (!Schema::hasTable('ufs')) {
            return false;
        }

        if (DB::table('ufs')->count() === 0) {
            return false;
        }

        $orphansUsers = DB::table('users')
            ->whereNotNull('uf_lotacao')
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('ufs')
                    ->whereColumn('ufs.sigla', 'users.uf_lotacao');
            })
            ->exists();

        $orphansPu = DB::table('perfil_usuario')
            ->whereNotNull('uf')
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('ufs')
                    ->whereColumn('ufs.sigla', 'perfil_usuario.uf');
            })
            ->exists();

        $orphansSol = DB::table('solicitacoes_cadastro')
            ->whereNotNull('uf')
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('ufs')
                    ->whereColumn('ufs.sigla', 'solicitacoes_cadastro.uf');
            })
            ->exists();

        return !$orphansUsers && !$orphansPu && !$orphansSol;
    }
};
