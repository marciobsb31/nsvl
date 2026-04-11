<?php

namespace Database\Seeders;

use App\Models\Perfil;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Catálogo oficial de perfis ({@see \App\Models\Perfil::CATALOGO_OFICIAL}),
 * alinhado a GET /api/perfis e GET /api/gerenciar-perfis.
 */
class PerfilSeeder extends Seeder
{
    public function run(): void
    {
        $perfis = [
            ['nome' => 'Gestor Federal', 'descricao' => 'Administrador Geral do Sistema', 'ativo' => true],
            ['nome' => 'Gestor Estadual', 'descricao' => 'Gestão operacional no âmbito estadual', 'ativo' => true],
            ['nome' => 'Gestor Municipal', 'descricao' => 'Gestão operacional no âmbito municipal', 'ativo' => true],
            ['nome' => 'Administrador Estadual', 'descricao' => 'Administração e configuração no âmbito estadual', 'ativo' => true],
            ['nome' => 'Administrador Municipal', 'descricao' => 'Administração e configuração no âmbito municipal', 'ativo' => true],
            ['nome' => 'Visitante Federal', 'descricao' => 'Acesso somente leitura no âmbito federal', 'ativo' => true],
            ['nome' => 'Visitante Estadual', 'descricao' => 'Acesso somente leitura no âmbito estadual', 'ativo' => true],
            ['nome' => 'Visitante Municipal', 'descricao' => 'Acesso somente leitura no âmbito municipal', 'ativo' => true],
        ];

        foreach ($perfis as $p) {
            Perfil::updateOrCreate(
                ['nome' => $p['nome']],
                ['descricao' => $p['descricao'], 'ativo' => $p['ativo']]
            );
        }

        DB::transaction(function (): void {
            $this->migrarPerfilLegadoParaNovo('Gestor Nacional', 'Gestor Federal');
            $this->migrarPerfilLegadoParaNovo('Administrador Nacional', 'Visitante Federal');

            Perfil::query()
                ->whereNotIn('nome', Perfil::CATALOGO_OFICIAL)
                ->delete();
        });
    }

    private function migrarPerfilLegadoParaNovo(string $nomeLegado, string $nomeNovo): void
    {
        $perfilLegado = Perfil::query()->where('nome', $nomeLegado)->first();
        $perfilNovo = Perfil::query()->where('nome', $nomeNovo)->first();

        if (!$perfilLegado || !$perfilNovo || $perfilLegado->id === $perfilNovo->id) {
            return;
        }

        if (Schema::hasTable('perfil_usuario')) {
            DB::table('perfil_usuario as pu_old')
                ->where('pu_old.perfil_id', $perfilLegado->id)
                ->whereExists(function ($query) use ($perfilNovo): void {
                    $query->select(DB::raw(1))
                        ->from('perfil_usuario as pu_new')
                        ->whereColumn('pu_new.usuario_id', 'pu_old.usuario_id')
                        ->where('pu_new.perfil_id', $perfilNovo->id);
                })
                ->delete();

            DB::table('perfil_usuario')
                ->where('perfil_id', $perfilLegado->id)
                ->update(['perfil_id' => $perfilNovo->id]);
        }

        if (Schema::hasTable('solicitacoes_cadastro')) {
            DB::table('solicitacoes_cadastro')
                ->where('perfil_id_solicitado', $perfilLegado->id)
                ->update(['perfil_id_solicitado' => $perfilNovo->id]);
        }

        if (Schema::hasTable('perfil_permissao')) {
            DB::table('perfil_permissao as pp_old')
                ->where('pp_old.perfil_id', $perfilLegado->id)
                ->whereExists(function ($query) use ($perfilNovo): void {
                    $query->select(DB::raw(1))
                        ->from('perfil_permissao as pp_new')
                        ->whereColumn('pp_new.permissao_id', 'pp_old.permissao_id')
                        ->where('pp_new.perfil_id', $perfilNovo->id);
                })
                ->delete();

            DB::table('perfil_permissao')
                ->where('perfil_id', $perfilLegado->id)
                ->update(['perfil_id' => $perfilNovo->id]);
        }

        $perfilLegado->delete();
    }
}
