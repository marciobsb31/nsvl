<?php

namespace Tests\Feature\SolicitacaoCadastro;

use App\Models\Perfil;
use App\Models\SolicitacaoCadastro;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;
use Tests\Traits\ActingAsUserTrait;
use PHPUnit\Framework\Attributes\Test;

class CriarSolicitacaoTest extends TestCase
{
    use ActingAsUserTrait;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);
    }

    private function dadosSolicitacao(array $override = []): array
    {
        return array_merge([
            'nome'                  => 'João da Silva',
            'CPF'                   => '52998224725',
            'emailInstitucional'    => 'joao@orgao.gov.br',
            'telefoneInstitucional' => '61999998888',
            'telefonePessoal'       => '61988887777',
            'esferaAtuacao'         => 'federal',
            'uf'                    => 'DF',
            'municipio'             => 'Brasília',
            'orgao'                 => 'Ministério da Saúde',
            'cargo'                 => 'Analista',
        ], $override);
    }

    #[Test]
    public function cria_solicitacao_publica_com_sucesso(): void
    {
        $this->postJson('/api/solicitacoes-cadastro', $this->dadosSolicitacao())
            ->assertStatus(201)
            ->assertJsonPath('message', 'Solicitação registrada com sucesso.');

        $this->assertDatabaseHas('solicitacoes_cadastro', [
            'nome'   => 'João da Silva',
            'status' => 'em_analise',
        ]);
    }

    #[Test]
    public function cpf_obrigatorio(): void
    {
        $this->postJson('/api/solicitacoes-cadastro', $this->dadosSolicitacao(['CPF' => '']))
            ->assertStatus(422);
    }

    #[Test]
    public function cpf_invalido_retorna_422(): void
    {
        $this->postJson('/api/solicitacoes-cadastro', $this->dadosSolicitacao(['CPF' => '00000000000']))
            ->assertStatus(422);
    }

    #[Test]
    public function email_institucional_obrigatorio(): void
    {
        $this->postJson('/api/solicitacoes-cadastro', $this->dadosSolicitacao(['emailInstitucional' => '']))
            ->assertStatus(422);
    }

    #[Test]
    public function esfera_obrigatoria_e_valida(): void
    {
        $this->postJson('/api/solicitacoes-cadastro', $this->dadosSolicitacao(['esferaAtuacao' => 'invalida']))
            ->assertStatus(422);
    }

    #[Test]
    public function nao_permite_duplicata_em_analise(): void
    {
        $cpf = '52998224725';
        SolicitacaoCadastro::factory()->create([
            'cpf_hash' => User::hashCpf($cpf),
            'status'   => 'em_analise',
        ]);

        $this->postJson('/api/solicitacoes-cadastro', $this->dadosSolicitacao(['CPF' => $cpf]))
            ->assertStatus(422);
    }

    #[Test]
    public function usuario_autenticado_cria_com_perfil_e_vigencia(): void
    {
        $user = $this->criarUsuarioFederal();
        $perfil = Perfil::factory()->federal()->create(['nome' => 'Administrador Nacional']);

        $this->autenticar($user)
            ->postJson('/api/solicitacoes-cadastro', $this->dadosSolicitacao([
                'perfilId'      => $perfil->id,
                'vigenciaInicio' => '2026-04-01',
            ]))
            ->assertStatus(201);
    }

    #[Test]
    public function registra_auditoria_ao_criar(): void
    {
        $this->postJson('/api/solicitacoes-cadastro', $this->dadosSolicitacao())
            ->assertStatus(201);

        $this->assertDatabaseHas('auditoria_log', [
            'action' => 'solicitacao_cadastro.criada',
        ]);
    }
}
