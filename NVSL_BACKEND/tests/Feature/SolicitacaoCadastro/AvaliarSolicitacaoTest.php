<?php

namespace Tests\Feature\SolicitacaoCadastro;

use App\Models\Perfil;
use App\Models\SolicitacaoCadastro;
use Tests\TestCase;
use Tests\Traits\ActingAsUserTrait;
use PHPUnit\Framework\Attributes\Test;

class AvaliarSolicitacaoTest extends TestCase
{
    use ActingAsUserTrait;

    #[Test]
    public function usuario_nao_autenticado_recebe_401(): void
    {
        $s = SolicitacaoCadastro::factory()->create();

        $this->patchJson("/api/solicitacoes-cadastro/{$s->id}", [
            'status' => 'aprovado', 'perfil_id' => 1,
        ])->assertStatus(401);
    }

    #[Test]
    public function usuario_federal_aprova_solicitacao(): void
    {
        $user = $this->criarUsuarioFederal();
        $perfil = Perfil::factory()->create();
        $s = SolicitacaoCadastro::factory()->create();

        $this->autenticar($user)
            ->patchJson("/api/solicitacoes-cadastro/{$s->id}", [
                'status'          => 'aprovado',
                'perfil_id'       => $perfil->id,
                'vigencia_inicio' => '2026-04-01',
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Solicitação aprovada.');

        $this->assertEquals('aprovado', $s->fresh()->status);
    }

    #[Test]
    public function usuario_federal_reprova_com_justificativa(): void
    {
        $user = $this->criarUsuarioFederal();
        $s = SolicitacaoCadastro::factory()->create();

        $this->autenticar($user)
            ->patchJson("/api/solicitacoes-cadastro/{$s->id}", [
                'status'        => 'reprovado',
                'justificativa' => 'Documentação insuficiente para aprovação do cadastro.',
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Solicitação reprovada.');
    }

    #[Test]
    public function reprovacao_exige_justificativa(): void
    {
        $user = $this->criarUsuarioFederal();
        $s = SolicitacaoCadastro::factory()->create();

        $this->autenticar($user)
            ->patchJson("/api/solicitacoes-cadastro/{$s->id}", [
                'status' => 'reprovado',
            ])
            ->assertStatus(422);
    }

    #[Test]
    public function aprovacao_exige_perfil_id(): void
    {
        $user = $this->criarUsuarioFederal();
        $s = SolicitacaoCadastro::factory()->create();

        $this->autenticar($user)
            ->patchJson("/api/solicitacoes-cadastro/{$s->id}", [
                'status' => 'aprovado',
            ])
            ->assertStatus(422);
    }

    #[Test]
    public function nao_pode_avaliar_solicitacao_ja_avaliada(): void
    {
        $user = $this->criarUsuarioFederal();
        $perfil = Perfil::factory()->create();
        $s = SolicitacaoCadastro::factory()->aprovado()->create();

        $this->autenticar($user)
            ->patchJson("/api/solicitacoes-cadastro/{$s->id}", [
                'status'    => 'aprovado',
                'perfil_id' => $perfil->id,
            ])
            ->assertStatus(422);
    }

    #[Test]
    public function solicitacao_inexistente_retorna_404(): void
    {
        $user = $this->criarUsuarioFederal();
        $perfil = Perfil::factory()->create();

        $this->autenticar($user)
            ->patchJson('/api/solicitacoes-cadastro/99999', [
                'status' => 'aprovado', 'perfil_id' => $perfil->id,
            ])
            ->assertStatus(404);
    }

    #[Test]
    public function registra_auditoria_ao_avaliar(): void
    {
        $user = $this->criarUsuarioFederal();
        $perfil = Perfil::factory()->create();
        $s = SolicitacaoCadastro::factory()->create();

        $this->autenticar($user)
            ->patchJson("/api/solicitacoes-cadastro/{$s->id}", [
                'status' => 'aprovado', 'perfil_id' => $perfil->id,
            ]);

        $this->assertDatabaseHas('auditoria_log', [
            'acao' => 'gerenciar_cadastros.avaliacao',
        ]);
    }

    #[Test]
    public function aprovacao_vincula_perfil_ao_usuario(): void
    {
        $user = $this->criarUsuarioFederal();
        $perfil = Perfil::factory()->create();
        $s = SolicitacaoCadastro::factory()->create();

        $this->autenticar($user)
            ->patchJson("/api/solicitacoes-cadastro/{$s->id}", [
                'status' => 'aprovado', 'perfil_id' => $perfil->id,
            ]);

        $this->assertDatabaseHas('perfil_usuario', [
            'perfil_id' => $perfil->id,
        ]);
    }
}
