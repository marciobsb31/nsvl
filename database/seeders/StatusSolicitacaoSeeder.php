<?php

namespace Database\Seeders;

use App\Models\StatusSolicitacao;
use Illuminate\Database\Seeder;

class StatusSolicitacaoSeeder extends Seeder
{
    public function run(): void
    {
        $status = [
            ['nome' => 'em_analise'],
            ['nome' => 'aprovado'],
            ['nome' => 'reprovado'],
        ];

        foreach ($status as $s) {
            StatusSolicitacao::firstOrCreate(
                ['nome' => $s['nome']],
            );
        }
    }
}
