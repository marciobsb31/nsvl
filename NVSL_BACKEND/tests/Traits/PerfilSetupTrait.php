<?php

namespace Tests\Traits;

use App\Models\Perfil;

trait PerfilSetupTrait
{
    protected function criarPerfil(
        string $nome,
        bool $ativo = true
    ): Perfil {
        return Perfil::factory()->create([
            'nome'  => $nome,
            'ativo' => $ativo,
        ]);
    }
}
