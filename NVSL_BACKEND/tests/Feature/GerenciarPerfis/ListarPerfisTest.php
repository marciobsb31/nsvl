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
        Perfil::factory()->count(3)->create();
        $user = $this->criarUsuarioFederal();

        $this->autenticar($user)
            ->getJson('/api/gerenciar-perfis')
            ->assertOk()
            ->assertJsonStructure(['data' => [['id', 'nome', 'esfera', 'status', 'permissoes']]]);
    }

    #[Test]
    public function usuario_estadual_com_permissao_lista_perfis(): void
    {
        Perfil::factory()->count(2)->create();
        $user = $this->criarUsuarioEstadual([
            ['modulo' => 'Gerenciar Perfis', 'acao' => 'Visualizar'],
        ]);

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
    public function filtra_perfis_por_esfera(): void
    {
        Perfil::factory()->federal()->create();
        Perfil::factory()->estadual()->create();
        $user = $this->criarUsuarioFederal();

        $response = $this->autenticar($user)
            ->getJson('/api/gerenciar-perfis?esfera=federal')
            ->assertOk();

        foreach ($response->json('data') as $perfil) {
            $this->assertEquals('federal', $perfil['esfera']);
        }
    }

    #[Test]
    public function filtra_perfis_por_status(): void
    {
        Perfil::factory()->create(['status' => 'ativo']);
        Perfil::factory()->create(['status' => 'inativo']);
        $user = $this->criarUsuarioFederal();

        $response = $this->autenticar($user)
            ->getJson('/api/gerenciar-perfis?status=ativo')
            ->assertOk();

        foreach ($response->json('data') as $perfil) {
            $this->assertEquals('ativo', $perfil['status']);
        }
    }

    #[Test]
    public function resposta_contem_permissoes_do_perfil(): void
    {
        $user = $this->criarUsuarioFederal();
        $perfil = Perfil::factory()->create();
        $perm = $this->criarPermissao('Gerenciar Perfis', 'Criar');
        $perfil->permissoes()->sync([$perm->id]);

        $response = $this->autenticar($user)
            ->getJson('/api/gerenciar-perfis')
            ->assertOk();

        $data = collect($response->json('data'));
        $found = $data->firstWhere('id', $perfil->id);
        $this->assertNotEmpty($found['permissoes']);
    }

    #[Test]
    public function registra_log_de_auditoria_na_listagem(): void
    {
        $user = $this->criarUsuarioFederal();

        $this->autenticar($user)->getJson('/api/gerenciar-perfis');

        $this->assertDatabaseHas('auditoria_log', [
            'user_id' => $user->id,
            'action'  => 'gerenciar_perfis.listagem',
        ]);
    }
}
