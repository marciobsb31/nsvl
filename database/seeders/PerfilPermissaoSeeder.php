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

        $map = [
            'gestor_federal'  => $permissoes->keys()->toArray(),
            'gestor_estadual' => [
                'usuarios.visualizar',
                'solicitacoes_cadastro.visualizar',
                'solicitacoes_cadastro.editar',
            ],
            'gestor_municipal' => [
                'usuarios.visualizar',
                'solicitacoes_cadastro.visualizar',
            ],
            'admin_estadual' => [
                'usuarios.visualizar',
                'usuarios.editar',
            ],
            'admin_municipal' => [
                'usuarios.visualizar',
            ],
            'visitante_federal' => [
                'usuarios.visualizar',
            ],
            'visitante_estadual' => [
                'usuarios.visualizar',
            ],
            'visitante_municipal' => [
                'usuarios.visualizar',
            ],
        ];

        foreach ($map as $codigoPerfil => $codigosPermissao) {

            if (! isset($perfis[$codigoPerfil])) {
                continue;
            }

            $perfilId = $perfis[$codigoPerfil]->id;

            foreach ($codigosPermissao as $codigoPermissao) {

                if (! isset($permissoes[$codigoPermissao])) {
                    continue;
                }

                DB::table('perfil_permissao')->updateOrInsert(
                    [
                        'perfil_id'    => $perfilId,
                        'permissao_id' => $permissoes[$codigoPermissao]->id,
                    ],
                    [
                        'ativo'      => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        }
    }
}
