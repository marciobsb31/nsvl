<?php

namespace Tests\Feature\GerenciarPerfis;

use App\Models\Perfil;
use Tests\TestCase;
use Tests\Traits\ActingAsUserTrait;
use PHPUnit\Framework\Attributes\Test;

class VisualizarPerfilTest extends TestCase
{
    use ActingAsUserTrait;

    #[Test]
    public function usuario_nao_autenticado_recebe_401(): void
    {
        $perfil = Perfil::factory()->create();

        $this->getJson("/api/gerenciar-perfis/{$perfil->id}")
            ->assertStatus(401);
    }

    #[Test]
    public function usuario_federal_visualiza_perfil(): void
    {
        $user = $this->criarUsuarioFederal();
        $perfil = Perfil::factory()->create(['nome' => 'Perfil Detalhe']);

        $this->autenticar($user)
            ->getJson("/api/gerenciar-perfis/{$perfil->id}")
            ->assertOk()
            ->assertJsonPath('data.nome', 'Perfil Detalhe')
            ->assertJsonStructure(['data' => ['id', 'nome', 'esfera', 'status', 'permissoes']]);
    }

    #[Test]
    public function perfil_inexistente_retorna_404(): void
    {
        $user = $this->criarUsuarioFederal();

        $this->autenticar($user)
            ->getJson('/api/gerenciar-perfis/99999')
            ->assertStatus(404);
    }

    #[Test]
    public function usuario_estadual_com_permissao_visualiza(): void
    {
        $user = $this->criarUsuarioEstadual([
            ['modulo' => 'Gerenciar Perfis', 'acao' => 'Visualizar'],
        ]);
        $perfil = Perfil::factory()->create();

        $this->autenticar($user)
            ->getJson("/api/gerenciar-perfis/{$perfil->id}")
            ->assertOk();
    }

    #[Test]
    public function usuario_estadual_sem_permissao_recebe_403(): void
    {
        $user = $this->criarUsuarioEstadual();
        $perfil = Perfil::factory()->create();

        $this->autenticar($user)
            ->getJson("/api/gerenciar-perfis/{$perfil->id}")
            ->assertStatus(403);
    }
}
