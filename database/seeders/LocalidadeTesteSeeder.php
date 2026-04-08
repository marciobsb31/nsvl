<?php

namespace Database\Seeders;

use App\Models\Municipio;
use App\Models\Uf;
use Illuminate\Database\Seeder;

class LocalidadeTesteSeeder extends Seeder
{
    public function run(): void
    {
        $ufs = [
            'DF' => 'Distrito Federal',
            'GO' => 'Goiás',
            'SP' => 'São Paulo',
        ];

        foreach ($ufs as $sigla => $nome) {
            Uf::query()->updateOrCreate(
                ['sigla' => $sigla],
                ['nome' => $nome]
            );
        }

        $municipiosPorUf = [
            'DF' => ['Brasília'],
            'GO' => ['Alexânia', 'Anápolis', 'Goiânia'],
            'SP' => ['São Paulo'],
        ];

        foreach ($municipiosPorUf as $sigla => $municipios) {
            $uf = Uf::query()->where('sigla', $sigla)->first();
            if (!$uf) {
                continue;
            }

            foreach ($municipios as $municipio) {
                Municipio::query()->updateOrCreate(
                    [
                        'uf_id' => $uf->id,
                        'nome' => $municipio,
                    ],
                    []
                );
            }
        }
    }
}
