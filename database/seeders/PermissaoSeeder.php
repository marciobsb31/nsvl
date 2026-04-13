<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PermissaoSeeder extends Seeder
{
    public function run(): void
    {
        $recursos = DB::table('recursos')->get()->keyBy('codigo');
        $acoes = DB::table('acoes')->get()->keyBy('codigo');

        $matriz = [
            'usuarios'              => ['visualizar', 'editar', 'excluir'],
            'solicitacoes_cadastro' => ['visualizar', 'editar', 'analisar'],
            'perfis'                => ['visualizar', 'editar'],
            'plano_acao'            => ['visualizar', 'cadastrar', 'editar', 'enviar'],
            'relatorio_execucao'    => ['visualizar', 'cadastrar', 'editar', 'enviar'],
            'auditoria'             => ['visualizar'],
        ];

        foreach ($matriz as $codRecurso => $listaAcoes) {
            foreach ($listaAcoes as $codAcao) {

                $recurso = $recursos->get($codRecurso);
                $acao = $acoes->get($codAcao);

                if ($recurso && $acao) {
                    DB::table('permissoes')->updateOrInsert(
                        [
                            'recurso_id' => $recurso->id,
                            'acao_id'    => $acao->id,
                        ],
                        [
                            'codigo'     => "{$recurso->codigo}.{$acao->codigo}",
                            'nome'       => "{$acao->nome} {$recurso->nome}",
                            'ativo'      => true,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    );
                }
            }
        }
    }
}
