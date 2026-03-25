<?php

namespace Tests\Feature\GerenciarPerfis;

use App\Models\Perfil;
use App\Models\Permissao;
use Tests\TestCase;
use Tests\Traits\ActingAsUserTrait;
use PHPUnit\Framework\Attributes\Test;

class CriarPerfilTest extends TestCase
{
    use ActingAsUserTrait;

    private function dadosPerfil(array $override = []): array
    {
        return array_merge([
            'nome'       => 'Perfil Teste Criação',
            'descricao'  => 'Descrição do perfil de teste',
            'esfera'     => 'federal',
            'status'     => 'ativo',
            'permissoes' => [],
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
    public function usuario_federal_cria_perfil_com_permissoes(): void
    {
        $user = $this->criarUsuarioFederal();
        $perm = Permissao::create(['modulo' => 'Gerenciar Perfis', 'acao' => 'Visualizar']);

        $this->autenticar($user)
            ->postJson('/api/gerenciar-perfis', $this->dadosPerfil(['permissoes' => [$perm->id]]))
            ->assertStatus(201);

        $perfil = Perfil::where('nome', 'Perfil Teste Criação')->first();
        $this->assertCount(1, $perfil->permissoes);
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
        Perfil::factory()->create(['nome' => 'Nome Duplicado']);
        $user = $this->criarUsuarioFederal();

        $this->autenticar($user)
            ->postJson('/api/gerenciar-perfis', $this->dadosPerfil(['nome' => 'Nome Duplicado']))
            ->assertStatus(422);
    }

    #[Test]
    public function esfera_obrigatoria_e_valida(): void
    {
        $user = $this->criarUsuarioFederal();

        $this->autenticar($user)
            ->postJson('/api/gerenciar-perfis', $this->dadosPerfil(['esfera' => 'invalida']))
            ->assertStatus(422);
    }

    #[Test]
    public function status_obrigatorio_e_valido(): void
    {
        $user = $this->criarUsuarioFederal();

        $this->autenticar($user)
            ->postJson('/api/gerenciar-perfis', $this->dadosPerfil(['status' => 'inexistente']))
            ->assertStatus(422);
    }

    #[Test]
    public function usuario_estadual_pode_criar_perfil_estadual(): void
    {
        $user = $this->criarUsuarioEstadual([
            ['modulo' => 'Gerenciar Perfis', 'acao' => 'Criar'],
        ]);

        $this->autenticar($user)
            ->postJson('/api/gerenciar-perfis', $this->dadosPerfil(['esfera' => 'estadual']))
            ->assertStatus(201);
    }

    #[Test]
    public function usuario_estadual_nao_pode_criar_perfil_federal(): void
    {
        $user = $this->criarUsuarioEstadual([
            ['modulo' => 'Gerenciar Perfis', 'acao' => 'Criar'],
        ]);

        $this->autenticar($user)
            ->postJson('/api/gerenciar-perfis', $this->dadosPerfil(['esfera' => 'federal']))
            ->assertStatus(403);
    }

    #[Test]
    public function usuario_estadual_sem_permissao_criar_recebe_403(): void
    {
        $user = $this->criarUsuarioEstadual([
            ['modulo' => 'Gerenciar Perfis', 'acao' => 'Visualizar'],
        ]);

        $this->autenticar($user)
            ->postJson('/api/gerenciar-perfis', $this->dadosPerfil(['esfera' => 'estadual']))
            ->assertStatus(403);
    }

    #[Test]
    public function usuario_estadual_nao_pode_atribuir_permissoes_que_nao_possui(): void
    {
        $permPropria = Permissao::create(['modulo' => 'Gerenciar Perfis', 'acao' => 'Criar']);
        $permAlheia = Permissao::create(['modulo' => 'Relatórios', 'acao' => 'Exportar']);

        $user = $this->criarUsuarioEstadual([
            ['modulo' => 'Gerenciar Perfis', 'acao' => 'Criar'],
        ]);

        $this->autenticar($user)
            ->postJson('/api/gerenciar-perfis', $this->dadosPerfil([
                'esfera' => 'estadual',
                'permissoes' => [$permAlheia->id],
            ]))
            ->assertStatus(403);
    }

    #[Test]
    public function registra_log_de_auditoria_ao_criar(): void
    {
        $user = $this->criarUsuarioFederal();

        $this->autenticar($user)
            ->postJson('/api/gerenciar-perfis', $this->dadosPerfil());

        $this->assertDatabaseHas('auditoria_log', [
            'user_id' => $user->id,
            'action'  => 'gerenciar_perfis.cadastrar',
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
                'data' => ['id', 'nome', 'descricao', 'esfera', 'status', 'permissoes', 'created_at'],
            ]);
    }
}
