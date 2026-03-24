<?php

namespace Database\Seeders;

use App\Models\Permissao;
use Illuminate\Database\Seeder;

class PermissaoSeeder extends Seeder
{
    public function run(): void
    {
        $permissoes = [
            ['modulo' => 'Gerenciar Cadastros',  'acao' => 'Visualizar',  'descricao' => 'Visualizar solicitações de cadastro'],
            ['modulo' => 'Gerenciar Cadastros',  'acao' => 'Criar',       'descricao' => 'Cadastrar novas solicitações'],
            ['modulo' => 'Gerenciar Cadastros',  'acao' => 'Aprovar',     'descricao' => 'Aprovar solicitações de cadastro'],
            ['modulo' => 'Gerenciar Cadastros',  'acao' => 'Reprovar',    'descricao' => 'Reprovar solicitações de cadastro'],

            ['modulo' => 'Gerenciar Perfis',     'acao' => 'Visualizar',  'descricao' => 'Visualizar perfis do sistema'],
            ['modulo' => 'Gerenciar Perfis',     'acao' => 'Criar',       'descricao' => 'Cadastrar novos perfis'],
            ['modulo' => 'Gerenciar Perfis',     'acao' => 'Editar',      'descricao' => 'Editar perfis existentes'],

            ['modulo' => 'Relatórios',           'acao' => 'Visualizar',  'descricao' => 'Acessar e gerar relatórios'],
            ['modulo' => 'Relatórios',           'acao' => 'Exportar',    'descricao' => 'Exportar relatórios'],

            ['modulo' => 'Plano de Ação',        'acao' => 'Visualizar',  'descricao' => 'Visualizar planos de ação'],
            ['modulo' => 'Plano de Ação',        'acao' => 'Criar',       'descricao' => 'Criar planos de ação'],
            ['modulo' => 'Plano de Ação',        'acao' => 'Enviar',      'descricao' => 'Enviar planos de ação'],
        ];

        foreach ($permissoes as $p) {
            Permissao::firstOrCreate(
                ['modulo' => $p['modulo'], 'acao' => $p['acao']],
                ['descricao' => $p['descricao']]
            );
        }
    }
}
