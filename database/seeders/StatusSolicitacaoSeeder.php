<?php

namespace Database\Seeders;

use App\Enums\StatusSolicitacaoEnum;
use App\Models\StatusSolicitacao;
use Illuminate\Database\Seeder;

class StatusSolicitacaoSeeder extends Seeder
{
    public function run(): void
    {
        foreach (StatusSolicitacaoEnum::cases() as $status) {
            StatusSolicitacao::firstOrCreate(
                ['codigo' => $status->value],
                [
                    'nome'       => $status->label(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
