<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AcaoSeeder extends Seeder
{
    public function run(): void
    {
        $acoes = [
            [
                'codigo'    => 'visualizar',
                'nome'      => 'Visualizar',
                'descricao' => 'Permite visualizar registros e detalhes.',
                'ativo'     => true,
            ],
            [
                'codigo'    => 'criar',
                'nome'      => 'Criar',
                'descricao' => 'Permite a criação de novos registros.',
                'ativo'     => true,
            ],
            [
                'codigo'    => 'editar',
                'nome'      => 'Editar',
                'descricao' => 'Permite a alteração de registros existentes.',
                'ativo'     => true,
            ],
            [
                'codigo'    => 'excluir',
                'nome'      => 'Excluir',
                'descricao' => 'Permite a exclusão (ou desativação) de registros.',
                'ativo'     => true,
            ],
            [
                'codigo'    => 'exportar',
                'nome'      => 'Exportar',
                'descricao' => 'Permite a exportação de dados em formatos como PDF ou Excel.',
                'ativo'     => true,
            ],
        ];

        foreach ($acoes as $acao) {
            DB::table('acoes')->updateOrInsert(
                ['codigo' => $acao['codigo']],
                array_merge($acao, [
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ])
            );
        }
    }
}
