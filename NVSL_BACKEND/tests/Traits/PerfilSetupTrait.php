<?php

namespace Tests\Traits;

use App\Models\Perfil;
use App\Models\Permissao;
use Database\Seeders\PermissaoSeeder;

trait PerfilSetupTrait
{
    protected function seedPermissoes(): void
    {
        $this->seed(PermissaoSeeder::class);
    }

    protected function criarPerfilComPermissoes(
        string $nome,
        string $esfera,
        array $permissoes = [],
        string $status = 'ativo'
    ): Perfil {
        $perfil = Perfil::factory()->create([
            'nome'   => $nome,
            'esfera' => $esfera,
            'status' => $status,
        ]);

        if (!empty($permissoes)) {
            $ids = [];
            foreach ($permissoes as $p) {
                $perm = Permissao::firstOrCreate(
                    ['modulo' => $p['modulo'], 'acao' => $p['acao']],
                    ['descricao' => $p['descricao'] ?? null]
                );
                $ids[] = $perm->id;
            }
            $perfil->permissoes()->sync($ids);
        }

        return $perfil->fresh('permissoes');
    }
}
