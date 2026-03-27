<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class EsferaSeeder extends Seeder
{
    public function run(): void
    {
        $temCodigo = Schema::hasColumn('esferas', 'codigo');

        $esferas = [
            ['nome' => 'Federal', 'codigo' => 'federal'],
            ['nome' => 'Estadual', 'codigo' => 'estadual'],
            ['nome' => 'Municipal', 'codigo' => 'municipal'],
        ];

        foreach ($esferas as $e) {
            $exists = DB::table('esferas')->where('nome', $e['nome'])->exists();
            if ($exists) {
                continue;
            }

            $data = ['nome' => $e['nome']];
            if ($temCodigo) {
                $data['codigo'] = $e['codigo'];
            }

            DB::table('esferas')->insert($data);
        }
    }
}
