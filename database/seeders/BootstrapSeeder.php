<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class BootstrapSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            EsferaSeeder::class,
            StatusSolicitacaoSeeder::class,
            PerfilSeeder::class,
        ]);
    }
}
