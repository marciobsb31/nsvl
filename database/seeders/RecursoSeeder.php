<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RecursoSeeder extends Seeder
{
    public function run(): void
    {
        $recursos = [
            [
                'codigo'    => 'usuarios',
                'nome'      => 'Gestão de Usuários',
                'descricao' => 'Controle de usuários do sistema e seus dados básicos.',
                'ativo'     => true,
            ],
            [
                'codigo'    => 'solicitacoes_cadastro',
                'nome'      => 'Solicitações de Cadastro',
                'descricao' => 'Fluxo de aprovação e análise de novos cadastros.',
                'ativo'     => true,
            ],
            [
                'codigo'    => 'perfis',
                'nome'      => 'Perfis e Permissões',
                'descricao' => 'Configuração de perfis de acesso e níveis de autorização.',
                'ativo'     => true,
            ],
            [
                'codigo'    => 'abrangencias',
                'nome'      => 'Abrangências e Escopos',
                'descricao' => 'Gestão de limites geográficos (Goiás, Brasília, etc).',
                'ativo'     => true,
            ],
            [
                'codigo'    => 'auditoria',
                'nome'      => 'Logs de Auditoria',
                'descricao' => 'Rastreabilidade de ações realizadas no sistema.',
                'ativo'     => true,
            ],
        ];

        foreach ($recursos as $recurso) {
            DB::table('recursos')->updateOrInsert(
                ['codigo' => $recurso['codigo']],
                array_merge($recurso, [
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ])
            );
        }
    }
}
