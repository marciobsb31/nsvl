<?php

namespace Tests\Feature\SolicitacaoCadastro;

use App\Models\Perfil;
use App\Models\PerfilUsuario;
use App\Models\SolicitacaoCadastro;
use App\Models\Usuario;
use Tests\TestCase;
use Tests\Traits\ActingAsUserTrait;
use PHPUnit\Framework\Attributes\Test;

class GerenciarPerfisVinculadosTest extends TestCase
{
    use ActingAsUserTrait;

    private function criarCenarioAprovado(): array
    {
        $usuarioSolicitante = Usuario::factory()->create(['cpf' => '52998224725']);
        $solicitacao = SolicitacaoCadastro::factory()->aprovado()->create([
            'user_id' => $usuarioSolicitante->id,
        ]);
        $perfil = Perfil::factory()->create();
        $pu = PerfilUsuario::create([
            'usuario_id' => $usuarioSolicitante->id,
            'perfil_id'  => $perfil->id,
            'data_inicio_vigencia' => now()->subDay()->toDateString(),
        ]);

        return compact('solicitacao', 'usuarioSolicitante', 'perfil', 'pu');
    }

    #[Test]
    public function adiciona_perfil_vinculado_a_solicitacao_aprovada(): void
    {
        $admin = $this->criarUsuarioFederal();
        $cenario = $this->criarCenarioAprovado();
        $novoPerfil = Perfil::factory()->create();

        $this->autenticar($admin)
            ->postJson("/api/solicitacoes-cadastro/{$cenario['solicitacao']->id}/perfis", [
                'perfil_id' => $novoPerfil->id,
            ])
            ->assertStatus(201)
            ->assertJsonPath('message', 'Perfil vinculado adicionado com sucesso.');
    }

    #[Test]
    public function nao_permite_duplicar_vinculo_de_perfil(): void
    {
        $admin = $this->criarUsuarioFederal();
        $cenario = $this->criarCenarioAprovado();

        $this->autenticar($admin)
            ->postJson("/api/solicitacoes-cadastro/{$cenario['solicitacao']->id}/perfis", [
                'perfil_id' => $cenario['perfil']->id,
            ])
            ->assertStatus(422);
    }

    #[Test]
    public function ativa_perfil_vinculado(): void
    {
        $admin = $this->criarUsuarioFederal();
        $cenario = $this->criarCenarioAprovado();

        $this->autenticar($admin)
            ->patchJson("/api/solicitacoes-cadastro/{$cenario['solicitacao']->id}/perfis/{$cenario['pu']->id}/ativar")
            ->assertOk()
            ->assertJsonPath('message', 'Perfil vinculado ativado com sucesso.');
    }

    #[Test]
    public function desativa_perfil_vinculado(): void
    {
        $admin = $this->criarUsuarioFederal();
        $cenario = $this->criarCenarioAprovado();

        $this->autenticar($admin)
            ->patchJson("/api/solicitacoes-cadastro/{$cenario['solicitacao']->id}/perfis/{$cenario['pu']->id}/desativar")
            ->assertOk()
            ->assertJsonPath('message', 'Perfil vinculado desativado com sucesso.');

        $this->assertNotNull($cenario['pu']->fresh()->data_fim_vigencia);
    }

    #[Test]
    public function solicitacao_inexistente_retorna_404(): void
    {
        $admin = $this->criarUsuarioFederal();
        $perfil = Perfil::factory()->create();

        $this->autenticar($admin)
            ->postJson('/api/solicitacoes-cadastro/99999/perfis', ['perfil_id' => $perfil->id])
            ->assertStatus(404);
    }
}
