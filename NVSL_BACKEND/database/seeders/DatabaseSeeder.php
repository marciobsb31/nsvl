<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Inclui perfis, esferas, status e usuários de exemplo (govbr_sub teste-*).
     */
    public function run(): void
    {
        $this->call(UsuarioExemploSeeder::class);
    }
}
