<?php

namespace Tests\Feature\GerenciarPerfis;

use App\Models\Perfil;
use Tests\TestCase;
use Tests\Traits\ActingAsUserTrait;
use PHPUnit\Framework\Attributes\Test;

class CriarPerfilTest extends TestCase
{
    use ActingAsUserTrait;

    private function dadosPerfil(array $override = []): array
    {
        return array_merge([
            'nome'      => 'Gestor Municipal',
            'descricao' => 'Descrição do perfil de teste',
            'ativo'     => true,
        ], $override);
    }

    #[Test]
    public function usuario_nao_autenticado_recebe_401(): void
    {
        $this->postJson('/api/gerenciar-perfis', $this->dadosPerfil())
            ->assertStatus(401);
    }

    #[Test]
    public function usuario_federal_cria_perfil_com_sucesso(): void
    {
        $user = $this->criarUsuarioFederal();

        $this->autenticar($user)
            ->postJson('/api/gerenciar-perfis', $this->dadosPerfil())
            ->assertStatus(201)
            ->assertJsonPath('message', 'Perfil cadastrado com sucesso.')
            ->assertJsonPath('data.nome', 'Perfil Teste Criação');

        $this->assertDatabaseHas('perfis', ['nome' => 'Perfil Teste Criação']);
    }

    #[Test]
    public function nome_obrigatorio(): void
    {
        $user = $this->criarUsuarioFederal();

        $this->autenticar($user)
            ->postJson('/api/gerenciar-perfis', $this->dadosPerfil(['nome' => '']))
            ->assertStatus(422);
    }

    #[Test]
    public function nome_deve_ser_unico(): void
    {
        Perfil::factory()->create(['nome' => 'Gestor Municipal']);
        $user = $this->criarUsuarioFederal();

        $this->autenticar($user)
            ->postJson('/api/gerenciar-perfis', $this->dadosPerfil(['nome' => 'Gestor Municipal']))
            ->assertStatus(422);
    }

    #[Test]
    public function nome_fora_do_catalogo_oficial_recebe_422(): void
    {
        $user = $this->criarUsuarioFederal();

        $this->autenticar($user)
            ->postJson('/api/gerenciar-perfis', $this->dadosPerfil(['nome' => 'Perfil Personalizado']))
            ->assertStatus(422);
    }

    #[Test]
    public function usuario_estadual_pode_criar_perfil(): void
    {
        $user = $this->criarUsuarioEstadual();

        $this->autenticar($user)
            ->postJson('/api/gerenciar-perfis', $this->dadosPerfil())
            ->assertStatus(201);
    }

    #[Test]
    public function registra_log_de_auditoria_ao_criar(): void
    {
        $user = $this->criarUsuarioFederal();

        $this->autenticar($user)
            ->postJson('/api/gerenciar-perfis', $this->dadosPerfil());

        $this->assertDatabaseHas('auditoria_log', [
            'user_id' => $user->id,
            'acao'    => 'gerenciar_perfis.cadastrar',
            'tipo_operacao' => 'insert',
        ]);
    }

    #[Test]
    public function resposta_segue_formato_esperado(): void
    {
        $user = $this->criarUsuarioFederal();

        $this->autenticar($user)
            ->postJson('/api/gerenciar-perfis', $this->dadosPerfil())
            ->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'data' => ['id', 'nome', 'descricao', 'ativo', 'created_at'],
            ]);
    }
}
