<?php

namespace Tests\Integracao\Solicitacoes;

use App\Models\PerfilUsuario;
use App\Models\SolicitacaoCadastro;
use App\Models\StatusSolicitacao;
use App\Models\Usuario;
use Illuminate\Routing\Middleware\ThrottleRequests;
use PHPUnit\Framework\Attributes\Test;
use Tests\Integracao\TestCase;

class SolicitacoesCadastroEndpointsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ThrottleRequests::class);
    }

    #[Test]
    public function verifica_cpf_indisponivel_quando_ja_existe_perfil_ativo(): void
    {
        $response = $this->getJson('/api/solicitacoes-cadastro/verificar-cpf?cpf=11144477735');

        $response
            ->assertOk()
            ->assertJson([
                'disponivel' => false,
                'mensagem' => 'Este CPF jÃ¡ possui perfil ativo no sistema.',
            ]);
    }

    #[Test]
    public function verifica_cpf_indisponivel_quando_ja_existe_solicitacao_em_analise(): void
    {
        $usuario = Usuario::factory()->create([
            'cpf' => Usuario::factory()->make()->cpf,
            'govbr_sub' => 'teste-endpoint-cpf-em-analise',
        ]);

        SolicitacaoCadastro::factory()->create([
            'user_id' => $usuario->id,
            'status_id' => StatusSolicitacao::idPorNome(StatusSolicitacao::EM_ANALISE),
        ]);

        $response = $this->getJson('/api/solicitacoes-cadastro/verificar-cpf?cpf=' . $usuario->cpf);

        $response
            ->assertOk()
            ->assertJson([
                'disponivel' => false,
                'mensagem' => 'JÃ¡ existe uma solicitaÃ§Ã£o em anÃ¡lise para este CPF.',
            ]);
    }

    #[Test]
    public function cria_solicitacao_publica_com_dados_controlados(): void
    {
        $cpf = Usuario::factory()->make()->cpf;

        $response = $this->postJson('/api/solicitacoes-cadastro', [
            'nome' => 'Solicitante Publico',
            'CPF' => $cpf,
            'emailInstitucional' => 'solicitante.publico@teste.gov.br',
            'telefoneInstitucional' => '61999887766',
            'telefonePessoal' => '61988776655',
            'esferaAtuacao' => 'federal',
            'uf' => 'DF',
            'municipio' => 'BrasÃ­lia',
            'orgao' => 'Ministerio de Testes',
            'cargo' => 'Analista',
        ]);

        $response
            ->assertCreated()
            ->assertJsonStructure(['message', 'solicitacao_id']);

        $this->assertDatabaseHas('usuarios', [
            'cpf' => $cpf,
            'nome' => 'Solicitante Publico',
        ]);

        $this->assertDatabaseHas('solicitacoes_cadastro', [
            'email_institucional' => 'solicitante.publico@teste.gov.br',
            'orgao' => 'Ministerio de Testes',
        ]);
    }

    #[Test]
    public function cria_solicitacao_interna_com_cpf_do_formulario_sem_sobrescrever_com_usuario_logado(): void
    {
        $operador = $this->autenticarComoFederal();
        $cpfFormulario = Usuario::factory()->make()->cpf;

        $response = $this->postJson('/api/solicitacoes-cadastro', [
            'nome' => 'Novo Cadastrado Interno',
            'CPF' => $cpfFormulario,
            'emailInstitucional' => 'novo.interno@teste.gov.br',
            'telefoneInstitucional' => '61999887766',
            'telefonePessoal' => '61988776655',
            'esferaAtuacao' => 'federal',
            'uf' => 'DF',
            'municipio' => 'BrasÃ­lia',
            'orgao' => 'Ministerio de Testes',
            'cargo' => 'Analista',
            'perfilId' => $this->perfilPorNome('Gestor Federal')->id,
            'vigenciaInicio' => now()->toDateString(),
        ]);

        $response
            ->assertCreated()
            ->assertJsonStructure(['message', 'solicitacao_id']);

        $this->assertNotSame($operador->cpf, $cpfFormulario);

        $this->assertDatabaseHas('usuarios', [
            'cpf' => $cpfFormulario,
            'nome' => 'Novo Cadastrado Interno',
        ]);

        $this->assertDatabaseHas('solicitacoes_cadastro', [
            'email_institucional' => 'novo.interno@teste.gov.br',
            'orgao' => 'Ministerio de Testes',
        ]);

        $solicitacaoId = (int) $response->json('solicitacao_id');
        $usuarioDaSolicitacao = SolicitacaoCadastro::query()->findOrFail($solicitacaoId)->user;

        $this->assertNotNull($usuarioDaSolicitacao);
        $this->assertSame($cpfFormulario, $usuarioDaSolicitacao->cpf);
        $this->assertNotSame($operador->id, $usuarioDaSolicitacao->id);
    }

    #[Test]
    public function lista_solicitacoes_visiveis_para_operador_federal(): void
    {
        $this->autenticarComoFederal();

        $response = $this->getJson('/api/solicitacoes-cadastro');

        $response
            ->assertOk()
            ->assertJsonStructure(['data'])
            ->assertJsonFragment(['nome' => 'Fernanda Lima']);
    }

    #[Test]
    public function detalha_solicitacao_em_analise(): void
    {
        $this->autenticarComoFederal();
        $solicitacao = $this->solicitacaoPorCpfEStatus('55566677788', StatusSolicitacao::EM_ANALISE);

        $response = $this->getJson("/api/solicitacoes-cadastro/{$solicitacao->id}");

        $response
            ->assertOk()
            ->assertJsonPath('id', $solicitacao->id)
            ->assertJsonPath('status', StatusSolicitacao::EM_ANALISE);
    }

    #[Test]
    public function aprova_solicitacao_e_cria_vinculo_de_perfil(): void
    {
        $this->autenticarComoFederal();
        $solicitacao = $this->solicitacaoPorCpfEStatus('55566677788', StatusSolicitacao::EM_ANALISE);
        $perfil = $this->perfilPorNome('Gestor Estadual');

        $response = $this->patchJson("/api/solicitacoes-cadastro/{$solicitacao->id}", [
            'status' => 'aprovado',
            'perfil_id' => $perfil->id,
            'vigencia_inicio' => now()->toDateString(),
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('solicitacoes_cadastro', [
            'id' => $solicitacao->id,
            'status_id' => StatusSolicitacao::idPorNome(StatusSolicitacao::APROVADO),
        ]);

        $usuarioId = SolicitacaoCadastro::query()->findOrFail($solicitacao->id)->user_id;

        $this->assertDatabaseHas('perfil_usuario', [
            'usuario_id' => $usuarioId,
            'perfil_id' => $perfil->id,
        ]);
    }

    #[Test]
    public function adiciona_ativa_e_desativa_perfil_vinculado_em_solicitacao_aprovada(): void
    {
        $this->autenticarComoFederal();

        $solicitacao = $this->solicitacaoPorCpfEStatus('98765432100', StatusSolicitacao::APROVADO);
        $perfil = $this->perfilPorNome('Administrador Municipal');
        $usuarioId = SolicitacaoCadastro::query()->findOrFail($solicitacao->id)->user_id;
        $perfilOriginal = PerfilUsuario::query()
            ->where('usuario_id', $usuarioId)
            ->where('ativo', true)
            ->firstOrFail();

        $adicao = $this->postJson("/api/solicitacoes-cadastro/{$solicitacao->id}/perfis", [
            'perfil_id' => $perfil->id,
            'vigencia_inicio' => now()->toDateString(),
        ]);

        $adicao->assertCreated();

        $perfilAdicionado = PerfilUsuario::query()
            ->where('usuario_id', $usuarioId)
            ->where('perfil_id', $perfil->id)
            ->firstOrFail();

        $ativacao = $this->patchJson(
            "/api/solicitacoes-cadastro/{$solicitacao->id}/perfis/{$perfilOriginal->id}/ativar"
        );

        $ativacao
            ->assertOk()
            ->assertJsonPath('data.id', $perfilOriginal->id);

        $this->assertDatabaseHas('perfil_usuario', [
            'id' => $perfilOriginal->id,
            'ativo' => true,
        ]);

        $this->assertDatabaseHas('perfil_usuario', [
            'id' => $perfilAdicionado->id,
            'ativo' => false,
        ]);

        $desativacao = $this->patchJson(
            "/api/solicitacoes-cadastro/{$solicitacao->id}/perfis/{$perfilOriginal->id}/desativar"
        );

        $desativacao
            ->assertOk()
            ->assertJsonPath('data.id', $perfilOriginal->id);

        $this->assertDatabaseHas('perfil_usuario', [
            'id' => $perfilOriginal->id,
            'ativo' => false,
        ]);
    }

    #[Test]
    public function retorna_nao_encontrado_nos_endpoints_autenticados_quando_a_solicitacao_nao_existe(): void
    {
        $this->autenticarComoFederal();

        $this->getJson('/api/solicitacoes-cadastro/999999')
            ->assertNotFound()
            ->assertJsonPath('message', 'SolicitaÃ§Ã£o nÃ£o encontrada.');

        $this->patchJson('/api/solicitacoes-cadastro/999999', [
            'status' => 'aprovado',
            'perfil_id' => $this->perfilPorNome('Gestor Federal')->id,
            'vigencia_inicio' => now()->toDateString(),
        ])->assertNotFound()
            ->assertJsonPath('message', 'SolicitaÃ§Ã£o nÃ£o encontrada.');

        $this->patchJson('/api/solicitacoes-cadastro/999999/perfis/1/ativar')
            ->assertNotFound()
            ->assertJsonPath('message', 'SolicitaÃ§Ã£o nÃ£o encontrada.');

        $this->patchJson('/api/solicitacoes-cadastro/999999/perfis/1/desativar')
            ->assertNotFound()
            ->assertJsonPath('message', 'SolicitaÃ§Ã£o nÃ£o encontrada.');

        $this->postJson('/api/solicitacoes-cadastro/999999/perfis', [
            'perfil_id' => $this->perfilPorNome('Administrador Municipal')->id,
            'vigencia_inicio' => now()->toDateString(),
        ])->assertNotFound()
            ->assertJsonPath('message', 'SolicitaÃ§Ã£o nÃ£o encontrada.');
    }
}

