<?php

namespace Tests\Feature\GerenciarPerfis;

use App\Models\Perfil;
use App\Models\Permissao;
use Tests\TestCase;
use Tests\Traits\ActingAsUserTrait;
use PHPUnit\Framework\Attributes\Test;

class EditarPerfilTest extends TestCase
{
    use ActingAsUserTrait;

    private function dadosUpdate(array $override = []): array
    {
        return array_merge([
            'nome'       => 'Perfil Atualizado',
            'descricao'  => 'Descrição atualizada',
            'esfera'     => 'federal',
            'status'     => 'ativo',
            'permissoes' => [],
        ], $override);
    }

    #[Test]
    public function usuario_nao_autenticado_recebe_401(): void
    {
        $perfil = Perfil::factory()->create();

        $this->putJson("/api/gerenciar-perfis/{$perfil->id}", $this->dadosUpdate())
            ->assertStatus(401);
    }

    #[Test]
    public function usuario_federal_edita_perfil_com_sucesso(): void
    {
        $user = $this->criarUsuarioFederal();
        $perfil = Perfil::factory()->federal()->create();

        $this->autenticar($user)
            ->putJson("/api/gerenciar-perfis/{$perfil->id}", $this->dadosUpdate())
            ->assertOk()
            ->assertJsonPath('message', 'Perfil atualizado com sucesso.')
            ->assertJsonPath('data.nome', 'Perfil Atualizado');
    }

    #[Test]
    public function perfil_inexistente_retorna_404(): void
    {
        $user = $this->criarUsuarioFederal();

        $this->autenticar($user)
            ->putJson('/api/gerenciar-perfis/99999', $this->dadosUpdate())
            ->assertStatus(404);
    }

    #[Test]
    public function nome_deve_ser_unico_ignorando_proprio_registro(): void
    {
        $user = $this->criarUsuarioFederal();
        $perfil = Perfil::factory()->create(['nome' => 'Meu Perfil']);
        Perfil::factory()->create(['nome' => 'Outro Perfil']);

        $this->autenticar($user)
            ->putJson("/api/gerenciar-perfis/{$perfil->id}", $this->dadosUpdate(['nome' => 'Meu Perfil']))
            ->assertOk();

        $this->autenticar($user)
            ->putJson("/api/gerenciar-perfis/{$perfil->id}", $this->dadosUpdate(['nome' => 'Outro Perfil']))
            ->assertStatus(422);
    }

    #[Test]
    public function usuario_estadual_com_permissao_edita_perfil_estadual(): void
    {
        $user = $this->criarUsuarioEstadual([
            ['modulo' => 'Gerenciar Perfis', 'acao' => 'Editar'],
        ]);
        $perfil = Perfil::factory()->estadual()->create();

        $this->autenticar($user)
            ->putJson("/api/gerenciar-perfis/{$perfil->id}", $this->dadosUpdate(['esfera' => 'estadual']))
            ->assertOk();
    }

    #[Test]
    public function usuario_estadual_nao_pode_editar_perfil_federal(): void
    {
        $user = $this->criarUsuarioEstadual([
            ['modulo' => 'Gerenciar Perfis', 'acao' => 'Editar'],
        ]);
        $perfil = Perfil::factory()->federal()->create();

        $this->autenticar($user)
            ->putJson("/api/gerenciar-perfis/{$perfil->id}", $this->dadosUpdate())
            ->assertStatus(403);
    }

    #[Test]
    public function usuario_municipal_nao_pode_editar_nenhum_perfil(): void
    {
        $user = $this->criarUsuarioMunicipal([
            ['modulo' => 'Gerenciar Perfis', 'acao' => 'Editar'],
        ]);
        $perfil = Perfil::factory()->municipal()->create();

        $this->autenticar($user)
            ->putJson("/api/gerenciar-perfis/{$perfil->id}", $this->dadosUpdate(['esfera' => 'municipal']))
            ->assertStatus(403);
    }

    #[Test]
    public function altera_status_para_inativo(): void
    {
        $user = $this->criarUsuarioFederal();
        $perfil = Perfil::factory()->create(['status' => 'ativo']);

        $this->autenticar($user)
            ->putJson("/api/gerenciar-perfis/{$perfil->id}", $this->dadosUpdate(['status' => 'inativo']))
            ->assertOk();

        $this->assertEquals('inativo', $perfil->fresh()->status);
    }

    #[Test]
    public function atualiza_permissoes_do_perfil(): void
    {
        $user = $this->criarUsuarioFederal();
        $perfil = Perfil::factory()->create();
        $perm = Permissao::create(['modulo' => 'Relatórios', 'acao' => 'Visualizar']);

        $this->autenticar($user)
            ->putJson("/api/gerenciar-perfis/{$perfil->id}", $this->dadosUpdate(['permissoes' => [$perm->id]]))
            ->assertOk();

        $this->assertCount(1, $perfil->fresh()->permissoes);
    }

    #[Test]
    public function registra_log_de_auditoria_ao_editar(): void
    {
        $user = $this->criarUsuarioFederal();
        $perfil = Perfil::factory()->create();

        $this->autenticar($user)
            ->putJson("/api/gerenciar-perfis/{$perfil->id}", $this->dadosUpdate());

        $this->assertDatabaseHas('auditoria_log', [
            'user_id' => $user->id,
            'action'  => 'gerenciar_perfis.editar',
            'tipo_operacao' => 'update',
        ]);
    }

    #[Test]
    public function nome_obrigatorio_na_edicao(): void
    {
        $user = $this->criarUsuarioFederal();
        $perfil = Perfil::factory()->create();

        $this->autenticar($user)
            ->putJson("/api/gerenciar-perfis/{$perfil->id}", $this->dadosUpdate(['nome' => '']))
            ->assertStatus(422);
    }
}
