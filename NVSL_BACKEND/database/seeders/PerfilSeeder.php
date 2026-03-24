<?php

namespace Database\Seeders;

use App\Models\Perfil;
use App\Models\Permissao;
use Illuminate\Database\Seeder;

class PerfilSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(PermissaoSeeder::class);

        $perfis = [
            ['nome' => 'Administrador', 'descricao' => 'Acesso total ao sistema', 'esfera' => 'federal', 'status' => 'ativo'],
            ['nome' => 'Gestor', 'descricao' => 'Gestão de cadastros e planos', 'esfera' => 'federal', 'status' => 'ativo'],
            ['nome' => 'Analista', 'descricao' => 'Análise de solicitações', 'esfera' => 'federal', 'status' => 'ativo'],
            ['nome' => 'Visualizador', 'descricao' => 'Apenas visualização', 'esfera' => 'federal', 'status' => 'ativo'],
            ['nome' => 'Gestor Nacional', 'descricao' => 'Gestão nacional', 'esfera' => 'federal', 'status' => 'ativo'],
            ['nome' => 'Gestor Estadual', 'descricao' => 'Gestão estadual', 'esfera' => 'estadual', 'status' => 'ativo'],
            ['nome' => 'Gestor Municipal', 'descricao' => 'Gestão municipal', 'esfera' => 'municipal', 'status' => 'ativo'],
            ['nome' => 'Administrador Nacional', 'descricao' => 'Administração nacional', 'esfera' => 'federal', 'status' => 'ativo'],
            ['nome' => 'Administrador Estadual', 'descricao' => 'Administração estadual', 'esfera' => 'estadual', 'status' => 'ativo'],
            ['nome' => 'Administrador Municipal', 'descricao' => 'Administração municipal', 'esfera' => 'municipal', 'status' => 'ativo'],
        ];

        foreach ($perfis as $p) {
            Perfil::updateOrCreate(
                ['nome' => $p['nome']],
                ['descricao' => $p['descricao'], 'esfera' => $p['esfera'], 'status' => $p['status']]
            );
        }

        $todasPermissoes = Permissao::pluck('id')->toArray();

        $permGerenciarCadastros = Permissao::where('modulo', 'Gerenciar Cadastros')->pluck('id')->toArray();
        $permGerenciarPerfis    = Permissao::where('modulo', 'Gerenciar Perfis')->pluck('id')->toArray();
        $permRelatorios         = Permissao::where('modulo', 'Relatórios')->pluck('id')->toArray();
        $permPlanoAcao          = Permissao::where('modulo', 'Plano de Ação')->pluck('id')->toArray();
        $permVisualizacao       = Permissao::whereIn('acao', ['Visualizar'])->pluck('id')->toArray();

        $vinculacoes = [
            'Administrador'             => $todasPermissoes,
            'Administrador Nacional'    => $todasPermissoes,
            'Administrador Estadual'    => $todasPermissoes,
            'Administrador Municipal'   => $todasPermissoes,
            'Gestor'                    => array_merge($permGerenciarCadastros, $permGerenciarPerfis, $permRelatorios, $permPlanoAcao),
            'Gestor Nacional'           => array_merge($permGerenciarCadastros, $permGerenciarPerfis, $permRelatorios, $permPlanoAcao),
            'Gestor Estadual'           => array_merge($permGerenciarCadastros, $permRelatorios, $permPlanoAcao),
            'Gestor Municipal'          => array_merge($permGerenciarCadastros, $permPlanoAcao),
            'Analista'                  => array_merge($permGerenciarCadastros, $permRelatorios),
            'Visualizador'              => $permVisualizacao,
        ];

        foreach ($vinculacoes as $nomePerfil => $permissoesIds) {
            $perfil = Perfil::where('nome', $nomePerfil)->first();
            if ($perfil) {
                $perfil->permissoes()->syncWithoutDetaching($permissoesIds);
            }
        }
    }
}
