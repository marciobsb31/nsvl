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
            ['codigo' => 'usuarios', 'nome' => 'Gestão de Usuários', 'descricao' => 'Controle de usuários e dados básicos.'],
            ['codigo' => 'solicitacoes_cadastro', 'nome' => 'Solicitações de Cadastro', 'descricao' => 'Análise de novos membros.'],
            ['codigo' => 'perfis', 'nome' => 'Perfis e Permissões', 'descricao' => 'Níveis de autorização.'],
            ['codigo' => 'abrangencias', 'nome' => 'Abrangências', 'descricao' => 'Limites geográficos.'],
            ['codigo' => 'auditoria', 'nome' => 'Auditoria', 'descricao' => 'Logs do sistema.'],
            ['codigo' => 'plano_acao', 'nome' => 'Plano de Ação', 'descricao' => 'Gestão e envio de planos de ação.'],
            ['codigo' => 'relatorio_execucao', 'nome' => 'Relatório de Execução', 'descricao' => 'Relatórios de prestação de contas.'],
            ['codigo' => 'relatorios', 'nome' => 'Relatórios', 'descricao' => 'Relatórios'],
        ];

        foreach ($recursos as $recurso) {
            DB::table('recursos')->updateOrInsert(
                ['codigo' => $recurso['codigo']],
                array_merge($recurso, [
                    'ativo'      => true,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ])
            );
        }
    }
}
