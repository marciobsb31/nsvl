<?php

namespace App\Services\SolicitacaoCadastro;

use App\Enums\EsferaEnum;
use App\Enums\StatusSolicitacaoEnum;
use App\Enums\TipoAuditoria;
use App\Exceptions\ApiException;
use App\Helpers\CpfHelper;
use App\Mail\SolicitacaoCadastroAvaliada;
use App\Mail\SolicitacaoCadastroEnviada;
use App\Models\AuditLog;
use App\Models\Perfil;
use App\Models\PerfilUsuario;
use App\Models\SolicitacaoCadastro;
use App\Models\StatusSolicitacao;
use App\Models\Usuario;
use App\Models\UsuarioAbrangencia;
use App\Services\Audit\AuditLogService;
use App\Support\MvpPerfilRules;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class SolicitacaoCadastroService
{
    public function __construct(private readonly AuditLogService $audit) {}

    public function registrarAcessoDetalhamento(SolicitacaoCadastro $solicitacao): void
    {
        $usuarioId = auth()->id();
        if (! $usuarioId) {
            return;
        }

        $this->audit->log('gerenciar_cadastros.detalhamento', $usuarioId, [
            'solicitacao_id' => $solicitacao->id,
        ], TipoAuditoria::VIEW->name, 'solicitacoes_cadastro', $solicitacao->id);
    }

    public function detalhar(int $id): array
    {
        $solicitacao = SolicitacaoCadastro::with([
            'usuario', 'esfera', 'ufRelacao', 'municipioRelacao', 'statusSolicitacao',
        ])->find($id);

        if (! $solicitacao) {
            throw ApiException::notFound('Solicitação não encontrada.');
        }

        $this->registrarAcessoDetalhamento($solicitacao);

        try {
            $perfisVinculados = $this->obterPerfisVinculados($solicitacao);
        } catch (\Throwable $e) {
            $perfisVinculados = [];
            report($e);
        }

        return [
            'id'                         => $solicitacao->id,
            'nome'                       => $solicitacao->usuario?->nome ?? '',
            'cpf'                        => CpfHelper::mascarar($solicitacao->usuario?->cpf ?? ''),
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
            'perfil_id'                  => $solicitacao->perfil_id,
            'vigencia_inicio_solicitada' => $solicitacao->vigencia_inicio_solicitada?->format('Y-m-d'),
            'vigencia_fim_solicitada'    => $solicitacao->vigencia_fim_solicitada?->format('Y-m-d'),
            'perfis_vinculados'          => $perfisVinculados,
            'pode_avaliar'               => $solicitacao->status_id === $statusEmAnalise
                && (bool) auth()->user()?->hasPermissao('solicitacoes_cadastro.analisar'),
            'historico_reprovacoes' => $this->montarHistoricoReprovacoes($solicitacao),
        ];
    }

    /**
     * Eventos de reprovação (auditoria + fallback para registros antigos sem contexto).
     *
     * @return array<int, array{data: string|null, motivo: string, avaliador: string|null}>
     */
    private function montarHistoricoReprovacoes(SolicitacaoCadastro $solicitacao): array
    {
        if (strtolower($solicitacao->statusSolicitacao?->nome ?? '') !== StatusSolicitacao::REPROVADO) {
            return [];
        }

        $logs = AuditLog::query()
            ->with('usuario')
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
                'avaliador' => $log->usuario?->nome,
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
        // Validar hierarquia se gestor autenticado
        $usuarioAutenticado = auth('sanctum')->user() ?? auth()->user();
        $cadastroInterno = (bool) $usuarioAutenticado;
        if ($usuarioAutenticado) {
            $this->validarHierarquiaCadastro($usuarioAutenticado, $dados);
        }

        $usuario = Usuario::where('cpf', $dados['cpf'])->first();

        if (! $usuario) {
            try {
                $usuario = Usuario::create([
                    'cpf'       => $dados['cpf'],
                    'nome'      => $dados['nome'],
                    'email'     => $dados['email_institucional'],
                    'govbr_sub' => $dados['cpf'],
                    'ativo'     => false,
                    'telefone'  => $dados['telefone_pessoal'] ?? null,
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

        $perfilIdSolicitado = isset($dados['perfil_id']) && $dados['perfil_id'] !== null
            ? (int) $dados['perfil_id']
            : (isset($dados['perfilId']) && $dados['perfilId'] !== null ? (int) $dados['perfilId'] : null);
        if ($perfilIdSolicitado === 0) {
            $perfilIdSolicitado = null;
        }

        $esferaId = isset($dados['esfera_id']) && $dados['esfera_id'] !== null
            ? (int) $dados['esfera_id']
            : null;
        $ufId = isset($dados['uf_id']) && $dados['uf_id'] !== null && $dados['uf_id'] !== ''
            ? (int) $dados['uf_id']
            : null;
        $municipioId = isset($dados['municipio_id']) && $dados['municipio_id'] !== null && $dados['municipio_id'] !== ''
            ? (int) $dados['municipio_id']
            : null;

        if ($usuario && $perfilIdSolicitado) {
            $hoje = now()->toDateString();
            $jaPossuiPerfilAtivoNaAbrangencia = PerfilUsuario::query()
                ->where('usuario_id', $usuario->id)
                ->where('perfil_id', $perfilIdSolicitado)
                ->where('ativo', true)
                ->where(function ($q) use ($hoje) {
                    $q->whereNull('data_inicio_vigencia')->orWhere('data_inicio_vigencia', '<=', $hoje);
                })
                ->where(function ($q) use ($hoje) {
                    $q->whereNull('data_fim_vigencia')->orWhere('data_fim_vigencia', '>=', $hoje);
                })
                ->whereHas('abrangencia', function ($q) use ($dados) {
                    if (isset($dados['esfera_id']) && $dados['esfera_id'] !== null) {
                        $q->where('esfera_id', (int) $dados['esfera_id']);
                    }
                    if (isset($dados['uf_id']) && $dados['uf_id'] !== null) {
                        $q->where('uf_id', (int) $dados['uf_id']);
                    }
                    if (isset($dados['municipio_id']) && $dados['municipio_id'] !== null) {
                        $q->where('municipio_id', (int) $dados['municipio_id']);
                    }
                })
                ->exists();

            if ($jaPossuiPerfilAtivoNaAbrangencia) {
                throw ApiException::unprocessable(
                    'Este CPF já possui o perfil selecionado ativo para a área de atuação informada. Selecione outro perfil.'
                );
            }
        }

        $statusInicial = $cadastroInterno
            ? StatusSolicitacaoEnum::APROVADO->value
            : StatusSolicitacaoEnum::EM_ANALISE->value;

        if ($cadastroInterno && ! $perfilIdSolicitado) {
            throw ApiException::unprocessable('No cadastro interno, o perfil é obrigatório para ativação imediata.');
        }

        $solicitacao = SolicitacaoCadastro::create([
            'usuario_id'             => $usuario->id,
            'email_institucional'    => $dados['email_institucional'],
            'telefone_institucional' => $dados['telefone_institucional'] ?? null,
            'telefone_pessoal'       => $dados['telefone_pessoal'] ?? null,
            'esfera_id'              => $dados['esfera_id'] ?? null,
            'uf_id'                  => $dados['uf_id'] ?? null,
            'municipio_id'           => $dados['municipio_id'] ?? null,
            'orgao'                  => $dados['orgao'],
            'cargo'                  => $dados['cargo'] ?? null,
            'perfil_id'              => $perfilIdSolicitado,
            'vigencia_inicio'        => $dados['vigencia_inicio'] ?? null,
            'vigencia_fim'           => $dados['vigencia_fim'] ?? null,
            'status_id'              => $statusInicial,
            'aceite_termo_at'        => Carbon::now(),
        ]);

        if ($cadastroInterno) {
            $nomeLocalidade = $solicitacao->municipioRelacao?->nome
                ?? $solicitacao->ufRelacao?->nome
                ?? 'Âmbito Nacional';

            $abrangencia = UsuarioAbrangencia::create([
                'usuario_id'                     => $solicitacao->usuario_id,
                'esfera_id'                      => $solicitacao->esfera_id,
                'uf_id'                          => $solicitacao->uf_id,
                'municipio_id'                   => $solicitacao->municipio_id,
                'nome'                           => "{$solicitacao->esfera?->nome} - {$nomeLocalidade}",
                'origem_tipo'                    => 'solicitacao_cadastro',
                'solicitacao_cadastro_origem_id' => $solicitacao->id,
                'ativo'                          => true,
                'criado_por_usuario_id'          => auth()->id(),
            ]);

            $perfilUsuario = PerfilUsuario::create([
                'usuario_id'                     => $solicitacao->usuario_id,
                'perfil_id'                      => (int) $perfilIdSolicitado,
                'usuario_abrangencia_id'         => $abrangencia->id,
                'data_inicio_vigencia'           => $dados['vigencia_inicio'] ?? now(),
                'data_fim_vigencia'              => $dados['vigencia_fim'] ?? null,
                'ativo'                          => true,
                'origem_tipo'                    => 'solicitacao',
                'atribuido_por_usuario_id'       => auth()->id(),
                'solicitacao_cadastro_origem_id' => $solicitacao->id,
            ]);

            $solicitacao->usuario->update(['ativo' => true]);

            // Preserva o contexto já selecionado do usuário quando houver um vínculo ativo válido.
            $contextoAtivoAtual = $solicitacao->usuario->contextoAtivo()->first();
            $perfilContextoAtivoValido = $contextoAtivoAtual
                ? PerfilUsuario::query()
                    ->where('id', $contextoAtivoAtual->perfil_usuario_id)
                    ->where('usuario_id', $solicitacao->usuario_id)
                    ->where('ativo', true)
                    ->exists()
                : false;

            if (! $perfilContextoAtivoValido) {
                $solicitacao->usuario->contextoAtivo()->updateOrCreate(
                    ['usuario_id' => $solicitacao->usuario_id],
                    [
                        'perfil_usuario_id'      => $perfilUsuario->id,
                        'usuario_abrangencia_id' => $abrangencia->id,
                    ]
                );
            }
        }

        $this->audit->log(
            $usuario ? 'gerenciar_cadastros.solicitacao_interna_criada' : 'solicitacao_cadastro.criada',
            $usuario?->id,
            [
                'solicitacao_id' => $solicitacao->id,
                'perfil_id'      => $solicitacao->perfil_id,
                'esfera_id'      => $solicitacao->esfera_id,
                'uf_id'          => $solicitacao->uf_id,
                'municipio_id'   => $solicitacao->municipio_id,
                'origem'         => $usuario ? 'painel_interno' : 'formulario_publico',
            ],
            TipoAuditoria::INSERT->name,
            'solicitacoes_cadastro',
            $solicitacao->id
        );

        $solicitacao->load(['usuario', 'esfera', 'ufRelacao', 'municipioRelacao']);

        try {
            $destinatario = $dados['email_institucional'];
            if ($cadastroInterno) {
                Mail::to($destinatario)->send(new SolicitacaoCadastroAvaliada($solicitacao, 'aprovado', null));
            } else {
                Mail::to($destinatario)->send(new SolicitacaoCadastroEnviada($solicitacao));
            }
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
            'message' => $cadastroInterno
                ? 'Cadastro interno realizado com sucesso. Usuário ativado diretamente.'
                : 'Solicitação registrada com sucesso! Seu pedido está com o status "Em Análise" e será avaliado pela equipe gestora.',
            'solicitacao_id' => $solicitacao->id,
        ];
    }

    // ------------------------------------------------------------------
    // Avaliar (aprovar / reprovar)
    // ------------------------------------------------------------------

    public function avaliar($solicitacao, array $dados): array
    {
        if ($solicitacao->status_id !== StatusSolicitacaoEnum::EM_ANALISE->value) {
            throw ValidationException::withMessages([
                'status' => ['Apenas solicitações em análise podem ser aprovadas ou reprovadas.'],
            ]);
        }

        $aprovado = (int) $dados['status_id'] === StatusSolicitacaoEnum::APROVADO->value;
        $statusNome = $aprovado ? 'aprovado' : 'reprovado';

        if ($aprovado && isset($dados['perfil_id'])) {
            $this->validarHierarquiaPerfil((int) $dados['perfil_id']);
        }

        DB::transaction(function () use ($solicitacao, $aprovado, $dados): void {
            $updateData = [
                'status_id'                => $aprovado ? StatusSolicitacaoEnum::APROVADO->value : StatusSolicitacaoEnum::REPROVADO->value,
                'justificativa_reprovacao' => ! $aprovado ? ($dados['justificativa'] ?? null) : null,
                'avaliado_por_id'          => auth()->id(),
                'data_avaliacao'           => now(),
            ];

            $solicitacao->update($updateData);

            if ($aprovado) {
                $nomeLocalidade = $solicitacao->municipioRelacao?->nome
                    ?? $solicitacao->ufRelacao?->nome
                    ?? 'Âmbito Nacional';

                $abrangencia = UsuarioAbrangencia::firstOrCreate([
                    'usuario_id'   => $solicitacao->usuario_id,
                    'esfera_id'    => $solicitacao->esfera_id,
                    'uf_id'        => $solicitacao->uf_id,
                    'municipio_id' => $solicitacao->municipio_id,
                ], [
                    'nome'                           => "{$solicitacao->esfera?->nome} - {$nomeLocalidade}",
                    'origem_tipo'                    => 'solicitacao_cadastro',
                    'solicitacao_cadastro_origem_id' => $solicitacao->id,
                    'ativo'                          => true,
                    'criado_por_usuario_id'          => auth()->id(),
                ]);

                $perfilUsuario = PerfilUsuario::updateOrCreate(
                    [
                        'usuario_id'             => $solicitacao->usuario_id,
                        'perfil_id'              => (int) $dados['perfil_id'],
                        'usuario_abrangencia_id' => $abrangencia->id,
                    ],
                    [
                        'data_inicio_vigencia'           => $dados['vigencia_inicio'] ?? now(),
                        'data_fim_vigencia'              => $dados['vigencia_fim'] ?? null,
                        'ativo'                          => true,
                        'origem_tipo'                    => 'solicitacao',
                        'atribuido_por_usuario_id'       => auth()->id(),
                        'solicitacao_cadastro_origem_id' => $solicitacao->id,
                    ]
                );

                $solicitacao->usuario->update(['ativo' => true]);

                // Preserva o contexto já selecionado do usuário quando houver um vínculo ativo válido.
                $contextoAtivoAtual = $solicitacao->usuario->contextoAtivo()->first();
                $perfilContextoAtivoValido = $contextoAtivoAtual
                    ? PerfilUsuario::query()
                        ->where('id', $contextoAtivoAtual->perfil_usuario_id)
                        ->where('usuario_id', $solicitacao->usuario_id)
                        ->where('ativo', true)
                        ->exists()
                    : false;

                if (! $perfilContextoAtivoValido) {
                    $solicitacao->usuario->contextoAtivo()->updateOrCreate(
                        ['usuario_id' => $solicitacao->usuario_id],
                        [
                            'perfil_usuario_id'      => $perfilUsuario->id,
                            'usuario_abrangencia_id' => $abrangencia->id,
                        ]
                    );
                }
            }
        });

        $contextoAudit = [
            'solicitacao_id' => $solicitacao->id,
            'status'         => $statusNome,
            'perfil_id'      => $dados['perfil_id'] ?? null,
            'abrangencia'    => $solicitacao->abrangencia?->nome,
        ];
        if ($statusNome === 'reprovado') {
            $contextoAudit['motivo_reprovacao'] = $dados['justificativa'] ?? null;
        }
        $this->audit->log(
            'gerenciar_cadastros.avaliacao',
            auth()->user()->id,
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

    public function verificarCpf(
        string $cpfRaw,
        ?int $esferaId = null,
        ?int $ufId = null,
        ?int $municipioId = null
    ): array {
        $cpf = preg_replace('/\D/', '', $cpfRaw);

        if (strlen($cpf) !== 11) {
            return ['disponivel' => false, 'mensagem' => 'Informe um CPF com 11 dígitos.'];
        }

        if (! CpfHelper::validar($cpf)) {
            return ['disponivel' => false, 'mensagem' => 'CPF inválido. Verifique os dígitos informados.'];
        }

        $usuario = Usuario::where('cpf', $cpf)->first();
        if (! $usuario) {
            return ['disponivel' => true, 'mensagem' => 'CPF disponível para cadastro.', 'perfis_ativos' => []];
        }

        $hoje = now()->toDateString();
        $perfisAtivosQuery = PerfilUsuario::query()
            ->with(['perfil:id,nome,codigo', 'abrangencia:id,esfera_id,uf_id,municipio_id'])
            ->where('usuario_id', $usuario->id)
            ->where('ativo', true)
            ->where(function ($q) use ($hoje) {
                $q->whereNull('data_inicio_vigencia')->orWhere('data_inicio_vigencia', '<=', $hoje);
            })
            ->where(function ($q) use ($hoje) {
                $q->whereNull('data_fim_vigencia')->orWhere('data_fim_vigencia', '>=', $hoje);
            });

        if ($esferaId !== null) {
            $perfisAtivosQuery->whereHas('abrangencia', fn ($q) => $q->where('esfera_id', $esferaId));
        }

        if ($ufId !== null) {
            $perfisAtivosQuery->whereHas('abrangencia', fn ($q) => $q->where('uf_id', $ufId));
        }

        if ($municipioId !== null) {
            $perfisAtivosQuery->whereHas('abrangencia', fn ($q) => $q->where('municipio_id', $municipioId));
        }

        $perfisAtivos = $perfisAtivosQuery
            ->get()
            ->map(fn ($perfilUsuario) => [
                'id'     => (int) $perfilUsuario->perfil_id,
                'nome'   => (string) ($perfilUsuario->perfil?->nome ?? ''),
                'codigo' => (string) ($perfilUsuario->perfil?->codigo ?? ''),
            ])
            ->unique('id')
            ->values()
            ->all();

        $mensagem = count($perfisAtivos) > 0
            ? 'Este CPF já possui perfil ativo nesta área de atuação. Não é possível abrir nova solicitação para esta área.'
            : 'CPF disponível para cadastro.';

        return [
            'disponivel'    => count($perfisAtivos) === 0,
            'mensagem'      => $mensagem,
            'perfis_ativos' => $perfisAtivos,
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

        $vinculo = PerfilUsuario::where('id', $perfilUsuarioId)
            ->where('usuario_id', $usuarioSolicitante->id)
            ->first();

        if (! $vinculo) {
            throw ApiException::notFound('Vínculo de perfil não encontrado.');
        }

        $vinculo->update([
            'data_inicio_vigencia' => $vinculo->data_inicio_vigencia ?: now()->toDateString(),
            'data_fim_vigencia'    => null,
            'ativo'                => true,
        ]);

        $this->audit->log('gerenciar_cadastros.perfil_ativado', $user->id, [
            'solicitacao_id'    => $solicitacao->id,
            'perfil_usuario_id' => $perfilUsuarioId,
            'usuario_id'        => $usuarioSolicitante->id,
        ], TipoAuditoria::UPDATE->name, 'perfil_usuario', $perfilUsuarioId);

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

        $statusPermitidos = [
            StatusSolicitacaoEnum::APROVADO->value,
            StatusSolicitacaoEnum::EM_ANALISE->value,
        ];

        if (! in_array($solicitacao->status_id, $statusPermitidos, true)) {
            throw ValidationException::withMessages([
                'status' => ['Apenas solicitações em análise ou aprovadas permitem adicionar perfis vinculados.'],
            ]);
        }

        $usuarioSolicitante = $solicitacao->usuario;
        if (! $usuarioSolicitante) {
            throw ApiException::notFound('Usuário da solicitação não encontrado.');
        }

        $this->validarHierarquiaPerfil((int) $dados['perfil_id']);

        $nomeLocalidade = $solicitacao->municipioRelacao?->nome
            ?? $solicitacao->ufRelacao?->nome
            ?? 'Âmbito Nacional';

        $abrangencia = UsuarioAbrangencia::firstOrCreate(
            [
                'usuario_id'   => $usuarioSolicitante->id,
                'esfera_id'    => $solicitacao->esfera_id,
                'uf_id'        => $solicitacao->uf_id,
                'municipio_id' => $solicitacao->municipio_id,
            ],
            [
                'nome'                  => "{$solicitacao->esfera?->nome} - {$nomeLocalidade}",
                'origem_tipo'           => 'solicitacao_cadastro',
                'ativo'                 => true,
                'criado_por_usuario_id' => auth()->id(),
            ]
        );

        $vinculoExistente = PerfilUsuario::where('usuario_id', $usuarioSolicitante->id)
            ->where('perfil_id', (int) $dados['perfil_id'])
            ->where('usuario_abrangencia_id', $abrangencia->id)
            ->first();

        if ($vinculoExistente) {
            throw ValidationException::withMessages([
                'perfil_id' => ['Este perfil já está vinculado ao usuário nesta abrangência.'],
            ]);
        }

        $vinculo = PerfilUsuario::create([
            'usuario_id'                     => $usuarioSolicitante->id,
            'perfil_id'                      => (int) $dados['perfil_id'],
            'usuario_abrangencia_id'         => $abrangencia->id,
            'data_inicio_vigencia'           => $dados['vigencia_inicio'] ?? null,
            'data_fim_vigencia'              => $dados['vigencia_fim'] ?? null,
            'ativo'                          => true,
            'origem_tipo'                    => 'manual',
            'atribuido_por_usuario_id'       => auth()->id(),
            'solicitacao_cadastro_origem_id' => $solicitacao->id,
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

    /**
     * Valida que o operador logado pode atribuir o perfil conforme hierarquia:
     * Federal → qualquer perfil | Estadual → só estadual | Municipal → só municipal.
     */
    private function validarHierarquiaPerfil(int $perfilId): void
    {
        $operador = auth()->user();
        if (! $operador) {
            return;
        }

        $perfilSolicitado = Perfil::find($perfilId);
        if (! $perfilSolicitado) {
            return;
        }

        $codigoPerfilOperador = MvpPerfilRules::resolveActiveProfileCode($operador);
        $codigoPerfilSolicitado = (string) $perfilSolicitado->codigo;

        if (! MvpPerfilRules::canEvaluatorAssign($codigoPerfilOperador, $codigoPerfilSolicitado)) {
            throw ValidationException::withMessages([
                'perfil_id' => ['O perfil autenticado não pode atribuir o perfil selecionado.'],
            ]);
        }
    }

    private function buscarSolicitacao(int $id): SolicitacaoCadastro
    {
        $solicitacao = SolicitacaoCadastro::with('usuario')->find($id);
        if (! $solicitacao) {
            throw ApiException::notFound('Solicitação não encontrada.');
        }

        return $solicitacao;
    }

    private function obterPerfisVinculados(SolicitacaoCadastro $solicitacao): array
    {
        $usuario = $solicitacao->usuario;
        if (! $usuario) {
            return [];
        }

        $hoje = now()->toDateString();
        $vinculos = $usuario->perfisUsuario()
            ->with(['perfil', 'abrangencia.esfera', 'abrangencia.uf', 'abrangencia.municipio', 'solicitacaoCadastroOrigem'])
            ->get();

        $perfis = $vinculos->map(function ($pu) use ($hoje, $solicitacao): array {
            $inicio = $this->normalizarDataPivot($pu->data_inicio_vigencia);
            $fim = $this->normalizarDataPivot($pu->data_fim_vigencia);
            $ativo = (bool) ($pu->ativo ?? false);
            $vigente = $ativo && (! $inicio || $inicio <= $hoje) && (! $fim || $fim >= $hoje);

            $abrangencia = $pu->abrangencia;
            $origem = $pu->solicitacaoCadastroOrigem;

            return [
                'id'                => $pu->id,
                'perfil_usuario_id' => $pu->id,
                'ativo'             => $ativo,
                'perfil'            => $pu->perfil?->nome ?? '—',
                'vigencia_inicio'   => $inicio ?? '—',
                'vigencia_fim'      => $fim ?? '—',
                'vigente'           => $vigente,
                'esfera'            => $abrangencia?->esfera?->nome ?? '—',
                'uf'                => $abrangencia?->uf?->sigla ?? '—',
                'municipio'         => $abrangencia?->municipio?->nome ?? '—',
                'orgao'             => $origem?->orgao ?? $solicitacao->orgao ?? '—',
                'cargo'             => $origem?->cargo ?? $solicitacao->cargo ?? '—',
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

    /**
     * Valida que o gestor autenticado respeita a hierarquia de esfera ao cadastrar usuário.
     * Federal: sem restrições.
     * Estadual: deve cadastrar na mesma UF, esfera estadual e perfil estadual.
     * Municipal: deve cadastrar na mesma UF + município, esfera municipal e perfil municipal.
     */
    private function validarHierarquiaCadastro(Usuario $gestor, array $dados): void
    {
        $gestor->loadMissing('contextoAtivo.abrangencia');
        $abrangencia = $gestor->contextoAtivo?->abrangencia;

        if (! $abrangencia) {
            return;
        }

        try {
            $esferaGestor = EsferaEnum::from((int) $abrangencia->esfera_id);
        } catch (\ValueError) {
            return;
        }

        if ($esferaGestor === EsferaEnum::FEDERAL) {
            return;
        }

        $esferaRequisitada = (int) ($dados['esfera_id'] ?? 0);
        $ufRequisitada = (int) ($dados['uf_id'] ?? 0);
        $municipioRequisitado = (int) ($dados['municipio_id'] ?? 0);
        $perfilRequisitadoId = isset($dados['perfil_id']) && $dados['perfil_id'] !== null
            ? (int) $dados['perfil_id']
            : null;

        if ($esferaGestor === EsferaEnum::ESTADUAL) {
            if ($esferaRequisitada !== EsferaEnum::ESTADUAL->value) {
                throw ApiException::forbidden('Acesso não permitido.');
            }
            if ($ufRequisitada !== (int) $abrangencia->uf_id) {
                throw ApiException::forbidden('Acesso não permitido.');
            }
            if ($perfilRequisitadoId !== null) {
                $perfil = Perfil::find($perfilRequisitadoId);
                if (! $perfil || $perfil->esfera_id !== EsferaEnum::ESTADUAL->value) {
                    throw ApiException::forbidden('Acesso não permitido.');
                }
            }

            return;
        }

        if ($esferaGestor === EsferaEnum::MUNICIPAL) {
            if ($esferaRequisitada !== EsferaEnum::MUNICIPAL->value) {
                throw ApiException::forbidden('Acesso não permitido.');
            }
            if ($ufRequisitada !== (int) $abrangencia->uf_id) {
                throw ApiException::forbidden('Acesso não permitido.');
            }
            if ($municipioRequisitado !== (int) $abrangencia->municipio_id) {
                throw ApiException::forbidden('Acesso não permitido.');
            }
            if ($perfilRequisitadoId !== null) {
                $perfil = Perfil::find($perfilRequisitadoId);
                if (! $perfil || $perfil->esfera_id !== EsferaEnum::MUNICIPAL->value) {
                    throw ApiException::forbidden('Acesso não permitido.');
                }
            }
        }
    }
}
