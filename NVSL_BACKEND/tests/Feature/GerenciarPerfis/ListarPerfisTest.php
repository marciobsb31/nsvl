<?php

namespace Tests\Feature\GerenciarPerfis;

use App\Models\Perfil;
use Tests\TestCase;
use Tests\Traits\ActingAsUserTrait;
use PHPUnit\Framework\Attributes\Test;

class ListarPerfisTest extends TestCase
{
    use ActingAsUserTrait;

    #[Test]
    public function usuario_nao_autenticado_recebe_401(): void
    {
        $this->getJson('/api/gerenciar-perfis')
            ->assertStatus(401);
    }

    #[Test]
    public function usuario_federal_lista_todos_os_perfis(): void
    {
        Perfil::factory()->create(['nome' => 'Gestor Nacional']);
        Perfil::factory()->create(['nome' => 'Gestor Estadual']);
        Perfil::factory()->create(['nome' => 'Perfil Legado Fora Do Catalogo']);
        $user = $this->criarUsuarioFederal();

        $response = $this->autenticar($user)
            ->getJson('/api/gerenciar-perfis')
            ->assertOk()
            ->assertJsonStructure(['data' => [['id', 'nome', 'ativo']]]);

        $this->assertCount(2, $response->json('data'));
    }

    #[Test]
    public function usuario_estadual_lista_perfis(): void
    {
        Perfil::factory()->create(['nome' => 'Gestor Municipal']);
        Perfil::factory()->create(['nome' => 'Legado Ignorado']);
        $user = $this->criarUsuarioEstadual();

        $this->autenticar($user)
            ->getJson('/api/gerenciar-perfis')
            ->assertOk();
    }

    #[Test]
    public function usuario_estadual_sem_permissao_recebe_403(): void
    {
        $user = $this->criarUsuarioEstadual();

        $this->autenticar($user)
            ->getJson('/api/gerenciar-perfis')
            ->assertStatus(403);
    }

    #[Test]
    public function filtra_perfis_por_ativo(): void
    {
        Perfil::factory()->create(['nome' => 'Gestor Nacional', 'ativo' => true]);
        Perfil::factory()->create(['nome' => 'Gestor Estadual', 'ativo' => false]);
        $user = $this->criarUsuarioFederal();

        $response = $this->autenticar($user)
            ->getJson('/api/gerenciar-perfis?status=ativo')
            ->assertOk();

        $this->assertCount(1, $response->json('data'));
        foreach ($response->json('data') as $perfil) {
            $this->assertTrue($perfil['ativo']);
        }
    }

    #[Test]
    public function registra_log_de_auditoria_na_listagem(): void
    {
        $user = $this->criarUsuarioFederal();

        $this->autenticar($user)->getJson('/api/gerenciar-perfis');

        $this->assertDatabaseHas('auditoria_log', [
            'user_id' => $user->id,
            'acao'    => 'gerenciar_perfis.listagem',
        ]);
    }
}
