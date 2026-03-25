<?php

namespace Tests\Traits;

use App\Models\Perfil;
use App\Models\PerfilUsuario;
use App\Models\Permissao;
use App\Models\User;

trait ActingAsUserTrait
{
    protected function criarUsuarioFederal(array $attrs = []): User
    {
        $user = User::factory()->federal()->create($attrs);
        $perfil = Perfil::factory()->federal()->create(['nome' => 'Federal Test ' . $user->id]);
        $pu = PerfilUsuario::create([
            'usuario_id' => $user->id,
            'perfil_id'  => $perfil->id,
            'data_inicio_vigencia' => now()->subDay()->toDateString(),
        ]);
        $user->update(['perfil_usuario_ativo_id' => $pu->id]);

        return $user->fresh();
    }

    protected function criarUsuarioEstadual(array $permissoes = [], array $attrs = []): User
    {
        $user = User::factory()->estadual()->create($attrs);
        $perfil = Perfil::factory()->estadual()->create(['nome' => 'Estadual Test ' . $user->id]);
        $this->vincularPermissoes($perfil, $permissoes);

        $pu = PerfilUsuario::create([
            'usuario_id' => $user->id,
            'perfil_id'  => $perfil->id,
            'data_inicio_vigencia' => now()->subDay()->toDateString(),
            'uf' => $user->uf_lotacao,
        ]);
        $user->update(['perfil_usuario_ativo_id' => $pu->id]);

        return $user->fresh();
    }

    protected function criarUsuarioMunicipal(array $permissoes = [], array $attrs = []): User
    {
        $user = User::factory()->municipal()->create($attrs);
        $perfil = Perfil::factory()->municipal()->create(['nome' => 'Municipal Test ' . $user->id]);
        $this->vincularPermissoes($perfil, $permissoes);

        $pu = PerfilUsuario::create([
            'usuario_id' => $user->id,
            'perfil_id'  => $perfil->id,
            'data_inicio_vigencia' => now()->subDay()->toDateString(),
            'uf' => $user->uf_lotacao,
            'municipio' => $user->municipio_lotacao,
        ]);
        $user->update(['perfil_usuario_ativo_id' => $pu->id]);

        return $user->fresh();
    }

    protected function autenticar(User $user): self
    {
        return $this->actingAs($user, 'sanctum');
    }

    protected function criarPermissao(string $modulo, string $acao): Permissao
    {
        return Permissao::firstOrCreate(
            ['modulo' => $modulo, 'acao' => $acao],
            ['descricao' => "{$modulo} - {$acao}"]
        );
    }

    private function vincularPermissoes(Perfil $perfil, array $permissoes): void
    {
        if (empty($permissoes)) {
            return;
        }

        $ids = [];
        foreach ($permissoes as $p) {
            $perm = $this->criarPermissao($p['modulo'], $p['acao']);
            $ids[] = $perm->id;
        }
        $perfil->permissoes()->sync($ids);
    }
}
