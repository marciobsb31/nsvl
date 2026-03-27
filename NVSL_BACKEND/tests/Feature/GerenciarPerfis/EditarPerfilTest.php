<?php

namespace Tests\Feature\GerenciarPerfis;

use App\Models\Perfil;
use Tests\TestCase;
use Tests\Traits\ActingAsUserTrait;
use PHPUnit\Framework\Attributes\Test;

class EditarPerfilTest extends TestCase
{
    use ActingAsUserTrait;

    private function dadosUpdate(array $override = []): array
    {
        return array_merge([
            'nome'      => 'Gestor Nacional',
            'descricao' => 'Descrição atualizada',
            'ativo'     => true,
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
        $perfil = Perfil::factory()->create(['nome' => 'Gestor Nacional']);

        $this->autenticar($user)
            ->putJson("/api/gerenciar-perfis/{$perfil->id}", $this->dadosUpdate())
            ->assertOk()
            ->assertJsonPath('message', 'Perfil atualizado com sucesso.')
            ->assertJsonPath('data.nome', 'Gestor Nacional')
            ->assertJsonPath('data.descricao', 'Descrição atualizada');
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
        $perfil = Perfil::factory()->create(['nome' => 'Gestor Nacional']);
        Perfil::factory()->create(['nome' => 'Gestor Estadual']);

        $this->autenticar($user)
            ->putJson("/api/gerenciar-perfis/{$perfil->id}", $this->dadosUpdate(['nome' => 'Gestor Nacional']))
            ->assertOk();

        $this->autenticar($user)
            ->putJson("/api/gerenciar-perfis/{$perfil->id}", $this->dadosUpdate(['nome' => 'Gestor Estadual']))
            ->assertStatus(422);
    }

    #[Test]
    public function usuario_estadual_edita_perfil(): void
    {
        $user = $this->criarUsuarioEstadual();
        $perfil = Perfil::factory()->create(['nome' => 'Gestor Municipal']);

        $this->autenticar($user)
            ->putJson("/api/gerenciar-perfis/{$perfil->id}", $this->dadosUpdate([
                'nome' => 'Gestor Municipal',
            ]))
            ->assertOk();
    }

    #[Test]
    public function altera_ativo_para_false(): void
    {
        $user = $this->criarUsuarioFederal();
        $perfil = Perfil::factory()->create(['nome' => 'Administrador Municipal', 'ativo' => true]);

        $this->autenticar($user)
            ->putJson("/api/gerenciar-perfis/{$perfil->id}", $this->dadosUpdate([
                'nome'  => 'Administrador Municipal',
                'ativo' => false,
            ]))
            ->assertOk();

        $this->assertFalse($perfil->fresh()->ativo);
    }

    #[Test]
    public function registra_log_de_auditoria_ao_editar(): void
    {
        $user = $this->criarUsuarioFederal();
        $perfil = Perfil::factory()->create(['nome' => 'Gestor Estadual']);

        $this->autenticar($user)
            ->putJson("/api/gerenciar-perfis/{$perfil->id}", $this->dadosUpdate([
                'nome' => 'Gestor Estadual',
            ]));

        $this->assertDatabaseHas('auditoria_log', [
            'user_id' => $user->id,
            'acao'    => 'gerenciar_perfis.editar',
            'tipo_operacao' => 'update',
        ]);
    }

    #[Test]
    public function nome_obrigatorio_na_edicao(): void
    {
        $user = $this->criarUsuarioFederal();
        $perfil = Perfil::factory()->create(['nome' => 'Gestor Municipal']);

        $this->autenticar($user)
            ->putJson("/api/gerenciar-perfis/{$perfil->id}", $this->dadosUpdate(['nome' => '']))
            ->assertStatus(422);
    }
}
