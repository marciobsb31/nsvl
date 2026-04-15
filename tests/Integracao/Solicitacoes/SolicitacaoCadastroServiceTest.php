<?php

namespace Tests\Integracao\Solicitacoes;

use App\Exceptions\ApiException;
use App\Models\Perfil;
use App\Models\PerfilUsuario;
use App\Models\SolicitacaoCadastro;
use App\Models\StatusSolicitacao;
use App\Models\Usuario;
use App\Services\SolicitacaoCadastro\SolicitacaoCadastroService;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use Tests\Integracao\TestCase;

class SolicitacaoCadastroServiceTest extends TestCase
{
    #[Test]
    public function verificar_cpf_cobre_cenarios_invalidos_e_disponivel(): void
    {
        $service = app(SolicitacaoCadastroService::class);
        $cpfDisponivel = Usuario::factory()->make()->cpf;

        $this->assertSame(
            ['disponivel' => false, 'mensagem' => 'Informe um CPF com 11 dígitos.'],
            $service->verificarCpf('123'),
        );

        $this->assertSame(
            ['disponivel' => false, 'mensagem' => 'CPF inválido. Verifique os dígitos informados.'],
            $service->verificarCpf('12345678901'),
        );

        $res = $service->verificarCpf($cpfDisponivel);
        $this->assertSame(true, $res['disponivel']);
        $this->assertSame('CPF disponível para cadastro.', $res['mensagem']);
        $this->assertIsArray($res['perfis_disponiveis'] ?? null);
    }

    #[Test]
    public function verificar_cpf_indisponivel_quando_ja_existe_solicitacao_em_analise(): void
    {
        $service = app(SolicitacaoCadastroService::class);
        $usuario = Usuario::factory()->create([
            'cpf' => Usuario::factory()->make()->cpf,
            'govbr_sub' => 'teste-cpf-em-analise',
        ]);

        SolicitacaoCadastro::factory()->create([
            'user_id' => $usuario->id,
            'status_id' => StatusSolicitacao::idPorNome(StatusSolicitacao::EM_ANALISE),
        ]);

        $res = $service->verificarCpf($usuario->cpf);
        $this->assertSame(false, $res['disponivel']);
        $this->assertSame('Já existe uma solicitação em análise para este CPF.', $res['mensagem']);
        $this->assertSame([], $res['perfis_disponiveis'] ?? []);
    }

    #[Test]
    public function rejeita_nova_solicitacao_quando_ja_existe_uma_em_analise_para_o_mesmo_usuario(): void
    {
        $service = app(SolicitacaoCadastroService::class);
        $usuario = Usuario::factory()->create([
            'cpf' => '40168299307',
            'govbr_sub' => 'usuario-em-analise',
        ]);

        SolicitacaoCadastro::factory()->create([
            'user_id' => $usuario->id,
            'status_id' => StatusSolicitacao::idPorNome(StatusSolicitacao::EM_ANALISE),
        ]);

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Já existe uma solicitação em análise para este CPF. Aguarde a avaliação da equipe gestora antes de enviar uma nova solicitação.');

        $service->criar(null, $this->payloadSolicitacaoPublica(cpf: '40168299307'));
    }

    #[Test]
    public function rejeita_solicitacao_publica_sem_cpf_informado(): void
    {
        $service = app(SolicitacaoCadastroService::class);

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('CPF é obrigatório para solicitação sem autenticação.');

        $payload = $this->payloadSolicitacaoPublica(cpf: Usuario::factory()->make()->cpf);
        unset($payload['CPF']);

        $service->criar(null, $payload);
    }

    #[Test]
    public function cria_usuario_com_govbr_sub_provisorio_unico_quando_pending_padrao_ja_existe(): void
    {
        $service = app(SolicitacaoCadastroService::class);
        $cpfAlvo = Usuario::factory()->make()->cpf;
        $cpfExistente = Usuario::factory()->make()->cpf;

        Usuario::factory()->create([
            'cpf' => $cpfExistente,
            'govbr_sub' => 'pending-' . $cpfAlvo,
        ]);

        $resultado = $service->criar(null, $this->payloadSolicitacaoPublica(
            cpf: $cpfAlvo,
            uf: 'GO',
            municipio: 'Goiânia',
        ));

        $this->assertSame(
            'Solicitação registrada com sucesso! Seu pedido está com o status "Em Análise" e será avaliado pela equipe gestora.',
            $resultado['message']
        );

        $usuarioNovo = Usuario::query()->where('cpf', $cpfAlvo)->firstOrFail();
        $this->assertStringStartsWith('pending-' . $cpfAlvo . '-', $usuarioNovo->govbr_sub);
    }

    #[Test]
    public function cria_solicitacao_interna_federal_sem_perfil_e_ignora_datas_invalidas(): void
    {
        $service = app(SolicitacaoCadastroService::class);
        $operador = $this->usuarioPorSub('teste-federal-001');
        $cpf = Usuario::factory()->make()->cpf;

        $resultado = $service->criar($operador, [
            ...$this->payloadSolicitacaoInterna(
                cpf: $cpf,
                esfera: 'federal',
                uf: 'DF',
                municipio: 'Brasília',
                perfilId: $this->perfilPorNome('Gestor Nacional')->id,
            ),
            'perfilId' => 0,
            'vigenciaInicio' => 'data-invalida',
            'vigenciaFim' => 'outra-data-invalida',
        ]);

        $this->assertSame(
            'Solicitação registrada com sucesso! Seu pedido está com o status "Em Análise" e será avaliado pela equipe gestora.',
            $resultado['message'],
        );

        $this->assertDatabaseHas('solicitacoes_cadastro', [
            'id' => $resultado['solicitacao_id'],
            'perfil_id_solicitado' => null,
            'vigencia_inicio_solicitada' => null,
            'vigencia_fim_solicitada' => null,
        ]);
    }

    #[Test]
    public function rejeita_solicitacao_quando_o_municipio_nao_pertence_a_uf_informada(): void
    {
        $service = app(SolicitacaoCadastroService::class);

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Município "Campinas" não encontrado para a UF GO. Verifique a grafia ou selecione na lista oficial.');

        $service->criar(null, $this->payloadSolicitacaoPublica(
            cpf: '40168299307',
            uf: 'GO',
            municipio: 'Campinas',
        ));
    }

    #[Test]
    public function rejeita_solicitacao_quando_o_municipio_nao_e_informado(): void
    {
        $service = app(SolicitacaoCadastroService::class);

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Informe o município.');

        $service->criar(null, $this->payloadSolicitacaoPublica(
            cpf: Usuario::factory()->make()->cpf,
            municipio: '   ',
        ));
    }

    #[Test]
    public function rejeita_operador_estadual_quando_a_solicitacao_e_de_outra_esfera(): void
    {
        $service = app(SolicitacaoCadastroService::class);
        $operador = $this->usuarioPorSub('teste-estadual-go-002');
        $perfil = Perfil::query()->where('nome', 'Gestor Estadual')->firstOrFail();

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Operadores da esfera estadual só podem registrar solicitações na esfera estadual.');

        $service->criar($operador, $this->payloadSolicitacaoInterna(
            cpf: '51230432006',
            esfera: 'federal',
            uf: 'GO',
            municipio: 'Goiânia',
            perfilId: $perfil->id,
        ));
    }

    #[Test]
    public function rejeita_operador_estadual_quando_a_uf_e_diferente_da_lotacao(): void
    {
        $service = app(SolicitacaoCadastroService::class);
        $operador = $this->usuarioPorSub('teste-estadual-go-002');
        $perfil = Perfil::query()->where('nome', 'Gestor Estadual')->firstOrFail();

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('A UF do solicitante deve ser a mesma da UF de lotação do seu usuário.');

        $service->criar($operador, $this->payloadSolicitacaoInterna(
            cpf: Usuario::factory()->make()->cpf,
            esfera: 'estadual',
            uf: 'DF',
            municipio: 'Brasília',
            perfilId: $perfil->id,
        ));
    }

    #[Test]
    public function rejeita_quando_usuario_ja_possui_o_mesmo_perfil_ativo(): void
    {
        $service = app(SolicitacaoCadastroService::class);
        $operador = $this->usuarioPorSub('teste-federal-001');

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('O perfil selecionado já está vinculado ao usuário ou não está disponível para solicitação.');

        $service->criar($operador, $this->payloadSolicitacaoInterna(
            cpf: '11144477735',
            esfera: 'federal',
            uf: 'DF',
            municipio: 'Brasília',
            perfilId: $this->perfilPorNome('Gestor Nacional')->id,
        ));
    }

    #[Test]
    public function rejeita_operador_municipal_quando_a_solicitacao_e_de_outra_esfera(): void
    {
        $service = app(SolicitacaoCadastroService::class);
        $operador = $this->usuarioPorSub('teste-municipal-alexania-003');
        $perfil = Perfil::query()->where('nome', 'Gestor Municipal')->firstOrFail();

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Operadores da esfera municipal só podem registrar solicitações na esfera municipal.');

        $service->criar($operador, $this->payloadSolicitacaoInterna(
            cpf: Usuario::factory()->make()->cpf,
            esfera: 'estadual',
            uf: 'GO',
            municipio: 'Alexânia',
            perfilId: $perfil->id,
        ));
    }

    #[Test]
    public function rejeita_operador_municipal_quando_o_municipio_nao_e_o_mesmo_do_contexto(): void
    {
        $service = app(SolicitacaoCadastroService::class);
        $operador = $this->usuarioPorSub('teste-municipal-alexania-003');
        $perfil = Perfil::query()->where('nome', 'Gestor Municipal')->firstOrFail();

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('O município do solicitante deve ser o mesmo do município de lotação do seu usuário.');

        $service->criar($operador, $this->payloadSolicitacaoInterna(
            cpf: '51230432006',
            esfera: 'municipal',
            uf: 'GO',
            municipio: 'Goiânia',
            perfilId: $perfil->id,
        ));
    }

    #[Test]
    public function rejeita_operador_municipal_quando_o_perfil_solicitado_nao_e_municipal(): void
    {
        $service = app(SolicitacaoCadastroService::class);
        $operador = $this->usuarioPorSub('teste-municipal-alexania-003');
        $perfil = Perfil::query()->where('nome', 'Gestor Estadual')->firstOrFail();

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Seu nível de acesso só permite solicitar perfis do tipo municipal.');

        $service->criar($operador, $this->payloadSolicitacaoInterna(
            cpf: Usuario::factory()->make()->cpf,
            esfera: 'municipal',
            uf: 'GO',
            municipio: 'Alexânia',
            perfilId: $perfil->id,
        ));
    }

    #[Test]
    public function rejeita_operador_estadual_quando_o_perfil_solicitado_nao_e_estadual(): void
    {
        $service = app(SolicitacaoCadastroService::class);
        $operador = $this->usuarioPorSub('teste-estadual-go-002');
        $perfil = Perfil::query()->where('nome', 'Gestor Municipal')->firstOrFail();

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Seu nível de acesso só permite solicitar perfis do tipo estadual.');

        $service->criar($operador, $this->payloadSolicitacaoInterna(
            cpf: '51230432006',
            esfera: 'estadual',
            uf: 'GO',
            municipio: 'Goiânia',
            perfilId: $perfil->id,
        ));
    }

    #[Test]
    public function rejeita_operador_restrito_quando_o_perfil_informado_e_invalido(): void
    {
        $service = app(SolicitacaoCadastroService::class);
        $operador = $this->usuarioPorSub('teste-estadual-go-002');

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Perfil informado é inválido.');

        $service->criar($operador, $this->payloadSolicitacaoInterna(
            cpf: Usuario::factory()->make()->cpf,
            esfera: 'estadual',
            uf: 'GO',
            municipio: 'Goiânia',
            perfilId: 999999,
        ));
    }

    #[Test]
    public function reprova_solicitacao_e_persiste_justificativa(): void
    {
        $service = app(SolicitacaoCadastroService::class);
        $operador = $this->usuarioPorSub('teste-federal-001');
        $solicitacao = $this->solicitacaoPorCpfEStatus('55566677788', StatusSolicitacao::EM_ANALISE);

        $resultado = $service->avaliar($operador, $solicitacao->id, [
            'status' => 'reprovado',
            'justificativa' => 'Documentação enviada está incompleta.',
        ]);

        $this->assertSame('Solicitação reprovada.', $resultado['message']);
        $this->assertDatabaseHas('solicitacoes_cadastro', [
            'id' => $solicitacao->id,
            'status_id' => StatusSolicitacao::idPorNome(StatusSolicitacao::REPROVADO),
            'justificativa_reprovacao' => 'Documentação enviada está incompleta.',
        ]);
    }

    #[Test]
    public function aprova_solicitacao_desativando_perfis_anteriores_e_vinculando_o_novo_perfil(): void
    {
        $service = app(SolicitacaoCadastroService::class);
        $operador = $this->usuarioPorSub('teste-federal-001');
        $solicitacao = $this->solicitacaoPorCpfEStatus('55566677788', StatusSolicitacao::EM_ANALISE);
        $perfilAntigo = $this->perfilPorNome('Gestor Municipal');
        $perfilNovo = $this->perfilPorNome('Administrador Estadual');

        PerfilUsuario::create([
            'usuario_id' => $solicitacao->user_id,
            'perfil_id' => $perfilAntigo->id,
            'data_inicio_vigencia' => now()->subDay()->toDateString(),
            'data_fim_vigencia' => null,
            'ativo' => true,
        ]);

        $resultado = $service->avaliar($operador, $solicitacao->id, [
            'status' => 'aprovado',
            'perfil_id' => $perfilNovo->id,
            'vigencia_inicio' => now()->toDateString(),
            'vigencia_fim' => now()->addDays(30)->toDateString(),
        ]);

        $this->assertSame('Solicitação aprovada.', $resultado['message']);
        $this->assertSame('aprovado', $resultado['data']['status']);

        $this->assertDatabaseHas('perfil_usuario', [
            'usuario_id' => $solicitacao->user_id,
            'perfil_id' => $perfilAntigo->id,
            'ativo' => false,
        ]);

        $this->assertDatabaseHas('perfil_usuario', [
            'usuario_id' => $solicitacao->user_id,
            'perfil_id' => $perfilNovo->id,
            'ativo' => true,
        ]);
    }

    #[Test]
    public function rejeita_avaliacao_de_solicitacao_que_nao_esta_mais_em_analise(): void
    {
        $service = app(SolicitacaoCadastroService::class);
        $operador = $this->usuarioPorSub('teste-federal-001');
        $solicitacao = $this->solicitacaoPorCpfEStatus('98765432100', StatusSolicitacao::APROVADO);

        $this->expectException(ValidationException::class);

        try {
            $service->avaliar($operador, $solicitacao->id, [
                'status' => 'reprovado',
                'justificativa' => 'Tentativa inválida de reavaliar.',
            ]);
        } catch (ValidationException $exception) {
            $this->assertSame(
                'Apenas solicitações em análise podem ser aprovadas ou reprovadas.',
                $exception->errors()['status'][0] ?? null,
            );

            throw $exception;
        }
    }

    #[Test]
    public function avaliar_lanca_erro_quando_a_solicitacao_nao_existe(): void
    {
        $service = app(SolicitacaoCadastroService::class);
        $operador = $this->usuarioPorSub('teste-federal-001');

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Solicitação não encontrada.');

        $service->avaliar($operador, 999999, [
            'status' => 'aprovado',
            'perfil_id' => $this->perfilPorNome('Gestor Nacional')->id,
        ]);
    }

    #[Test]
    public function detalhar_retorna_historico_de_reprovacoes_quando_existente(): void
    {
        $service = app(SolicitacaoCadastroService::class);
        $operador = $this->usuarioPorSub('teste-federal-001');
        $solicitacao = $this->solicitacaoPorCpfEStatus('55566677788', StatusSolicitacao::EM_ANALISE);

        $service->avaliar($operador, $solicitacao->id, [
            'status' => 'reprovado',
            'justificativa' => 'Documentação enviada está incompleta.',
        ]);

        $detalhe = $service->detalhar($operador, $solicitacao->id);

        $this->assertSame('reprovado', $detalhe['status']);
        $this->assertCount(1, $detalhe['historico_reprovacoes']);
        $this->assertSame(
            'Documentação enviada está incompleta.',
            $detalhe['historico_reprovacoes'][0]['motivo'],
        );
    }

    #[Test]
    public function detalhar_reprovada_sem_log_retorna_fallback_da_justificativa_da_solicitacao(): void
    {
        $service = app(SolicitacaoCadastroService::class);
        $operador = $this->usuarioPorSub('teste-federal-001');
        $usuario = Usuario::factory()->create([
            'cpf' => Usuario::factory()->make()->cpf,
            'govbr_sub' => 'sub-reprovacao-fallback',
        ]);

        $solicitacao = SolicitacaoCadastro::factory()->reprovado()->create([
            'user_id' => $usuario->id,
            'status_id' => StatusSolicitacao::idPorNome(StatusSolicitacao::REPROVADO),
            'justificativa_reprovacao' => 'Pendência documental sem auditoria detalhada.',
        ]);

        $detalhe = $service->detalhar($operador, $solicitacao->id);

        $this->assertSame('reprovado', $detalhe['status']);
        $this->assertNotEmpty($detalhe['historico_reprovacoes']);
        $this->assertSame(
            'Pendência documental sem auditoria detalhada.',
            $detalhe['historico_reprovacoes'][0]['motivo'],
        );
    }

    #[Test]
    public function detalhar_lanca_erro_quando_a_solicitacao_nao_existe(): void
    {
        $service = app(SolicitacaoCadastroService::class);
        $operador = $this->usuarioPorSub('teste-federal-001');

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Solicitação não encontrada.');

        $service->detalhar($operador, 999999);
    }

    #[Test]
    public function desativar_perfil_ajusta_data_fim_para_nao_ficar_antes_da_data_inicio(): void
    {
        $service = app(SolicitacaoCadastroService::class);
        $operador = $this->usuarioPorSub('teste-federal-001');
        $solicitacao = $this->solicitacaoPorCpfEStatus('98765432100', StatusSolicitacao::APROVADO);
        $usuarioId = SolicitacaoCadastro::query()->findOrFail($solicitacao->id)->user_id;
        $perfil = PerfilUsuario::query()
            ->where('usuario_id', $usuarioId)
            ->where('ativo', true)
            ->firstOrFail();

        $perfil->update([
            'data_inicio_vigencia' => now()->addDays(5)->toDateString(),
        ]);

        $service->desativarPerfil($operador, $solicitacao->id, $perfil->id);

        $perfil->refresh();

        $this->assertSame(
            $perfil->data_inicio_vigencia?->toDateString(),
            $perfil->data_fim_vigencia?->toDateString(),
        );
    }

    #[Test]
    public function bloqueia_autodesativacao_para_usuario_que_nao_e_gestor_nacional(): void
    {
        $service = app(SolicitacaoCadastroService::class);
        $operador = $this->usuarioPorSub('teste-estadual-go-002');

        $solicitacao = SolicitacaoCadastro::factory()->create([
            'user_id' => $operador->id,
            'status_id' => StatusSolicitacao::idPorNome(StatusSolicitacao::APROVADO),
        ]);

        $perfil = PerfilUsuario::query()
            ->where('usuario_id', $operador->id)
            ->where('ativo', true)
            ->firstOrFail();

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Você não pode desativar o próprio cadastro. Apenas Gestor Nacional pode executar essa ação no próprio usuário.');

        $service->desativarPerfil($operador, $solicitacao->id, $perfil->id);
    }

    #[Test]
    public function permite_autodesativacao_quando_operador_e_gestor_nacional(): void
    {
        $service = app(SolicitacaoCadastroService::class);
        $operador = $this->usuarioPorSub('teste-federal-001');

        $solicitacao = SolicitacaoCadastro::factory()->create([
            'user_id' => $operador->id,
            'status_id' => StatusSolicitacao::idPorNome(StatusSolicitacao::APROVADO),
        ]);

        $perfil = PerfilUsuario::query()
            ->where('usuario_id', $operador->id)
            ->where('ativo', true)
            ->firstOrFail();

        $resultado = $service->desativarPerfil($operador, $solicitacao->id, $perfil->id);

        $this->assertSame('Perfil vinculado desativado com sucesso.', $resultado['message']);

        $perfil->refresh();
        $this->assertFalse((bool) $perfil->ativo);
    }

    #[Test]
    public function listar_aplica_filtros_textuais_e_de_catalogo(): void
    {
        $service = app(SolicitacaoCadastroService::class);
        $operador = $this->usuarioPorSub('teste-federal-001');

        $resultado = $service->listar($operador, [
            'cpf' => '98765432100',
            'nome' => 'Ana',
            'uf' => 'GO',
            'municipio' => 'Alex',
            'orgao' => 'Prefeitura',
            'esfera' => 'municipal',
            'status' => 'aprovado',
        ]);

        $this->assertCount(1, $resultado);
        $this->assertSame('Ana Costa Municipal', $resultado[0]['nome']);
        $this->assertSame('GO', $resultado[0]['uf']);
        $this->assertSame('Prefeitura Municipal de Alexânia', $resultado[0]['orgao']);
    }

    #[Test]
    public function detalhar_solicitacao_aprovada_retorna_perfis_vinculados(): void
    {
        $service = app(SolicitacaoCadastroService::class);
        $operador = $this->usuarioPorSub('teste-federal-001');
        $solicitacao = $this->solicitacaoPorCpfEStatus('98765432100', StatusSolicitacao::APROVADO);

        $detalhe = $service->detalhar($operador, $solicitacao->id);

        $this->assertNotEmpty($detalhe['perfis_vinculados']);
        $this->assertSame('Gestor Municipal', $detalhe['perfis_vinculados'][0]['perfil']);
        $this->assertSame('Municipal', $detalhe['perfis_vinculados'][0]['esfera']);
    }

    #[Test]
    public function rejeita_solicitacao_quando_a_esfera_e_invalida(): void
    {
        $service = app(SolicitacaoCadastroService::class);

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Esfera de atuação inválida ou não cadastrada.');

        $service->criar(null, [
            ...$this->payloadSolicitacaoPublica(cpf: Usuario::factory()->make()->cpf),
            'esferaAtuacao' => 'internacional',
        ]);
    }

    #[Test]
    public function rejeita_solicitacao_quando_a_uf_e_invalida(): void
    {
        $service = app(SolicitacaoCadastroService::class);

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('UF inválida ou não cadastrada no sistema.');

        $service->criar(null, $this->payloadSolicitacaoPublica(
            cpf: Usuario::factory()->make()->cpf,
            uf: 'ZZ',
            municipio: 'Cidade Teste',
        ));
    }

    #[Test]
    public function rejeita_solicitacao_quando_operador_estadual_nao_informa_perfil(): void
    {
        $service = app(SolicitacaoCadastroService::class);
        $operador = $this->usuarioPorSub('teste-estadual-go-002');

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Selecione o perfil solicitado.');

        $payload = $this->payloadSolicitacaoInterna(
            cpf: Usuario::factory()->make()->cpf,
            esfera: 'estadual',
            uf: 'GO',
            municipio: 'Goiânia',
            perfilId: $this->perfilPorNome('Gestor Estadual')->id,
        );

        unset($payload['perfilId']);

        $service->criar($operador, $payload);
    }

    #[Test]
    public function rejeita_adicao_de_perfil_duplicado_para_o_mesmo_usuario(): void
    {
        $service = app(SolicitacaoCadastroService::class);
        $operador = $this->usuarioPorSub('teste-federal-001');
        $solicitacao = $this->solicitacaoPorCpfEStatus('98765432100', StatusSolicitacao::APROVADO);

        $this->expectException(ValidationException::class);

        try {
            $service->adicionarPerfil($operador, $solicitacao->id, [
                'perfil_id' => $this->perfilPorNome('Gestor Municipal')->id,
                'vigencia_inicio' => now()->toDateString(),
            ]);
        } catch (ValidationException $exception) {
            $this->assertSame(
                'Este perfil já está vinculado ao usuário.',
                $exception->errors()['perfil_id'][0] ?? null,
            );

            throw $exception;
        }
    }

    #[Test]
    public function adiciona_perfil_com_sucesso_em_solicitacao_aprovada(): void
    {
        $service = app(SolicitacaoCadastroService::class);
        $operador = $this->usuarioPorSub('teste-federal-001');
        $solicitacao = $this->solicitacaoPorCpfEStatus('98765432100', StatusSolicitacao::APROVADO);
        $perfil = $this->perfilPorNome('Administrador Municipal');

        $resultado = $service->adicionarPerfil($operador, $solicitacao->id, [
            'perfil_id' => $perfil->id,
            'vigencia_inicio' => now()->toDateString(),
            'vigencia_fim' => now()->addDays(30)->toDateString(),
        ]);

        $this->assertSame('Perfil vinculado adicionado com sucesso.', $resultado['message']);
        $this->assertDatabaseHas('perfil_usuario', [
            'usuario_id' => $solicitacao->user_id,
            'perfil_id' => $perfil->id,
            'ativo' => true,
        ]);

        $this->assertDatabaseMissing('perfil_usuario', [
            'usuario_id' => $solicitacao->user_id,
            'perfil_id' => $this->perfilPorNome('Gestor Municipal')->id,
            'ativo' => true,
        ]);
    }

    #[Test]
    public function rejeita_ativacao_e_desativacao_quando_o_vinculo_nao_existe(): void
    {
        $service = app(SolicitacaoCadastroService::class);
        $operador = $this->usuarioPorSub('teste-federal-001');
        $solicitacao = $this->solicitacaoPorCpfEStatus('98765432100', StatusSolicitacao::APROVADO);

        foreach (['ativarPerfil', 'desativarPerfil'] as $metodo) {
            try {
                $service->{$metodo}($operador, $solicitacao->id, 999999);
                self::fail('Era esperada ApiException para vínculo inexistente.');
            } catch (ApiException $exception) {
                $this->assertSame('Vínculo de perfil não encontrado.', $exception->getMessage());
            }
        }
    }

    #[Test]
    public function rejeita_operacoes_de_perfil_quando_a_solicitacao_nao_existe(): void
    {
        $service = app(SolicitacaoCadastroService::class);
        $operador = $this->usuarioPorSub('teste-federal-001');

        foreach (['ativarPerfil', 'desativarPerfil'] as $metodo) {
            try {
                $service->{$metodo}($operador, 999999, 1);
                self::fail('Era esperada ApiException para solicitação inexistente.');
            } catch (ApiException $exception) {
                $this->assertSame('Solicitação não encontrada.', $exception->getMessage());
            }
        }
    }

    #[Test]
    public function rejeita_adicao_de_perfil_em_solicitacao_nao_aprovada(): void
    {
        $service = app(SolicitacaoCadastroService::class);
        $operador = $this->usuarioPorSub('teste-federal-001');
        $solicitacao = $this->solicitacaoPorCpfEStatus('55566677788', StatusSolicitacao::EM_ANALISE);

        $this->expectException(ValidationException::class);

        try {
            $service->adicionarPerfil($operador, $solicitacao->id, [
                'perfil_id' => $this->perfilPorNome('Administrador Municipal')->id,
                'vigencia_inicio' => now()->toDateString(),
            ]);
        } catch (ValidationException $exception) {
            $this->assertSame(
                'Apenas solicitações aprovadas permitem adicionar novos perfis vinculados.',
                $exception->errors()['status'][0] ?? null,
            );

            throw $exception;
        }
    }

    #[Test]
    public function rejeita_adicao_de_perfil_quando_a_solicitacao_nao_existe(): void
    {
        $service = app(SolicitacaoCadastroService::class);
        $operador = $this->usuarioPorSub('teste-federal-001');

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Solicitação não encontrada.');

        $service->adicionarPerfil($operador, 999999, [
            'perfil_id' => $this->perfilPorNome('Administrador Municipal')->id,
            'vigencia_inicio' => now()->toDateString(),
        ]);
    }

    private function payloadSolicitacaoPublica(string $cpf, string $uf = 'DF', string $municipio = 'Brasília'): array
    {
        return [
            'nome' => 'Solicitante Publico',
            'CPF' => $cpf,
            'emailInstitucional' => 'solicitante.publico@teste.gov.br',
            'telefoneInstitucional' => '61999887766',
            'telefonePessoal' => '61988776655',
            'esferaAtuacao' => 'federal',
            'uf' => $uf,
            'municipio' => $municipio,
            'orgao' => 'Ministerio de Testes',
            'cargo' => 'Analista',
        ];
    }

    private function payloadSolicitacaoInterna(
        string $cpf,
        string $esfera,
        string $uf,
        string $municipio,
        int $perfilId,
    ): array {
        return [
            'nome' => 'Solicitante Interno',
            'CPF' => $cpf,
            'emailInstitucional' => 'interno@teste.gov.br',
            'telefoneInstitucional' => '61999887766',
            'telefonePessoal' => '61988776655',
            'esferaAtuacao' => $esfera,
            'uf' => $uf,
            'municipio' => $municipio,
            'orgao' => 'Orgão Interno',
            'cargo' => 'Analista',
            'perfilId' => $perfilId,
            'vigenciaInicio' => now()->toDateString(),
        ];
    }
}
