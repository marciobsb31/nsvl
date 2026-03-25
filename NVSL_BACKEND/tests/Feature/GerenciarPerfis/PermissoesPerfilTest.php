<?php

namespace Tests\Feature\GerenciarPerfis;

use App\Models\Permissao;
use Tests\TestCase;
use Tests\Traits\ActingAsUserTrait;
use PHPUnit\Framework\Attributes\Test;

class PermissoesPerfilTest extends TestCase
{
    use ActingAsUserTrait;

    #[Test]
    public function usuario_nao_autenticado_recebe_401(): void
    {
        $this->getJson('/api/gerenciar-perfis/permissoes')
            ->assertStatus(401);
    }

    #[Test]
    public function usuario_federal_ve_todas_as_permissoes(): void
    {
        Permissao::create(['modulo' => 'Gerenciar Perfis', 'acao' => 'Criar']);
        Permissao::create(['modulo' => 'Relatórios', 'acao' => 'Visualizar']);

        $user = $this->criarUsuarioFederal();

        $response = $this->autenticar($user)
            ->getJson('/api/gerenciar-perfis/permissoes')
            ->assertOk()
            ->assertJsonStructure(['data' => [['id', 'funcionalidade', 'nome_acao']]]);

        $this->assertGreaterThanOrEqual(2, count($response->json('data')));
    }

    #[Test]
    public function usuario_estadual_ve_apenas_permissoes_do_seu_perfil(): void
    {
        $permGerenciar = Permissao::create(['modulo' => 'Gerenciar Perfis', 'acao' => 'Visualizar']);
        Permissao::create(['modulo' => 'Relatórios', 'acao' => 'Exportar']);

        $user = $this->criarUsuarioEstadual([
            ['modulo' => 'Gerenciar Perfis', 'acao' => 'Visualizar'],
        ]);

        $response = $this->autenticar($user)
            ->getJson('/api/gerenciar-perfis/permissoes')
            ->assertOk();

        $permissoes = collect($response->json('data'));
        $this->assertTrue($permissoes->contains('funcionalidade', 'Gerenciar Perfis'));
        $this->assertFalse($permissoes->contains(fn ($p) => $p['funcionalidade'] === 'Relatórios' && $p['nome_acao'] === 'Exportar'));
    }
}
