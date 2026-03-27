<?php

namespace Tests\Feature\SolicitacaoCadastro;

use App\Models\Esfera;
use App\Models\Municipio;
use App\Models\Perfil;
use App\Models\SolicitacaoCadastro;
use App\Models\StatusSolicitacao;
use App\Models\Uf;
use App\Models\Usuario;
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
        $this->seedDadosCadastroMinimos();
    }

    private function seedDadosCadastroMinimos(): void
    {
        Esfera::firstOrCreate(['nome' => 'Federal']);
        $uf = Uf::firstOrCreate(
            ['sigla' => 'DF'],
            ['nome' => 'Distrito Federal']
        );
        Municipio::firstOrCreate(['nome' => 'Brasília', 'uf_id' => $uf->id]);
    }

    private function dadosSolicitacao(array $override = []): array
    {
        return array_merge([
            'nome'                  => 'João da Silva',
            'CPF'                   => '52998224725',
            'emailInstitucional'    => 'joao@orgao.gov.br',
            'telefoneInstitucional' => '61999998888',
            'esferaAtuacao'         => 'federal',
            'uf'                    => 'DF',
            'municipio'             => 'Brasília',
            'orgao'                 => 'Ministério da Saúde',
            'cargo'                 => 'Analista',
            'aceiteTermo'           => true,
        ], $override);
    }

    #[Test]
    public function cria_solicitacao_publica_com_sucesso(): void
    {
        $this->postJson('/api/solicitacoes-cadastro', $this->dadosSolicitacao())
            ->assertStatus(201)
            ->assertJsonPath('message', 'Solicitação registrada com sucesso.');

        $this->assertDatabaseHas('usuarios', [
            'cpf'       => '52998224725',
            'govbr_sub' => 'pending-52998224725',
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
    public function aceite_termo_obrigatorio(): void
    {
        $this->postJson('/api/solicitacoes-cadastro', $this->dadosSolicitacao(['aceiteTermo' => false]))
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
        $usuario = Usuario::factory()->create(['cpf' => $cpf]);
        $statusEmAnalise = StatusSolicitacao::idPorNome(StatusSolicitacao::EM_ANALISE);

        SolicitacaoCadastro::factory()->create([
            'user_id'   => $usuario->id,
            'status_id' => $statusEmAnalise,
        ]);

        $this->postJson('/api/solicitacoes-cadastro', $this->dadosSolicitacao(['CPF' => $cpf]))
            ->assertStatus(422);
    }

    #[Test]
    public function usuario_autenticado_cria_com_perfil_e_vigencia(): void
    {
        $user = $this->criarUsuarioFederal();
        $perfil = Perfil::factory()->create(['nome' => 'Administrador Nacional', 'ativo' => true]);

        $this->autenticar($user)
            ->postJson('/api/solicitacoes-cadastro', $this->dadosSolicitacao([
                'perfilId'      => $perfil->id,
                'vigenciaInicio' => '2026-04-01',
            ]))
            ->assertStatus(201);

        $this->assertDatabaseHas('usuarios', [
            'cpf'       => '52998224725',
            'govbr_sub' => 'pending-52998224725',
        ]);

        $this->assertDatabaseHas('solicitacoes_cadastro', [
            'vigencia_inicio_solicitada' => '2026-04-01',
            'vigencia_fim_solicitada'    => null,
        ]);
    }

    #[Test]
    public function cadastro_interno_persiste_vigencia_fim_quando_informada(): void
    {
        $user = $this->criarUsuarioFederal();
        $perfil = Perfil::factory()->create(['nome' => 'Gestor Nacional', 'ativo' => true]);

        $this->autenticar($user)
            ->postJson('/api/solicitacoes-cadastro', $this->dadosSolicitacao([
                'perfilId'       => $perfil->id,
                'vigenciaInicio' => '2026-04-01',
                'vigenciaFim'    => '2026-12-31',
            ]))
            ->assertStatus(201);

        $this->assertDatabaseHas('solicitacoes_cadastro', [
            'vigencia_inicio_solicitada' => '2026-04-01',
            'vigencia_fim_solicitada'    => '2026-12-31',
        ]);
    }

    #[Test]
    public function operador_estadual_nao_registra_solicitacao_com_uf_diferente_da_lotacao(): void
    {
        Esfera::firstOrCreate(['nome' => 'Estadual']);
        $ufGo = Uf::firstOrCreate(['sigla' => 'GO'], ['nome' => 'Goiás']);
        Municipio::firstOrCreate(['nome' => 'Goiânia', 'uf_id' => $ufGo->id]);

        $operador = $this->criarUsuarioEstadual();
        $perfil = Perfil::factory()->create(['nome' => 'Gestor Estadual', 'ativo' => true]);

        $this->autenticar($operador)
            ->postJson('/api/solicitacoes-cadastro', $this->dadosSolicitacao([
                'CPF'            => '52998224725',
                'esferaAtuacao' => 'estadual',
                'uf'             => 'DF',
                'municipio'      => 'Brasília',
                'perfilId'       => $perfil->id,
                'vigenciaInicio' => '2026-04-01',
            ]))
            ->assertStatus(422)
            ->assertJsonFragment([
                'message' => 'A UF do solicitante deve ser a mesma da UF de lotação do seu usuário.',
            ]);
    }

    #[Test]
    public function operador_estadual_nao_registra_perfil_que_nao_seja_estadual(): void
    {
        Esfera::firstOrCreate(['nome' => 'Estadual']);
        $ufGo = Uf::firstOrCreate(['sigla' => 'GO'], ['nome' => 'Goiás']);
        Municipio::firstOrCreate(['nome' => 'Goiânia', 'uf_id' => $ufGo->id]);

        $operador = $this->criarUsuarioEstadual();
        $perfil = Perfil::factory()->create(['nome' => 'Gestor Nacional', 'ativo' => true]);

        $this->autenticar($operador)
            ->postJson('/api/solicitacoes-cadastro', $this->dadosSolicitacao([
                'CPF'            => '52998224725',
                'esferaAtuacao' => 'estadual',
                'uf'             => 'GO',
                'municipio'      => 'Goiânia',
                'perfilId'       => $perfil->id,
                'vigenciaInicio' => '2026-04-01',
            ]))
            ->assertStatus(422)
            ->assertJsonFragment([
                'message' => 'Seu nível de acesso só permite solicitar perfis do tipo estadual.',
            ]);
    }

    #[Test]
    public function operador_municipal_nao_registra_outro_municipio(): void
    {
        Esfera::firstOrCreate(['nome' => 'Municipal']);
        $ufGo = Uf::firstOrCreate(['sigla' => 'GO'], ['nome' => 'Goiás']);
        Municipio::firstOrCreate(['nome' => 'Alexânia', 'uf_id' => $ufGo->id]);
        Municipio::firstOrCreate(['nome' => 'Goiânia', 'uf_id' => $ufGo->id]);

        $operador = $this->criarUsuarioMunicipal();
        $perfil = Perfil::factory()->create(['nome' => 'Gestor Municipal', 'ativo' => true]);

        $this->autenticar($operador)
            ->postJson('/api/solicitacoes-cadastro', $this->dadosSolicitacao([
                'CPF'            => '52998224725',
                'esferaAtuacao' => 'municipal',
                'uf'             => 'GO',
                'municipio'      => 'Goiânia',
                'perfilId'       => $perfil->id,
                'vigenciaInicio' => '2026-04-01',
            ]))
            ->assertStatus(422)
            ->assertJsonFragment([
                'message' => 'O município do solicitante deve ser o mesmo do município de lotação do seu usuário.',
            ]);
    }

    #[Test]
    public function cadastro_interno_nao_reutiliza_govbr_sub_do_operador(): void
    {
        $operador = $this->criarUsuarioFederal(['govbr_sub' => 'teste-federal-001']);
        $perfil = Perfil::factory()->create(['nome' => 'Gestor Nacional', 'ativo' => true]);

        $this->autenticar($operador)
            ->postJson('/api/solicitacoes-cadastro', $this->dadosSolicitacao([
                'CPF'              => '52998224725',
                'perfilId'         => $perfil->id,
                'vigenciaInicio'   => '2026-04-01',
            ]))
            ->assertStatus(201);

        $this->assertDatabaseHas('usuarios', [
            'cpf'       => '52998224725',
            'govbr_sub' => 'pending-52998224725',
        ]);
        $this->assertDatabaseHas('usuarios', [
            'govbr_sub' => 'teste-federal-001',
        ]);
    }

    #[Test]
    public function municipio_inexistente_para_uf_retorna_422(): void
    {
        $this->postJson('/api/solicitacoes-cadastro', $this->dadosSolicitacao([
            'municipio' => 'Cidade Fictícia Inexistente',
        ]))
            ->assertStatus(422)
            ->assertJsonFragment(['message' => 'Município "Cidade Fictícia Inexistente" não encontrado para a UF DF. Verifique a grafia ou selecione na lista oficial.']);
    }

    #[Test]
    public function registra_auditoria_ao_criar(): void
    {
        $this->postJson('/api/solicitacoes-cadastro', $this->dadosSolicitacao())
            ->assertStatus(201);

        $this->assertDatabaseHas('auditoria_log', [
            'acao' => 'solicitacao_cadastro.criada',
        ]);
    }
}
