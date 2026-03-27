<?php

namespace Tests\Feature\TrocaContexto;

use App\Models\Perfil;
use App\Models\PerfilUsuario;
use Tests\TestCase;
use Tests\Traits\ActingAsUserTrait;
use PHPUnit\Framework\Attributes\Test;

class TrocarContextoTest extends TestCase
{
    use ActingAsUserTrait;

    #[Test]
    public function usuario_nao_autenticado_recebe_401(): void
    {
        $this->postJson('/api/user/trocar-contexto', ['perfil_usuario_id' => 1])
            ->assertStatus(401);
    }

    #[Test]
    public function troca_contexto_com_sucesso(): void
    {
        $user = $this->criarUsuarioFederal();

        $novoPerfil = Perfil::factory()->create(['ativo' => true]);
        $novoPu = PerfilUsuario::create([
            'usuario_id' => $user->id,
            'perfil_id'  => $novoPerfil->id,
            'data_inicio_vigencia' => now()->subDay()->toDateString(),
            'ativo'      => false,
        ]);

        $this->autenticar($user)
            ->postJson('/api/user/trocar-contexto', ['perfil_usuario_id' => $novoPu->id])
            ->assertOk()
            ->assertJsonPath('message', 'Contexto alterado com sucesso.');
    }

    #[Test]
    public function perfil_usuario_id_obrigatorio(): void
    {
        $user = $this->criarUsuarioFederal();

        $this->autenticar($user)
            ->postJson('/api/user/trocar-contexto', [])
            ->assertStatus(422);
    }

    #[Test]
    public function perfil_nao_vigente_retorna_403(): void
    {
        $user = $this->criarUsuarioFederal();

        $this->autenticar($user)
            ->postJson('/api/user/trocar-contexto', ['perfil_usuario_id' => 99999])
            ->assertStatus(403);
    }

    #[Test]
    public function registra_auditoria_na_troca(): void
    {
        $user = $this->criarUsuarioFederal();
        $novoPerfil = Perfil::factory()->create(['ativo' => true]);
        $novoPu = PerfilUsuario::create([
            'usuario_id' => $user->id,
            'perfil_id'  => $novoPerfil->id,
            'data_inicio_vigencia' => now()->subDay()->toDateString(),
        ]);

        $this->autenticar($user)
            ->postJson('/api/user/trocar-contexto', ['perfil_usuario_id' => $novoPu->id]);

        $this->assertDatabaseHas('auditoria_log', [
            'user_id' => $user->id,
            'acao'    => 'contexto.troca',
            'tipo_operacao' => 'update',
        ]);
    }
}
