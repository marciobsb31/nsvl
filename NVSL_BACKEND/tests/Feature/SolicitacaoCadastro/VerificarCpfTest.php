<?php

namespace Tests\Feature\SolicitacaoCadastro;

use App\Models\SolicitacaoCadastro;
use App\Models\StatusSolicitacao;
use App\Models\Usuario;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class VerificarCpfTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);
    }

    #[Test]
    public function cpf_disponivel_retorna_true(): void
    {
        $this->getJson('/api/solicitacoes-cadastro/verificar-cpf?cpf=52998224725')
            ->assertOk()
            ->assertJsonPath('disponivel', true);
    }

    #[Test]
    public function cpf_com_usuario_existente_retorna_indisponivel(): void
    {
        $cpf = '52998224725';
        Usuario::factory()->create(['cpf' => $cpf]);

        $this->getJson("/api/solicitacoes-cadastro/verificar-cpf?cpf={$cpf}")
            ->assertOk()
            ->assertJsonPath('disponivel', false);
    }

    #[Test]
    public function cpf_com_solicitacao_em_analise_retorna_indisponivel(): void
    {
        $cpf = '52998224725';
        $usuario = Usuario::factory()->create(['cpf' => $cpf]);
        $statusEmAnalise = StatusSolicitacao::idPorNome(StatusSolicitacao::EM_ANALISE);

        SolicitacaoCadastro::factory()->create([
            'user_id'   => $usuario->id,
            'status_id' => $statusEmAnalise,
        ]);

        $this->getJson("/api/solicitacoes-cadastro/verificar-cpf?cpf={$cpf}")
            ->assertOk()
            ->assertJsonPath('disponivel', false);
    }

    #[Test]
    public function cpf_com_menos_de_11_digitos_retorna_indisponivel(): void
    {
        $this->getJson('/api/solicitacoes-cadastro/verificar-cpf?cpf=12345')
            ->assertOk()
            ->assertJsonPath('disponivel', false);
    }

    #[Test]
    public function cpf_invalido_retorna_indisponivel(): void
    {
        $this->getJson('/api/solicitacoes-cadastro/verificar-cpf?cpf=11111111111')
            ->assertOk()
            ->assertJsonPath('disponivel', false);
    }
}
