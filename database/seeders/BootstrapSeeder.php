<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class BootstrapSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            LocalidadesSeeder::class,
            EsferaSeeder::class,
            StatusSolicitacaoSeeder::class,
            AcaoSeeder::class,
            RecursoSeeder::class,
            PermissaoSeeder::class,
            PerfilSeeder::class,
            PerfilPermissaoSeeder::class,
            UsuarioAdminSeeder::class,
        ]);
    }
}
