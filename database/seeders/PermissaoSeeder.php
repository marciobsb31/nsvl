<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PermissaoSeeder extends Seeder
{
    public function run(): void
    {
        $recursos = DB::table('recursos')->get();
        $acoes = DB::table('acoes')->get();

        foreach ($recursos as $recurso) {
            foreach ($acoes as $acao) {
                DB::table('permissoes')->updateOrInsert([
                    'recurso_id' => $recurso->id,
                    'acao_id'    => $acao->id,
                    'codigo'     => "{$recurso->codigo}.{$acao->codigo}",
                    'nome'       => "{$acao->nome} {$recurso->nome}",
                    'ativo'      => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
