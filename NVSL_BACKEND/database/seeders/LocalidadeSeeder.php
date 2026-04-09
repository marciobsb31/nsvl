<?php

namespace Database\Seeders;

use App\Models\Municipio;
use App\Models\Uf;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class LocalidadeSeeder extends Seeder
{
    public function run(): void
    {
        if ($this->jaPossuiLocalidades()) {
            return;
        }

        // Em testes automatizados evitamos dependência de rede.
        if (app()->environment('testing')) {
            $this->call(LocalidadeTesteSeeder::class);
            return;
        }

        try {
            Artisan::call('localidades:importar-ibge');
        } catch (\Throwable) {
            // Fallback para dados mínimos quando IBGE estiver indisponível.
        }

        if (!$this->jaPossuiLocalidades()) {
            $this->call(LocalidadeTesteSeeder::class);
        }
    }

    private function jaPossuiLocalidades(): bool
    {
        return Uf::query()->exists() && Municipio::query()->exists();
    }
}