<?php

namespace Tests\Feature\TrocaContexto;

use Tests\TestCase;
use Tests\Traits\ActingAsUserTrait;
use PHPUnit\Framework\Attributes\Test;

class ListarPerfisAtivosTest extends TestCase
{
    use ActingAsUserTrait;

    #[Test]
    public function usuario_nao_autenticado_recebe_401(): void
    {
        $this->getJson('/api/user/perfis-ativos')
            ->assertStatus(401);
    }

    #[Test]
    public function retorna_perfis_vigentes_do_usuario(): void
    {
        $user = $this->criarUsuarioFederal();

        $this->autenticar($user)
            ->getJson('/api/user/perfis-ativos')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [['perfil_usuario_id', 'perfil_id', 'nome', 'ativo']],
            ]);
    }

    #[Test]
    public function indica_perfil_ativo_corretamente(): void
    {
        $user = $this->criarUsuarioFederal();

        $response = $this->autenticar($user)
            ->getJson('/api/user/perfis-ativos')
            ->assertOk();

        $perfisAtivos = collect($response->json('data'))->where('ativo', true);
        $this->assertCount(1, $perfisAtivos);
    }
}
