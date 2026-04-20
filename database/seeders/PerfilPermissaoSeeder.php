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

        // Permissões funcionais do sistema (exclui gestão de perfis — não disponível no MVP)
        $permissoesSemPerfis = $permissoes
            ->filter(fn ($p) => ! str_starts_with($p->codigo, 'perfis.'))
            ->keys()
            ->toArray();

        $map = [
            'gestor_federal'   => $permissoesSemPerfis,
            'gestor_estadual'  => $permissoesSemPerfis,
            'gestor_municipal' => $permissoesSemPerfis,

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
                'solicitacoes_cadastro.visualizar',
                'plano_acao.visualizar',
                'relatorio_execucao.visualizar',
                'relatorios.visualizar',
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
