<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PerfilPermissaoSeeder extends Seeder
{
    public function run(): void
    {
        $perfis = DB::table('perfis')->get()->keyBy('codigo');
        $permissoes = DB::table('permissoes')->get()->keyBy('codigo');

        $vinculos = [];

        if (isset($perfis['admin_federal'])) {
            foreach ($permissoes as $permissao) {
                $vinculos[] = [
                    'perfil_id'    => $perfis['admin_federal']->id,
                    'permissao_id' => $permissao->id,
                ];
            }
        }

        if (isset($perfis['gestor_estadual'])) {
            $codigosPermissao = [
                'usuarios.visualizar',
                'solicitacoes_cadastro.visualizar',
                'solicitacoes_cadastro.editar',
            ];

            foreach ($codigosPermissao as $cod) {
                if (isset($permissoes[$cod])) {
                    $vinculos[] = [
                        'perfil_id'    => $perfis['gestor_estadual']->id,
                        'permissao_id' => $permissoes[$cod]->id,
                    ];
                }
            }
        }

        foreach ($vinculos as $vinculo) {
            DB::table('perfil_permissao')->updateOrInsert($vinculo);
        }
    }
}
