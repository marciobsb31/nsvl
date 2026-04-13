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

            // RN03: Acesso a todas as funcionalidades (com restrição de escopo no código)
            'gestor_estadual' => [
                'usuarios.visualizar', 'usuarios.editar',
                'solicitacoes_cadastro.visualizar', 'solicitacoes_cadastro.editar', 'solicitacoes_cadastro.analisar',
                'plano_acao.visualizar', 'plano_acao.cadastrar', 'plano_acao.editar', 'plano_acao.enviar',
                'relatorio_execucao.visualizar', 'relatorio_execucao.enviar',
            ],

            'gestor_municipal' => [
                'usuarios.visualizar', 'usuarios.editar',
                'solicitacoes_cadastro.visualizar', 'solicitacoes_cadastro.editar', 'solicitacoes_cadastro.analisar',
                'plano_acao.visualizar', 'plano_acao.cadastrar', 'plano_acao.editar', 'plano_acao.enviar',
                'relatorio_execucao.visualizar', 'relatorio_execucao.enviar',
            ],

            'admin_estadual' => [
                'usuarios.visualizar',
                'plano_acao.visualizar', 'plano_acao.cadastrar', 'plano_acao.editar',
                'relatorio_execucao.visualizar',
            ],

            'admin_municipal' => [
                'usuarios.visualizar',
                'plano_acao.visualizar', 'plano_acao.cadastrar', 'plano_acao.editar',
                'relatorio_execucao.visualizar',
            ],

            'visitante_federal' => [
                'usuarios.visualizar',
                'solicitacoes_cadastro.visualizar',
                'plano_acao.visualizar',
                'relatorio_execucao.visualizar',
            ],

            'visitante_estadual' => [
                'usuarios.visualizar',
                'plano_acao.visualizar',
                'relatorio_execucao.visualizar',
            ],

            'visitante_municipal' => [
                'usuarios.visualizar',
                'plano_acao.visualizar',
                'relatorio_execucao.visualizar',
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
