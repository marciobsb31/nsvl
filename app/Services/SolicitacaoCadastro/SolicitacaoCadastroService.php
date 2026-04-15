<?php

namespace App\Services\SolicitacaoCadastro;

use App\Enums\StatusSolicitacaoEnum;
use App\Enums\TipoAuditoria;
use App\Exceptions\ApiException;
use App\Helpers\CpfHelper;
use App\Mail\SolicitacaoCadastroAvaliada;
use App\Mail\SolicitacaoCadastroEnviada;
use App\Models\AuditLog;
use App\Models\PerfilUsuario;
use App\Models\SolicitacaoCadastro;
use App\Models\StatusSolicitacao;
use App\Models\Usuario;
use App\Models\UsuarioAbrangencia;
use App\Models\UsuarioContexto;
use App\Services\Audit\AuditLogService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class SolicitacaoCadastroService
{
    public function __construct(private readonly AuditLogService $audit) {}

    public function detalhar(int $id): array
    {
        $solicitacao = SolicitacaoCadastro::with([
            'usuario', 'esfera', 'ufRelacao', 'municipioRelacao', 'statusSolicitacao',
        ])->find($id);

        if (! $solicitacao) {
            throw ApiException::notFound('Solicitação não encontrada.');
        }

        $this->audit->log('gerenciar_cadastros.detalhamento', auth()->user()->id, [
            'solicitacao_id' => $solicitacao->id,
        ], TipoAuditoria::VIEW->name, 'solicitacoes_cadastro', $solicitacao->id);

        try {
            $perfisVinculados = $this->obterPerfisVinculados($solicitacao);
        } catch (\Throwable $e) {
            $perfisVinculados = [];
            report($e);
        }

        $statusEmAnalise = StatusSolicitacao::idPorNome(StatusSolicitacao::EM_ANALISE);

        return [
            'id'                         => $solicitacao->id,
            'nome'                       => $solicitacao->usuario?->nome ?? '',
            'cpf'                        => CpfHelper::mascarar($solicitacao->usuario?->cpf ?? ''),
            'cpf_digitos'                => $solicitacao->usuario?->cpf ?? '',
            'status'                     => $solicitacao->statusSolicitacao?->nome ?? '',
            'created_at'                 => $solicitacao->created_at?->toIso8601String(),
            'updated_at'                 => $solicitacao->updated_at?->toIso8601String(),
            'email_institucional'        => $solicitacao->email_institucional,
            'telefone_institucional'     => $solicitacao->telefone_institucional,
            'telefone_pessoal'           => $solicitacao->telefone_pessoal,
            'esfera_atuacao'             => $solicitacao->esfera?->nome ?? '',
            'uf'                         => $solicitacao->ufRelacao?->sigla ?? '',
            'municipio'                  => $solicitacao->municipioRelacao?->nome ?? '',
            'orgao'                      => $solicitacao->orgao,
            'cargo'                      => $solicitacao->cargo,
            'perfil_id_solicitado'       => $solicitacao->perfil_id,
            'vigencia_inicio_solicitada' => $solicitacao->vigencia_inicio?->format('Y-m-d'),
            'vigencia_fim_solicitada'    => $solicitacao->vigencia_fim?->format('Y-m-d'),
            'perfis_vinculados'          => $perfisVinculados,
            'pode_avaliar'               => $solicitacao->status_id === $statusEmAnalise && $user->isGestor(),
            'historico_reprovacoes'      => $this->montarHistoricoReprovacoes($solicitacao),
        ];
    }

    /**
     * Eventos de reprovação (auditoria + fallback para registros antigos sem contexto).
     *
     * @return array<int, array{data: string|null, motivo: string, avaliador: string|null}>
     */
    private function montarHistoricoReprovacoes(SolicitacaoCadastro $solicitacao): array
    {
        if (($solicitacao->statusSolicitacao?->nome ?? '') !== StatusSolicitacao::REPROVADO) {
            return [];
        }

        $logs = AuditLog::query()
            ->with('user')
            ->where('tabela_afetada', 'solicitacoes_cadastro')
            ->where('registro_id', $solicitacao->id)
            ->where('acao', 'gerenciar_cadastros.avaliacao')
            ->orderBy('created_at', 'asc')
            ->get();

        $itens = [];
        foreach ($logs as $log) {
            $ctx = $log->contexto ?? [];
            if (($ctx['status'] ?? '') !== 'reprovado') {
                continue;
            }
            $motivo = $ctx['motivo_reprovacao'] ?? null;
            if (! is_string($motivo) || trim($motivo) === '') {
                continue;
            }
            $itens[] = [
                'data'      => $log->created_at?->toIso8601String(),
                'motivo'    => $motivo,
                'avaliador' => $log->user?->nome,
            ];
        }

        if ($itens === [] && $solicitacao->justificativa_reprovacao) {
            $itens[] = [
                'data'      => $solicitacao->updated_at?->toIso8601String(),
                'motivo'    => $solicitacao->justificativa_reprovacao,
                'avaliador' => null,
            ];
        }

        return $itens;
    }

    public function criar(array $dados): array
    {

        $usuario = Usuario::where('cpf', $dados['cpf'])->first();

        $cpf = strlen($cpfDigits) === 11 ? $cpfDigits : ($user?->cpf ?? null);

        // Auto-cadastro: usuário autenticado submetendo solicitação para si mesmo (fluxo GOV.BR).
        // Cadastro interno via painel (Gestor criando para outro usuário) requer perfil de Gestor.
        $ehAutoCadastro = $user !== null && $user->cpf === $cpf;

        if ($user && !$ehAutoCadastro && !$user->isGestor()) {
            throw ApiException::forbidden('Apenas usuários com perfil de Gestor podem criar cadastros. Visitantes e Administradores não têm acesso.');
        }

        if (!$cpf) {
            throw ApiException::unprocessable('CPF é obrigatório.');
        }

        $statusEmAnalise = StatusSolicitacao::idPorNome(StatusSolicitacao::EM_ANALISE);

        // Buscar ou criar o usuario
        $usuario = Usuario::where('cpf', $cpf)->first();
        if (!$usuario) {
            $govbrSub = $this->govbrSubProvisorioParaCpf($cpf);
            try {
                $usuario = Usuario::create([
                    'cpf'       => $dados['cpf'],
                    'nome'      => $dados['nome'],
                    'email'     => $dados['emailInstitucional'],
                    'govbr_sub' => $govbrSub,
                    'telefone'  => $dados['telefoneInstitucional'] ?? null,
                    'ativo'     => true,
                ]);
            } catch (QueryException $e) {
                $msg = $e->getMessage();
                if (str_contains($msg, '23505') || str_contains($msg, 'UNIQUE constraint')) {
                    throw ApiException::unprocessable(
                        'Não foi possível concluir o cadastro: identificador ou CPF já vinculado a outro registro. Verifique os dados ou contate o suporte.'
                    );
                }
                throw $e;
            }
        } else {
            $usuario->update([
                'nome'  => $dados['nome'],
                'email' => $dados['email_institucional'],
            ]);
        }

        $existente = SolicitacaoCadastro::where('usuario_id', $usuario->id)
            ->where('status_id', $statusEmAnalise)
            ->exists();

        if ($existente) {
            throw ApiException::unprocessable('Já existe uma solicitação em análise para este CPF. Aguarde a avaliação da equipe gestora antes de enviar uma nova solicitação.');
        }

        $perfisDisponiveis = $this->obterPerfisDisponiveisParaUsuario($usuario->id);
        if ($perfisDisponiveis === []) {
            throw ApiException::unprocessable('Este CPF já atingiu o máximo de perfis disponíveis no sistema.');
        }

        $esferaNome = strtolower((string) ($dados['esferaAtuacao'] ?? ''));
        $ufSigla    = strtoupper((string) ($dados['uf'] ?? ''));
        $munNome    = trim((string) ($dados['municipio'] ?? ''));

        $esferaId   = Esfera::whereRaw('LOWER(nome) = ?', [$esferaNome])->value('id');
        $ufId       = Uf::where('sigla', $ufSigla)->value('id');
        $municipioId = Municipio::where('nome', $munNome)->where('uf_id', $ufId)->value('id');

        $this->validarReferenciasGeograficas($esferaNome, $esferaId, $ufId, $municipioId, $munNome, $ufSigla);

        $perfilIdSolicitado = isset($dados['perfilId']) ? (int) $dados['perfilId'] : null;
        if ($perfilIdSolicitado === 0) {
            $perfilIdSolicitado = null;
        }

        if ($perfilIdSolicitado) {
            $idsDisponiveis = array_map(
                static fn (array $opcao): int => (int) ($opcao['value'] ?? 0),
                $perfisDisponiveis
            );
            if (!in_array($perfilIdSolicitado, $idsDisponiveis, true)) {
                throw ApiException::unprocessable('O perfil selecionado já está vinculado ao usuário ou não está disponível para solicitação.');
            }
        }

        // Restrições de operador (esfera/UF/município e coerência de perfil) aplicam-se apenas
        // ao cadastro interno via painel. No auto-cadastro (fluxo GOV.BR) o perfil será
        // atribuído pelo gestor na avaliação da solicitação.
        if (!$ehAutoCadastro) {
            $this->assertOperadorPodeRegistrarSolicitacao(
                $user,
                $esferaNome,
                $ufSigla,
                $municipioId,
                $perfilIdSolicitado
            );
        }

        $this->verificarDuplicidade($usuario->id, $perfilIdSolicitado);

        $vigenciaInicioSol = $this->normalizarDataSolicitacaoOpcional($dados['vigenciaInicio'] ?? null);
        $vigenciaFimSol    = $this->normalizarDataSolicitacaoOpcional($dados['vigenciaFim'] ?? null);

        $solicitacao = SolicitacaoCadastro::create([
            'usuario_id'                 => $usuario->id,
            'email_institucional'        => $dados['emailInstitucional'],
            'telefone_institucional'     => $dados['telefoneInstitucional'] ?? null,
            'telefone_pessoal'           => $dados['telefonePessoal'] ?? null,
            'esfera_id'                  => $esferaId,
            'uf_id'                      => $ufId,
            'municipio_id'               => $municipioId,
            'orgao'                      => $dados['orgao'],
            'cargo'                      => $dados['cargo'] ?? null,
            'perfil_id'                  => $perfilIdSolicitado,
            'vigencia_inicio'            => $vigenciaInicioSol,
            'vigencia_fim'               => $vigenciaFimSol,
            'status_id'                  => $statusEmAnalise,
            'aceite_termo_at'            => Carbon::now(),
        ]);

        $this->audit->log(
            $ehAutoCadastro ? 'solicitacao_cadastro.criada' : ($user ? 'gerenciar_cadastros.solicitacao_interna_criada' : 'solicitacao_cadastro.criada'),
            $user?->id,
            [
                'solicitacao_id'       => $solicitacao->id,
                'perfil_id_solicitado' => $solicitacao->perfil_id,
                'esfera_id'            => $solicitacao->esfera_id,
                'uf_id'                => $solicitacao->uf_id,
                'municipio_id'         => $solicitacao->municipio_id,
                'origem'               => $ehAutoCadastro ? 'govbr' : ($user ? 'painel_interno' : 'formulario_publico'),
            ],
            TipoAuditoria::INSERT->name,
            'solicitacoes_cadastro',
            $solicitacao->id
        );

        $solicitacao->load(['usuario', 'esfera', 'ufRelacao', 'municipioRelacao']);

        try {
            $destinatario = $dados['email_institucional'];
            Mail::to($destinatario)->send(new SolicitacaoCadastroEnviada($solicitacao));
        } catch (\Throwable $e) {
            logger()->error('Falha ao enviar e-mail de confirmação da solicitação.', [
                'solicitacao_id'   => $solicitacao->id,
                'destinatario'     => $destinatario ?? null,
                'mailer'           => config('mail.default'),
                'host'             => config('mail.mailers.smtp.host'),
                'port'             => config('mail.mailers.smtp.port'),
                'scheme'           => config('mail.mailers.smtp.scheme'),
                'from'             => config('mail.from.address'),
                'auth_configurada' => ! empty(config('mail.mailers.smtp.username')),
                'erro'             => $e->getMessage(),
            ]);
            report($e);
        }

        return [
            'message'        => 'Solicitação registrada com sucesso! Seu pedido está com o status "Em Análise" e será avaliado pela equipe gestora.',
            'solicitacao_id' => $solicitacao->id,
        ];
    }

    // ------------------------------------------------------------------
    // Avaliar (aprovar / reprovar)
    // ------------------------------------------------------------------

    public function avaliar(Usuario $user, int $id, array $dados): array
    {
        // Verificar se usuário tem permissão para avaliar
        if ($user->isVisitante()) {
            throw ApiException::forbidden('Usuários com perfil visitante não podem avaliar solicitações.');
        }

        $solicitacao = SolicitacaoCadastro::with(['usuario', 'esfera', 'ufRelacao', 'municipioRelacao'])->find($id);
        if (! $solicitacao) {
            throw ApiException::notFound('Solicitação não encontrada.');
        }

        $statusEmAnalise = StatusSolicitacao::idPorNome(StatusSolicitacao::EM_ANALISE);
        $statusAprovado = StatusSolicitacao::idPorNome(StatusSolicitacao::APROVADO);
        $statusReprovado = StatusSolicitacao::idPorNome(StatusSolicitacao::REPROVADO);

        if ($solicitacao->status_id !== $statusEmAnalise) {
            throw ValidationException::withMessages([
                'status' => ['Apenas solicitações em análise podem ser aprovadas ou reprovadas.'],
            ]);
        }

        $statusNome = $dados['status'];
        $novoStatusId = $statusNome === 'aprovado' ? $statusAprovado : $statusReprovado;

        if ($novoStatusId === $statusAprovado) {
            $perfilIdAprovado = (int) ($dados['perfil_id'] ?? 0);
            $this->assertPerfilPermitidoParaOperador($user, $perfilIdAprovado);
        }

        DB::transaction(function () use ($solicitacao, $novoStatusId, $statusAprovado, $dados, $user): void {
            $updateData = ['status_id' => $novoStatusId];
            if ($novoStatusId !== $statusAprovado) {
                $updateData['justificativa_reprovacao'] = $dados['justificativa'] ?? null;
            }
            $solicitacao->update($updateData);

            if ($novoStatusId === $statusAprovado) {
                // Criar ou obter usuario_abrangencia da solicitação
                $usuarioAbrangencia = UsuarioAbrangencia::updateOrCreate(
                    [
                        'usuario_id'     => $solicitacao->usuario_id,
                        'esfera_id'      => $solicitacao->esfera_id,
                        'uf_id'          => $solicitacao->uf_id,
                        'municipio_id'   => $solicitacao->municipio_id,
                    ],
                    [
                        'nome'                         => $this->gerarNomeAbrangencia($solicitacao),
                        'origem_tipo'                  => 'solicitacao',
                        'solicitacao_cadastro_origem_id' => $solicitacao->id,
                        'criado_por_usuario_id'        => $user->id,
                        'ativo'                        => true,
                    ]
                );

                // Desativar outras abrangências do usuário
                UsuarioAbrangencia::where('usuario_id', $solicitacao->usuario_id)
                    ->where('id', '!=', $usuarioAbrangencia->id)
                    ->update(['ativo' => false]);

                $perfilUsuario = PerfilUsuario::updateOrCreate(
                    [
                        'usuario_id' => $solicitacao->usuario_id,
                        'perfil_id'  => (int) $dados['perfil_id'],
                    ],
                    [
                        'usuario_abrangencia_id'        => $usuarioAbrangencia->id,
                        'data_inicio_vigencia'          => $dados['vigencia_inicio'] ?? null,
                        'data_fim_vigencia'             => $dados['vigencia_fim'] ?? null,
                        'origem_tipo'                   => 'solicitacao',
                        'solicitacao_cadastro_origem_id' => $solicitacao->id,
                        'atribuido_por_usuario_id'       => $user->id,
                        'ativo'                         => true,
                    ]
                );

                // Atualizar usuario_contexto com o novo perfil e abrangência
                UsuarioContexto::updateOrCreate(
                    ['usuario_id' => $solicitacao->usuario_id],
                    [
                        'perfil_usuario_id'      => $perfilUsuario->id,
                        'usuario_abrangencia_id' => $usuarioAbrangencia->id,
                    ]
                );
            }
        });

        $contextoAudit = [
            'solicitacao_id' => $solicitacao->id,
            'status'         => $statusNome,
            'perfil_id'      => $dados['perfil_id'] ?? null,
        ];
        if ($statusNome === 'reprovado') {
            $contextoAudit['motivo_reprovacao'] = $dados['justificativa'] ?? null;
        }
        $this->audit->log(
            'gerenciar_cadastros.avaliacao',
            $user->id,
            $contextoAudit,
            TipoAuditoria::UPDATE->name,
            'solicitacoes_cadastro',
            $solicitacao->id
        );

        try {
            $destinatario = $solicitacao->email_institucional ?: $solicitacao->usuario?->email;
            if (! empty($destinatario)) {
                Mail::to($destinatario)->send(new SolicitacaoCadastroAvaliada(
                    $solicitacao,
                    $statusNome,
                    $dados['justificativa'] ?? null
                ));
            }
        } catch (\Throwable $e) {
            logger()->error('Falha ao enviar e-mail de avaliacao da solicitacao.', [
                'solicitacao_id'   => $solicitacao->id,
                'status'           => $statusNome,
                'destinatario'     => $destinatario ?? null,
                'mailer'           => config('mail.default'),
                'host'             => config('mail.mailers.smtp.host'),
                'port'             => config('mail.mailers.smtp.port'),
                'scheme'           => config('mail.mailers.smtp.scheme'),
                'from'             => config('mail.from.address'),
                'auth_configurada' => ! empty(config('mail.mailers.smtp.username')),
                'erro'             => $e->getMessage(),
            ]);
            report($e);
        }

        return [
            'message' => $statusNome === 'aprovado'
                ? 'Solicitação aprovada.'
                : 'Solicitação reprovada.',
            'data' => [
                'id'     => $solicitacao->id,
                'status' => $statusNome,
            ],
        ];
    }

    // ------------------------------------------------------------------
    // Verificar CPF
    // ------------------------------------------------------------------

    public function verificarCpf(string $cpfRaw): array
    {
        $cpf = preg_replace('/\D/', '', $cpfRaw);

        if (strlen($cpf) !== 11) {
            return ['disponivel' => false, 'mensagem' => 'Informe um CPF com 11 dígitos.'];
        }

        if (! CpfHelper::validar($cpf)) {
            return ['disponivel' => false, 'mensagem' => 'CPF inválido. Verifique os dígitos informados.'];
        }

        $statusEmAnalise = StatusSolicitacao::idPorNome(StatusSolicitacao::EM_ANALISE);

        $emAnalise = SolicitacaoCadastro::whereHas('usuario', fn ($q) => $q->where('cpf', $cpf))
            ->where('status_id', $statusEmAnalise)
            ->exists();

        if ($emAnalise) {
            return [
                'disponivel' => false,
                'mensagem' => 'Já existe uma solicitação em análise para este CPF.',
                'perfis_disponiveis' => [],
            ];
        }

        $usuario = Usuario::where('cpf', $cpf)->first();
        $perfisDisponiveis = $this->obterPerfisDisponiveisParaUsuario($usuario?->id);

        if ($usuario && $perfisDisponiveis === []) {
            return [
                'disponivel' => false,
                'mensagem' => 'Este CPF já atingiu o máximo de perfis disponíveis no sistema.',
                'perfis_disponiveis' => [],
            ];
        }

        return [
            'disponivel' => true,
            'mensagem' => 'CPF disponível para cadastro.',
            'perfis_disponiveis' => $perfisDisponiveis,
        ];
    }

    // ------------------------------------------------------------------
    // Ativar perfil vinculado
    // ------------------------------------------------------------------

    public function ativarPerfil(Usuario $user, int $solicitacaoId, int $perfilUsuarioId): array
    {
        $solicitacao = $this->buscarSolicitacao($solicitacaoId);
        $usuarioSolicitante = $solicitacao->usuario;
        if (! $usuarioSolicitante) {
            throw ApiException::notFound('Usuário da solicitação não encontrado.');
        }

        $this->assertUsuarioPodeGerenciarPerfisVinculados($user, $usuarioSolicitante);

        $vinculo = PerfilUsuario::where('id', $perfilUsuarioId)
            ->where('usuario_id', $usuarioSolicitante->id)
            ->first();

        if (! $vinculo) {
            throw ApiException::notFound('Vínculo de perfil não encontrado.');
        }

        $hoje = now()->toDateString();
        $inicioAtual = $vinculo->data_inicio_vigencia?->toDateString();
        // Garantir vigência corrente ao ativar (evita perfil "inativo" na grade por início futuro).
        $dataInicio = (!$inicioAtual || $inicioAtual > $hoje) ? $hoje : $inicioAtual;

        $vinculo->update([
            'data_inicio_vigencia' => $dataInicio,
            'data_fim_vigencia'    => null,
            'ativo'                => true,
        ]);

        $vinculo->refresh();

        // Sincronizar usuario_contexto com o perfil agora ativo
        UsuarioContexto::updateOrCreate(
            ['usuario_id' => $usuarioSolicitante->id],
            [
                'perfil_usuario_id'      => $vinculo->id,
                'usuario_abrangencia_id' => $vinculo->usuario_abrangencia_id,
            ]
        );

        $this->audit->log('gerenciar_cadastros.perfil_ativado', $user->id, [
            'solicitacao_id'         => $solicitacao->id,
            'perfil_usuario_id'      => $vinculo->id,
            'usuario_id'             => $usuarioSolicitante->id,
            'usuario_abrangencia_id' => $vinculo->usuario_abrangencia_id,
        ], AuditLog::TIPO_UPDATE, 'perfil_usuario', $vinculo->id);

        return ['message' => 'Perfil vinculado ativado com sucesso.', 'data' => ['id' => $perfilUsuarioId]];
    }

    // ------------------------------------------------------------------
    // Desativar perfil vinculado
    // ------------------------------------------------------------------

    public function desativarPerfil(Usuario $user, int $solicitacaoId, int $perfilUsuarioId): array
    {
        $solicitacao = $this->buscarSolicitacao($solicitacaoId);
        $usuarioSolicitante = $solicitacao->usuario;
        if (! $usuarioSolicitante) {
            throw ApiException::notFound('Usuário da solicitação não encontrado.');
        }

        $this->assertUsuarioPodeGerenciarPerfisVinculados($user, $usuarioSolicitante);

        $vinculo = PerfilUsuario::where('id', $perfilUsuarioId)
            ->where('usuario_id', $usuarioSolicitante->id)
            ->first();

        if (! $vinculo) {
            throw ApiException::notFound('Vínculo de perfil não encontrado.');
        }

        $dataFim = now()->toDateString();
        $dataInicio = $vinculo->data_inicio_vigencia?->toDateString();

        if ($dataInicio && $dataInicio > $dataFim) {
            $dataFim = $dataInicio;
        }

        $vinculo->update([
            'data_fim_vigencia' => $dataFim,
            'ativo'             => false,
        ]);

        // Limpar usuario_contexto se o perfil desativado era o contexto ativo
        UsuarioContexto::where('usuario_id', $usuarioSolicitante->id)
            ->where('perfil_usuario_id', $vinculo->id)
            ->update([
                'perfil_usuario_id'      => null,
                'usuario_abrangencia_id' => null,
            ]);

        $this->audit->log('gerenciar_cadastros.perfil_desativado', $user->id, [
            'solicitacao_id'    => $solicitacao->id,
            'perfil_usuario_id' => $vinculo->id,
            'usuario_id'        => $usuarioSolicitante->id,
        ], TipoAuditoria::UPDATE->name, 'perfil_usuario', $vinculo->id);

        return ['message' => 'Perfil vinculado desativado com sucesso.', 'data' => ['id' => $vinculo->id]];
    }

    // ------------------------------------------------------------------
    // Adicionar perfil vinculado
    // ------------------------------------------------------------------

    public function adicionarPerfil(int $solicitacaoId, array $dados): array
    {
        $solicitacao = $this->buscarSolicitacao($solicitacaoId);
        $statusAprovado = StatusSolicitacao::idPorNome(StatusSolicitacao::APROVADO);

        if ($solicitacao->status_id !== $statusAprovado) {
            throw ValidationException::withMessages([
                'status' => ['Apenas solicitações aprovadas permitem adicionar novos perfis vinculados.'],
            ]);
        }

        $usuarioSolicitante = $solicitacao->usuario;
        if (! $usuarioSolicitante) {
            throw ApiException::notFound('Usuário da solicitação não encontrado.');
        }

        $this->assertUsuarioPodeGerenciarPerfisVinculados($user, $usuarioSolicitante);

        $vinculoExistente = PerfilUsuario::where('usuario_id', $usuarioSolicitante->id)
            ->where('perfil_id', (int) $dados['perfil_id'])
            ->first();

        if ($vinculoExistente) {
            throw ValidationException::withMessages([
                'perfil_id' => ['Este perfil já está vinculado ao usuário.'],
            ]);
        }

        $vinculo = PerfilUsuario::create([
            'usuario_id'                    => $usuarioSolicitante->id,
            'perfil_id'                     => (int) $dados['perfil_id'],
            'data_inicio_vigencia'          => $dados['vigencia_inicio'] ?? null,
            'data_fim_vigencia'             => $dados['vigencia_fim'] ?? null,
            'origem_tipo'                   => 'painel',
            'solicitacao_cadastro_origem_id' => $solicitacao->id,
            'atribuido_por_usuario_id'      => $user->id,
            'ativo'                         => true,
        ]);

        $this->audit->log('gerenciar_cadastros.perfil_adicionado', auth()->user()->id, [
            'solicitacao_id'    => $solicitacao->id,
            'perfil_usuario_id' => $vinculo->id,
            'perfil_id'         => $vinculo->perfil_id,
            'usuario_id'        => $usuarioSolicitante->id,
        ], TipoAuditoria::INSERT->name, 'perfil_usuario', $vinculo->id);

        return ['message' => 'Perfil vinculado adicionado com sucesso.', 'data' => ['id' => $vinculo->id]];
    }

    // ==================================================================
    // Metodos privados auxiliares
    // ==================================================================

    private function buscarSolicitacao(int $id): SolicitacaoCadastro
    {
        $solicitacao = SolicitacaoCadastro::with('usuario')->find($id);
        if (! $solicitacao) {
            throw ApiException::notFound('Solicitação não encontrada.');
        }

        return $solicitacao;
    }

            private function assertUsuarioPodeGerenciarPerfisVinculados(Usuario $user, Usuario $usuarioSolicitante): void
            {
                if (!$user->isGestor()) {
                    throw ApiException::forbidden('Apenas usuários com perfil de Gestor podem ativar, desativar ou adicionar perfis vinculados.');
                }

                if ($user->id === $usuarioSolicitante->id) {
                    throw ApiException::forbidden('Você não pode ativar, desativar ou adicionar perfis ao próprio usuário. Esta ação deve ser realizada por outro gestor.');
                }
            }

    private function obterPerfisVinculados(SolicitacaoCadastro $solicitacao): array
    {
        $usuario = $solicitacao->usuario;
        if (! $usuario) {
            return [];
        }

        $hoje = now()->toDateString();

        // Usar o model PerfilUsuario (não o pivot via belongsToMany) para o `id` ser sempre o da tabela perfil_usuario.
        $vinculos = PerfilUsuario::query()
            ->where('usuario_id', $usuario->id)
            ->with([
                'perfil',
                'usuarioAbrangencia.esfera',
                'usuarioAbrangencia.uf',
                'usuarioAbrangencia.municipio',
            ])
            ->get();

        $perfis = $vinculos->map(function (PerfilUsuario $vinculo) use ($hoje, $solicitacao): array {
            $inicio = $this->normalizarDataPivot($vinculo->data_inicio_vigencia);
            $fim    = $this->normalizarDataPivot($vinculo->data_fim_vigencia);
            $ativo  = (bool) $vinculo->ativo;
            $vigente = $ativo
                && (!$inicio || $inicio <= $hoje)
                && (!$fim || $fim >= $hoje);

            $abr = $vinculo->usuarioAbrangencia;

            return [
                'id'              => $vinculo->id,
                'perfil'          => $vinculo->perfil?->nome ?? '—',
                'vigencia_inicio' => $inicio ?? '—',
                'vigencia_fim'    => $fim ?? '—',
                'vigente'         => $vigente,
                'ativo'           => $ativo,
                'esfera'          => $abr?->esfera?->nome ?? $solicitacao->esfera?->nome ?? '—',
                'uf'              => $abr?->uf?->sigla ?? $solicitacao->ufRelacao?->sigla ?? '—',
                'municipio'       => $abr?->municipio?->nome ?? $solicitacao->municipioRelacao?->nome ?? '—',
                'orgao'           => $solicitacao->orgao ?? '—',
                'cargo'           => $solicitacao->cargo ?? '—',
            ];
        })->all();

        usort($perfis, function (array $a, array $b): int {
            $ativoA = (int) (($a['ativo'] ?? false) ? 1 : 0);
            $ativoB = (int) (($b['ativo'] ?? false) ? 1 : 0);

            if ($ativoA !== $ativoB) {
                return $ativoB <=> $ativoA;
            }

            return strcmp((string) ($a['perfil'] ?? ''), (string) ($b['perfil'] ?? ''));
        });

        return $perfis;
    }

    private function govbrSubProvisorioParaCpf(string $cpfDigits): string
    {
        $base = 'pending-' . $cpfDigits;
        if (!Usuario::where('govbr_sub', $base)->exists()) {
            return $base;
        }

        return $base . '-' . Str::lower(Str::random(8));
    }

    /**
     * Cadastro interno (operador autenticado): replica as regras de hierarquia do frontend.
     */
    private function assertOperadorPodeRegistrarSolicitacao(
        ?Usuario $operador,
        string $esferaFormLower,
        string $ufFormSigla,
        ?int $municipioId,
        ?int $perfilIdSolicitado
    ): void {
        if (!$operador) {
            return;
        }

        $esferaOp = mb_strtolower(trim((string) $operador->esfera_atuacao));

        if ($esferaOp === 'federal' || $esferaOp === '') {
            return;
        }

        if ($esferaOp === 'estadual') {
            if ($esferaFormLower !== 'estadual') {
                throw ApiException::unprocessable(
                    'Operadores da esfera estadual só podem registrar solicitações na esfera estadual.'
                );
            }
            $ufLot = strtoupper((string) ($operador->uf_lotacao ?? ''));
            if ($ufLot === '' || $ufFormSigla !== $ufLot) {
                throw ApiException::unprocessable(
                    'A UF do solicitante deve ser a mesma da UF de lotação do seu usuário.'
                );
            }
            $this->assertPerfilCoerenteComRestricaoEsferaOperador($perfilIdSolicitado, 'estadual');

            return;
        }

        if ($esferaOp === 'municipal') {
            if ($esferaFormLower !== 'municipal') {
                throw ApiException::unprocessable(
                    'Operadores da esfera municipal só podem registrar solicitações na esfera municipal.'
                );
            }
            $ufLot = strtoupper((string) ($operador->uf_lotacao ?? ''));
            if ($ufLot === '' || $ufFormSigla !== $ufLot) {
                throw ApiException::unprocessable(
                    'A UF do solicitante deve ser a mesma da UF de lotação do seu usuário.'
                );
            }
            $munLot        = mb_strtolower(trim((string) ($operador->municipio_lotacao ?? '')));
            $munResolvido  = mb_strtolower(trim((string) (Municipio::find($municipioId)?->nome ?? '')));
            if ($munLot === '' || $munResolvido === '' || $munResolvido !== $munLot) {
                throw ApiException::unprocessable(
                    'O município do solicitante deve ser o mesmo do município de lotação do seu usuário.'
                );
            }
            $this->assertPerfilCoerenteComRestricaoEsferaOperador($perfilIdSolicitado, 'municipal');
        }
    }

    private function assertPerfilCoerenteComRestricaoEsferaOperador(?int $perfilId, string $tipo): void
    {
        if (!$perfilId) {
            throw ApiException::unprocessable('Selecione o perfil solicitado.');
        }

        $perfil = Perfil::find($perfilId);
        if (!$perfil) {
            throw ApiException::unprocessable('Perfil informado é inválido.');
        }

        $permitidos = $tipo === 'estadual'
            ? ['Gestor Estadual', 'Gestor Municipal']
            : ['Gestor Municipal'];

        if (!in_array($perfil->nome, $permitidos, true)) {
            throw ApiException::unprocessable(
                $tipo === 'estadual'
                    ? 'Seu nível de acesso só permite solicitar perfis Gestor Estadual ou Gestor Municipal.'
                    : 'Seu nível de acesso só permite solicitar perfis Gestor Municipal.'
            );
        }
    }

    private function assertPerfilPermitidoParaOperador(Usuario $user, int $perfilId): void
    {
        if ($perfilId <= 0) {
            throw ApiException::unprocessable('Selecione o perfil para aprovação.');
        }

        if (!Perfil::perfilPermitidoParaUsuario($user, $perfilId)) {
            throw ApiException::forbidden('O perfil selecionado não é permitido para o gestor logado.');
        }
    }

    private function normalizarDataSolicitacaoOpcional(mixed $valor): ?string
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        try {
            return Carbon::parse((string) $valor)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    private function validarReferenciasGeograficas(
        string $esferaNome,
        ?int $esferaId,
        ?int $ufId,
        ?int $municipioId,
        string $munNome,
        string $ufSigla
    ): void {
        if (!$esferaId) {
            throw ApiException::unprocessable('Esfera de atuação inválida ou não cadastrada.');
        }
        if (!$ufId) {
            throw ApiException::unprocessable('UF inválida ou não cadastrada no sistema.');
        }
        if ($munNome === '') {
            throw ApiException::unprocessable('Informe o município.');
        }
        if (!$municipioId) {
            throw ApiException::unprocessable(
                "Município \"{$munNome}\" não encontrado para a UF {$ufSigla}. Verifique a grafia ou selecione na lista oficial."
            );
        }
    }

    private function verificarDuplicidade(int $userId, ?int $perfilIdSolicitado): void
    {
        // Sem perfil solicitado, não há como validar duplicidade de perfil ativo.
        if (!$perfilIdSolicitado) {
            return;
        }

        // Verificar se o usuário já possui o mesmo perfil ATIVO (vigente)
        $query = PerfilUsuario::where('usuario_id', $userId)
            ->where('ativo', true)
            ->where('perfil_id', $perfilIdSolicitado)
            ->where('data_inicio_vigencia', '<=', now()->toDateString())
            ->where(function ($q) {
                $q->whereNull('data_fim_vigencia')
                  ->orWhere('data_fim_vigencia', '>=', now()->toDateString());
            });

        if ($query->exists()) {
            $nomePerfil = Perfil::find($perfilIdSolicitado)?->nome;
            $msg = $nomePerfil
                ? "Você já possui o perfil ativo: {$nomePerfil}."
                : 'Você já possui este perfil ativo.';

            throw ApiException::unprocessable($msg);
        }
    }

    private function normalizarDataPivot(mixed $valor): ?string
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        if ($valor instanceof \DateTimeInterface) {
            return $valor->format('Y-m-d');
        }

        return Carbon::parse((string) $valor)->format('Y-m-d');
    }

    /**
     * Gera nome descritivo para uma abrangência baseado na solicitação
     */
    private function gerarNomeAbrangencia(SolicitacaoCadastro $solicitacao): string
    {
        $esfera = $solicitacao->esfera?->nome ?? 'Federal';
        $uf = $solicitacao->ufRelacao?->sigla ?? '';
        $municipio = $solicitacao->municipioRelacao?->nome ?? '';

        if ($esfera === 'Federal') {
            return 'Federal';
        } elseif ($esfera === 'Estadual') {
            return "Estadual - {$uf}";
        } elseif ($esfera === 'Municipal') {
            return "Municipal - {$uf} / {$municipio}";
        }

        return $esfera;
    }

    /**
     * Lista de perfis ainda não vinculados ao usuário.
     * Se não houver usuário para o CPF, retorna todo o catálogo oficial ativo.
     *
     * @return array<int, array{value:int,label:string}>
     */
    private function obterPerfisDisponiveisParaUsuario(?int $usuarioId): array
    {
        $query = Perfil::query()
            ->whereIn('nome', Perfil::CATALOGO_OFICIAL)
            ->where('ativo', true);

        if ($usuarioId) {
            // Exclui apenas perfis que estão ATIVOS no momento.
            // Perfis inativados (reprovados, expirados, desvinculados) ficam disponíveis para nova solicitação.
            $idsAtivos = PerfilUsuario::query()
                ->where('usuario_id', $usuarioId)
                ->where('ativo', true)
                ->pluck('perfil_id')
                ->map(static fn ($id) => (int) $id)
                ->unique()
                ->values()
                ->all();

            if ($idsAtivos !== []) {
                $query->whereNotIn('id', $idsAtivos);
            }
        }

        return $query->get(['id', 'nome'])
            ->sortBy(static fn (Perfil $perfil): int => Perfil::indiceNoCatalogo($perfil->nome))
            ->values()
            ->map(static fn (Perfil $perfil): array => [
                'value' => (int) $perfil->id,
                'label' => $perfil->nome,
            ])
            ->all();
    }
}
