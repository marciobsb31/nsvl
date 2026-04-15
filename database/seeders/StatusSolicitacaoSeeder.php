<?php

namespace Database\Seeders;

use App\Enums\StatusSolicitacaoEnum;
use App\Models\StatusSolicitacao;
use Illuminate\Database\Seeder;

class StatusSolicitacaoSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = [
            [
                'id' => 1,
                'codigo' => 'EM_ANALISE',
                'nome' => StatusSolicitacao::EM_ANALISE,
            ],
            [
                'id' => 2,
                'codigo' => 'APROVADO',
                'nome' => StatusSolicitacao::APROVADO,
            ],
            [
                'id' => 3,
                'codigo' => 'REPROVADO',
                'nome' => StatusSolicitacao::REPROVADO,
            ],
        ];

        foreach ($statuses as $status) {
            StatusSolicitacao::firstOrCreate(
                ['id' => $status['id']],
                [
                    'codigo'     => $status['codigo'],
                    'nome'       => $status['nome'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
