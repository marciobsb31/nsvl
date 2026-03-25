<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;
use Tests\Traits\ActingAsUserTrait;
use PHPUnit\Framework\Attributes\Test;

class UserEndpointTest extends TestCase
{
    use ActingAsUserTrait;

    #[Test]
    public function usuario_nao_autenticado_recebe_401(): void
    {
        $this->getJson('/api/user')
            ->assertStatus(401);
    }

    #[Test]
    public function usuario_autenticado_recebe_dados_seguros(): void
    {
        $user = $this->criarUsuarioFederal();

        $this->autenticar($user)
            ->getJson('/api/user')
            ->assertOk()
            ->assertJsonStructure(['id', 'name', 'email', 'role', 'esfera_atuacao', 'perfis_vigentes', 'permissoes'])
            ->assertJsonMissing(['cpf_hash']);
    }

    #[Test]
    public function resposta_contem_esfera_atuacao(): void
    {
        $user = $this->criarUsuarioFederal();

        $this->autenticar($user)
            ->getJson('/api/user')
            ->assertJsonPath('esfera_atuacao', 'federal');
    }

    #[Test]
    public function resposta_contem_perfis_vigentes(): void
    {
        $user = $this->criarUsuarioFederal();

        $response = $this->autenticar($user)->getJson('/api/user');

        $this->assertIsArray($response->json('perfis_vigentes'));
        $this->assertNotEmpty($response->json('perfis_vigentes'));
    }
}
