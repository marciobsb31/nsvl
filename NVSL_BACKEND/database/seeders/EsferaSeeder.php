<?php

namespace Database\Seeders;

use App\Models\Esfera;
use Illuminate\Database\Seeder;

class EsferaSeeder extends Seeder
{
    public function run(): void
    {
        $esferas = [
            ['codigo' => 'federal', 'nome' => 'Federal', 'ordem' => 1],
            ['codigo' => 'estadual', 'nome' => 'Estadual', 'ordem' => 2],
            ['codigo' => 'municipal', 'nome' => 'Municipal', 'ordem' => 3],
        ];

        foreach ($esferas as $e) {
            Esfera::updateOrCreate(
                ['codigo' => $e['codigo']],
                ['nome' => $e['nome'], 'ordem' => $e['ordem']]
            );
        }
    }
}
