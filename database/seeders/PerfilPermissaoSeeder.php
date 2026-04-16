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
            'gestor_federal' => $permissoes->keys()->toArray(),
            'gestor_estadual' => $permissoes->keys()->toArray(),
            'gestor_municipal' => $permissoes->keys()->toArray(),

            'admin_estadual' => [
                'plano_acao.visualizar', 'plano_acao.cadastrar', 'plano_acao.editar',
                'relatorio_execucao.visualizar',
                'relatorios.visualizar',
            ],

            'admin_municipal' => [
                'plano_acao.visualizar', 'plano_acao.cadastrar', 'plano_acao.editar',
                'relatorio_execucao.visualizar',
                'relatorios.visualizar',
            ],

            'visitante_federal' => [
                'usuarios.visualizar',
                'solicitacoes_cadastro.visualizar',
                'plano_acao.visualizar',
                'relatorio_execucao.visualizar',
            ],

            'visitante_estadual' => [
                'plano_acao.visualizar',
                'relatorio_execucao.visualizar',
                'relatorios.visualizar',
            ],

            'visitante_municipal' => [
                'plano_acao.visualizar',
                'relatorio_execucao.visualizar',
                'relatorios.visualizar',
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
