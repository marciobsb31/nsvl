<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // -------------------------------------------------------
        // 1. Criar tabela status_solicitacao
        // -------------------------------------------------------
        Schema::create('status_solicitacao', function (Blueprint $table) {
            $table->id();
            $table->string('nome')->unique();
        });

        DB::table('status_solicitacao')->insert([
            ['id' => 1, 'nome' => 'em_analise'],
            ['id' => 2, 'nome' => 'aprovado'],
            ['id' => 3, 'nome' => 'reprovado'],
        ]);

        // -------------------------------------------------------
        // 2. Renomear users → usuarios e adaptar colunas
        // -------------------------------------------------------
        Schema::rename('users', 'usuarios');

        Schema::table('usuarios', function (Blueprint $table) {
            $table->string('cpf', 11)->nullable()->after('id');
            $table->string('telefone')->nullable()->after('cpf');
        });

        // Converter cpf_hash → cpf (não é possível reverter o hash; limpar e exigir repreenchimento)
        // Em ambiente de desenvolvimento, preencher CPFs conhecidos dos seeders
        DB::table('usuarios')->update(['cpf' => null]);

        Schema::table('usuarios', function (Blueprint $table) {
            $table->renameColumn('name', 'nome');
        });

        // Remover colunas que não existem na nova modelagem
        Schema::table('usuarios', function (Blueprint $table) {
            if (Schema::hasColumn('usuarios', 'role')) {
                $table->dropColumn('role');
            }
        });

        Schema::table('usuarios', function (Blueprint $table) {
            if (Schema::hasColumn('usuarios', 'esfera_atuacao')) {
                $table->dropColumn('esfera_atuacao');
            }
        });

        Schema::table('usuarios', function (Blueprint $table) {
            if (Schema::hasColumn('usuarios', 'uf_lotacao')) {
                $table->dropColumn('uf_lotacao');
            }
        });

        Schema::table('usuarios', function (Blueprint $table) {
            if (Schema::hasColumn('usuarios', 'municipio_lotacao')) {
                $table->dropColumn('municipio_lotacao');
            }
        });

        $this->safeDropForeignKey('usuarios', 'users_perfil_usuario_ativo_id_foreign');
        Schema::table('usuarios', function (Blueprint $table) {
            if (Schema::hasColumn('usuarios', 'perfil_usuario_ativo_id')) {
                $table->dropColumn('perfil_usuario_ativo_id');
            }
        });

        Schema::table('usuarios', function (Blueprint $table) {
            if (Schema::hasColumn('usuarios', 'cpf_hash')) {
                $table->dropColumn('cpf_hash');
            }
            if (Schema::hasColumn('usuarios', 'picture')) {
                $table->dropColumn('picture');
            }
            if (Schema::hasColumn('usuarios', 'email_verified_at')) {
                $table->dropColumn('email_verified_at');
            }
            if (Schema::hasColumn('usuarios', 'remember_token')) {
                $table->dropColumn('remember_token');
            }
        });

        // -------------------------------------------------------
        // 3. Simplificar esferas (remover codigo, ordem)
        //    Primeiro dropar FKs de outras tabelas que dependem de esferas.codigo
        // -------------------------------------------------------
        $this->safeDropForeignKey('perfis', 'perfis_esfera_foreign');
        $this->safeDropForeignKey('solicitacoes_cadastro', 'solicitacoes_cadastro_esfera_atuacao_foreign');
        $this->safeDropForeignKey('perfil_usuario', 'perfil_usuario_esfera_foreign');
        $this->safeDropForeignKey('usuarios', 'users_esfera_atuacao_foreign');

        // Dropar FKs de UF que referenciam ufs.sigla (coluna será removida)
        $this->safeDropForeignKey('usuarios', 'users_uf_lotacao_foreign');
        $this->safeDropForeignKey('perfil_usuario', 'perfil_usuario_uf_foreign');
        $this->safeDropForeignKey('solicitacoes_cadastro', 'solicitacoes_cadastro_uf_foreign');

        Schema::table('esferas', function (Blueprint $table) {
            $drops = array_filter(['codigo', 'ordem', 'created_at', 'updated_at'], fn ($c) => Schema::hasColumn('esferas', $c));
            if ($drops) {
                $table->dropColumn($drops);
            }
        });

        // -------------------------------------------------------
        // 4. Simplificar ufs (remover id_ibge, regiao_sigla, regiao_nome, timestamps)
        // -------------------------------------------------------
        Schema::table('ufs', function (Blueprint $table) {
            $drops = array_filter(['id_ibge', 'regiao_sigla', 'regiao_nome', 'created_at', 'updated_at'], fn ($c) => Schema::hasColumn('ufs', $c));
            if ($drops) {
                $table->dropColumn($drops);
            }
        });

        // Simplificar municipios (remover id_ibge, timestamps)
        Schema::table('municipios', function (Blueprint $table) {
            $drops = array_filter(['id_ibge', 'created_at', 'updated_at'], fn ($c) => Schema::hasColumn('municipios', $c));
            if ($drops) {
                $table->dropColumn($drops);
            }
        });

        // -------------------------------------------------------
        // 5. Adaptar perfis (remover esfera/status, adicionar ativo)
        // -------------------------------------------------------
        Schema::table('perfis', function (Blueprint $table) {
            $table->boolean('ativo')->default(true)->after('descricao');
        });

        // Migrar status → ativo
        DB::table('perfis')->where('status', 'inativo')->update(['ativo' => false]);

        Schema::table('perfis', function (Blueprint $table) {
            if (Schema::hasColumn('perfis', 'esfera')) {
                $table->dropColumn('esfera');
            }
            if (Schema::hasColumn('perfis', 'status')) {
                $table->dropColumn('status');
            }
        });

        // -------------------------------------------------------
        // 6. Simplificar perfil_usuario (remover esfera, uf, municipio, orgao)
        // -------------------------------------------------------
        Schema::table('perfil_usuario', function (Blueprint $table) {
            if (Schema::hasColumn('perfil_usuario', 'esfera')) {
                $table->dropColumn('esfera');
            }
        });

        Schema::table('perfil_usuario', function (Blueprint $table) {
            if (Schema::hasColumn('perfil_usuario', 'uf')) {
                $table->dropColumn('uf');
            }
            if (Schema::hasColumn('perfil_usuario', 'municipio')) {
                $table->dropColumn('municipio');
            }
            if (Schema::hasColumn('perfil_usuario', 'orgao')) {
                $table->dropColumn('orgao');
            }
        });

        // Garantir coluna ativo existe e é boolean
        if (!Schema::hasColumn('perfil_usuario', 'ativo')) {
            Schema::table('perfil_usuario', function (Blueprint $table) {
                $table->boolean('ativo')->default(true)->after('data_fim_vigencia');
            });
        }

        // Atualizar FK de usuario_id para apontar à tabela usuarios
        $this->safeDropForeignKey('perfil_usuario', 'perfil_usuario_usuario_id_foreign');
        $this->safeDropForeignKey('perfil_usuario', 'perfil_usuario_perfil_id_foreign');
        Schema::table('perfil_usuario', function (Blueprint $table) {
            $table->foreign('usuario_id', 'perfil_usuario_user_id_fk')
                ->references('id')->on('usuarios')
                ->onDelete('cascade');
            $table->foreign('perfil_id', 'perfil_usuario_perfil_id_fk')
                ->references('id')->on('perfis')
                ->onDelete('cascade');
        });

        // -------------------------------------------------------
        // 7. Refatorar solicitacoes_cadastro para usar FKs
        // -------------------------------------------------------
        Schema::table('solicitacoes_cadastro', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->after('id');
            $table->unsignedBigInteger('esfera_id')->nullable()->after('email_institucional');
            $table->unsignedBigInteger('uf_id')->nullable()->after('esfera_id');
            $table->unsignedBigInteger('municipio_id')->nullable()->after('uf_id');
            $table->unsignedBigInteger('status_id')->nullable()->after('cargo');
        });

        // Migrar dados de string → FK
        $statusMap = [
            'em_analise' => 1,
            'aprovado'   => 2,
            'reprovado'  => 3,
        ];
        foreach ($statusMap as $nome => $id) {
            DB::table('solicitacoes_cadastro')
                ->where('status', $nome)
                ->update(['status_id' => $id]);
        }

        // Migrar esfera_atuacao string → esfera_id
        $esferas = DB::table('esferas')->pluck('id', 'nome');
        foreach ($esferas as $nome => $id) {
            DB::table('solicitacoes_cadastro')
                ->whereRaw('LOWER(esfera_atuacao) = ?', [strtolower($nome)])
                ->update(['esfera_id' => $id]);
        }

        // Migrar uf string → uf_id
        $ufs = DB::table('ufs')->pluck('id', 'sigla');
        foreach ($ufs as $sigla => $id) {
            DB::table('solicitacoes_cadastro')
                ->where('uf', $sigla)
                ->update(['uf_id' => $id]);
        }

        // Migrar municipio string → municipio_id
        $solicitacoes = DB::table('solicitacoes_cadastro')
            ->whereNotNull('municipio')
            ->where('municipio', '!=', '')
            ->get(['id', 'municipio', 'uf_id']);

        foreach ($solicitacoes as $sol) {
            $mun = DB::table('municipios')
                ->where('nome', $sol->municipio)
                ->where('uf_id', $sol->uf_id)
                ->first();
            if ($mun) {
                DB::table('solicitacoes_cadastro')
                    ->where('id', $sol->id)
                    ->update(['municipio_id' => $mun->id]);
            }
        }

        // Migrar user_id (buscar usuario pelo cpf_hash via tabela auxiliar se possível)
        // Em nova instalação isso não é necessário

        // Remover colunas antigas
        Schema::table('solicitacoes_cadastro', function (Blueprint $table) {
            if (Schema::hasColumn('solicitacoes_cadastro', 'cpf_hash')) {
                $table->dropColumn('cpf_hash');
            }
            if (Schema::hasColumn('solicitacoes_cadastro', 'cpf_exibicao')) {
                $table->dropColumn('cpf_exibicao');
            }
            if (Schema::hasColumn('solicitacoes_cadastro', 'nome')) {
                $table->dropColumn('nome');
            }
            if (Schema::hasColumn('solicitacoes_cadastro', 'telefone_pessoal')) {
                $table->dropColumn('telefone_pessoal');
            }
            if (Schema::hasColumn('solicitacoes_cadastro', 'esfera_atuacao')) {
                $table->dropColumn('esfera_atuacao');
            }
            if (Schema::hasColumn('solicitacoes_cadastro', 'uf')) {
                $table->dropColumn('uf');
            }
            if (Schema::hasColumn('solicitacoes_cadastro', 'municipio')) {
                $table->dropColumn('municipio');
            }
            if (Schema::hasColumn('solicitacoes_cadastro', 'status')) {
                $table->dropColumn('status');
            }
            if (Schema::hasColumn('solicitacoes_cadastro', 'vigencia_inicio_solicitada')) {
                $table->dropColumn('vigencia_inicio_solicitada');
            }
            if (Schema::hasColumn('solicitacoes_cadastro', 'vigencia_fim_solicitada')) {
                $table->dropColumn('vigencia_fim_solicitada');
            }
        });

        // Adicionar FKs (dropar existentes primeiro para evitar duplicatas)
        $this->safeDropForeignKey('solicitacoes_cadastro', 'solicitacoes_cadastro_perfil_id_solicitado_foreign');
        Schema::table('solicitacoes_cadastro', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('usuarios')->onDelete('cascade');
            $table->foreign('esfera_id')->references('id')->on('esferas')->onDelete('restrict');
            $table->foreign('uf_id')->references('id')->on('ufs')->onDelete('restrict');
            $table->foreign('municipio_id')->references('id')->on('municipios')->onDelete('restrict');
            $table->foreign('perfil_id_solicitado')->references('id')->on('perfis')->onDelete('restrict');
            $table->foreign('status_id')->references('id')->on('status_solicitacao')->onDelete('restrict');
        });

        // -------------------------------------------------------
        // 8. Renomear colunas em auditoria_log
        // -------------------------------------------------------
        Schema::table('auditoria_log', function (Blueprint $table) {
            if (Schema::hasColumn('auditoria_log', 'action')) {
                $table->renameColumn('action', 'acao');
            }
        });

        Schema::table('auditoria_log', function (Blueprint $table) {
            if (Schema::hasColumn('auditoria_log', 'context')) {
                $table->renameColumn('context', 'contexto');
            }
        });

        // Atualizar FK user_id → usuarios
        $this->safeDropForeignKey('auditoria_log', 'audit_logs_user_id_foreign');
        $this->safeDropForeignKey('auditoria_log', 'auditoria_log_user_id_foreign');
        Schema::table('auditoria_log', function (Blueprint $table) {
            $table->foreign('user_id', 'auditoria_log_user_fk')
                ->references('id')->on('usuarios')
                ->onDelete('set null');
        });

        // -------------------------------------------------------
        // 9. Remover tabelas permissoes e perfil_permissao
        // -------------------------------------------------------
        Schema::dropIfExists('perfil_permissao');
        Schema::dropIfExists('permissoes');

        // -------------------------------------------------------
        // 10. Atualizar personal_access_tokens FK
        // -------------------------------------------------------
        if (Schema::hasTable('personal_access_tokens')) {
            // A tabela Sanctum usa morph (tokenable_type/tokenable_id), não FK direta
            // Precisamos apenas atualizar o tokenable_type
            DB::table('personal_access_tokens')
                ->where('tokenable_type', 'App\\Models\\User')
                ->update(['tokenable_type' => 'App\\Models\\Usuario']);
        }
    }

    public function down(): void
    {
        // Rollback complexo — recriar tabelas removidas e reverter renomeações
        // Em ambiente de desenvolvimento, é mais seguro fazer fresh migration
        Schema::dropIfExists('status_solicitacao');

        if (Schema::hasTable('usuarios')) {
            Schema::rename('usuarios', 'users');
        }
    }

    private function hasForeignKey(string $table, string $foreignKeyName): bool
    {
        try {
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $foreignKeys = $sm->listTableForeignKeys($table);
            foreach ($foreignKeys as $fk) {
                if ($fk->getName() === $foreignKeyName) {
                    return true;
                }
            }
        } catch (\Throwable) {
        }
        return false;
    }

    private function safeDropForeignKey(string $table, string $constraintName): void
    {
        try {
            DB::statement("ALTER TABLE \"{$table}\" DROP CONSTRAINT IF EXISTS \"{$constraintName}\"");
        } catch (\Throwable) {
        }
    }
};
