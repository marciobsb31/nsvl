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

        $statusEmAnalise = StatusSolicitacao::idPorNome(StatusSolicitacao::EM_ANALISE);

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
        $usuarioAutenticado = auth()->user();
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

        $existente = SolicitacaoCadastro::where('usuario_id', $usuario->id)
            ->where('status_id', StatusSolicitacaoEnum::EM_ANALISE)
            ->exists();

        if ($existente) {
            throw ApiException::unprocessable('Já existe uma solicitação em análise para este CPF. Aguarde a avaliação da equipe gestora antes de enviar uma nova solicitação.');
        }

        $perfilIdSolicitado = isset($dados['perfil_id']) && $dados['perfil_id'] !== null
            ? (int) $dados['perfil_id']
            : (isset($dados['perfilId']) && $dados['perfilId'] !== null ? (int) $dados['perfilId'] : null);
        if ($perfilIdSolicitado === 0) {
            $perfilIdSolicitado = null;
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
            'status_id'              => StatusSolicitacaoEnum::EM_ANALISE->value,
            'aceite_termo_at'        => Carbon::now(),
        ]);

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
                    'nome'                  => "{$solicitacao->esfera?->nome} - {$nomeLocalidade}",
                    'origem_tipo'           => 'solicitacao_cadastro',
                    'origem_id'             => $solicitacao->id,
                    'ativo'                 => true,
                    'criado_por_usuario_id' => auth()->id(),
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

                $solicitacao->usuario->contextoAtivo()->updateOrCreate(
                    ['usuario_id' => $solicitacao->usuario_id],
                    [
                        'perfil_usuario_id'      => $perfilUsuario->id,
                        'usuario_abrangencia_id' => $abrangencia->id,
                    ]
                );
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
            return ['disponivel' => false, 'mensagem' => 'Já existe uma solicitação em análise para este CPF.'];
        }

        // Verificar se possui perfil vigente (ativo) — se sim, bloqueia
        $usuario = Usuario::where('cpf', $cpf)->first();
        if ($usuario && $usuario->perfisVigentes()) {
            return ['disponivel' => false, 'mensagem' => 'Este CPF já possui perfil ativo no sistema.'];
        }

        return ['disponivel' => true, 'mensagem' => 'CPF disponível para cadastro.'];
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

        $operador->load('contextoAtivo.abrangencia');
        $esferaIdOperador = $operador->contextoAtivo?->abrangencia?->esfera_id;

        // Federal (ou sem contexto definido) pode atribuir qualquer perfil
        if ($esferaIdOperador === null || (int) $esferaIdOperador === EsferaEnum::FEDERAL->value) {
            return;
        }

        $perfilSolicitado = \App\Models\Perfil::find($perfilId);
        if (! $perfilSolicitado) {
            return;
        }

        $esferaIdPerfil = (int) $perfilSolicitado->esfera_id;

        if ((int) $esferaIdOperador === EsferaEnum::ESTADUAL->value && $esferaIdPerfil !== EsferaEnum::ESTADUAL->value) {
            throw ValidationException::withMessages([
                'perfil_id' => ['Perfil estadual só pode atribuir perfis do tipo Estadual.'],
            ]);
        }

        if ((int) $esferaIdOperador === EsferaEnum::MUNICIPAL->value && $esferaIdPerfil !== EsferaEnum::MUNICIPAL->value) {
            throw ValidationException::withMessages([
                'perfil_id' => ['Perfil municipal só pode atribuir perfis do tipo Municipal.'],
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
        $vinculos = $usuario->perfis()
            ->withPivot(['id', 'data_inicio_vigencia', 'data_fim_vigencia', 'ativo'])
            ->get();

        $perfis = $vinculos->map(function ($perfil, $index) use ($hoje, $solicitacao): array {
            $inicio = $this->normalizarDataPivot($perfil->pivot->data_inicio_vigencia);
            $fim = $this->normalizarDataPivot($perfil->pivot->data_fim_vigencia);
            $perfilUsuarioId = (int) ($perfil->pivot->id ?? (($solicitacao->id * 1000) + $perfil->id + $index));
            $ativo = (bool) ($perfil->pivot->ativo ?? false);
            $vigente = $ativo && (! $inicio || $inicio <= $hoje) && (! $fim || $fim >= $hoje);

            return [
                'id'                => $perfilUsuarioId,
                'perfil_usuario_id' => $perfilUsuarioId,
                'ativo'             => $ativo,
                'perfil'            => $perfil->nome,
                'vigencia_inicio'   => $inicio ?? '—',
                'vigencia_fim'      => $fim ?? '—',
                'vigente'           => $vigente,
                'esfera'            => $solicitacao->esfera?->nome ?? '—',
                'uf'                => $solicitacao->ufRelacao?->sigla ?? '—',
                'municipio'         => $solicitacao->municipioRelacao?->nome ?? '—',
                'orgao'             => $solicitacao->orgao ?? '—',
                'cargo'             => $solicitacao->cargo ?? '—',
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

        $esferaRequisitada  = (int) ($dados['esfera_id'] ?? 0);
        $ufRequisitada      = (int) ($dados['uf_id'] ?? 0);
        $municipioRequisitado = (int) ($dados['municipio_id'] ?? 0);
        $perfilRequisitadoId  = isset($dados['perfil_id']) && $dados['perfil_id'] !== null
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
