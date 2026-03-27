<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;
use Tests\Traits\ActingAsUserTrait;
use PHPUnit\Framework\Attributes\Test;

class LogoutTest extends TestCase
{
    use ActingAsUserTrait;

    #[Test]
    public function usuario_nao_autenticado_recebe_401(): void
    {
        $this->postJson('/api/auth/logout')
            ->assertStatus(401);
    }

    #[Test]
    public function usuario_autenticado_faz_logout(): void
    {
        $user = $this->criarUsuarioFederal();

        $this->autenticar($user)
            ->postJson('/api/auth/logout')
            ->assertOk()
            ->assertJsonPath('message', 'Logout realizado com sucesso.');
    }

    #[Test]
    public function registra_auditoria_no_logout(): void
    {
        $user = $this->criarUsuarioFederal();

        $this->autenticar($user)->postJson('/api/auth/logout');

        $this->assertDatabaseHas('auditoria_log', [
            'user_id' => $user->id,
            'acao'    => 'auth.logout',
            'tipo_operacao' => 'logout',
        ]);
    }
}
