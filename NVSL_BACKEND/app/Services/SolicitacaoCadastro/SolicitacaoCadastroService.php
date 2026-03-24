<?php

namespace App\Services\SolicitacaoCadastro;

use App\Exceptions\ApiException;
use App\Helpers\CpfHelper;
use App\Models\AuditLog;
use App\Models\Perfil;
use App\Models\PerfilUsuario;
use App\Models\SolicitacaoCadastro;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SolicitacaoCadastroService
{
    public function __construct(
        private readonly AuditLogService $audit
    ) {}

    // ------------------------------------------------------------------
    // Listar
    // ------------------------------------------------------------------

    public function listar(User $user, array $filtros): array
    {
        $query = SolicitacaoCadastro::query()->visivelPara($user);

        if (!empty($filtros['cpf'])) {
            $query->where('cpf_hash', User::hashCpf($filtros['cpf']));
        }
        if (!empty($filtros['nome'])) {
            $this->aplicarFiltroLikeInsensitive($query, 'nome', $filtros['nome']);
        }
        if (!empty($filtros['uf'])) {
            $query->where('uf', strtoupper($filtros['uf']));
        }
        if (!empty($filtros['municipio'])) {
            $this->aplicarFiltroLikeInsensitive($query, 'municipio', $filtros['municipio']);
        }
        if (!empty($filtros['orgao'])) {
            $this->aplicarFiltroLikeInsensitive($query, 'orgao', $filtros['orgao']);
        }
        if (!empty($filtros['esfera'])) {
            $query->where('esfera_atuacao', $filtros['esfera']);
        }
        if (!empty($filtros['status'])) {
            $query->where('status', $filtros['status']);
        }

        $solicitacoes = $query->orderBy('created_at', 'desc')->get();

        $itens = $solicitacoes->map(fn (SolicitacaoCadastro $s) => [
            'id'             => $s->id,
            'nome'           => $s->nome,
            'status'         => $s->status,
            'created_at'     => $s->created_at?->toIso8601String(),
            'cpf'            => $s->cpf_mascarado,
            'esfera_atuacao' => $s->esfera_atuacao,
            'uf'             => $s->uf,
            'municipio'      => $s->municipio,
            'orgao'          => $s->orgao,
        ])->values()->all();

        $this->audit->log('gerenciar_cadastros.listagem', $user->id, [
            'total_registros' => count($itens),
            'filtros'         => array_filter($filtros),
        ], AuditLog::TIPO_VIEW);

        return $itens;
    }

    // ------------------------------------------------------------------
    // Detalhar
    // ------------------------------------------------------------------

    public function detalhar(User $user, int $id): array
    {
        $solicitacao = SolicitacaoCadastro::find($id);
        if (!$solicitacao) {
            throw ApiException::notFound('Solicitação não encontrada.');
        }

        $this->audit->log('gerenciar_cadastros.detalhamento', $user->id, [
            'solicitacao_id' => $solicitacao->id,
        ], AuditLog::TIPO_VIEW, 'solicitacoes_cadastro', $solicitacao->id);

        try {
            $perfisVinculados = $this->obterPerfisVinculados($solicitacao);
        } catch (\Throwable $e) {
            $perfisVinculados = [];
            report($e);
        }

        return [
            'id'                     => $solicitacao->id,
            'nome'                   => $solicitacao->nome,
            'cpf'                    => $solicitacao->cpf_mascarado,
            'status'                 => $solicitacao->status,
            'created_at'             => $solicitacao->created_at?->toIso8601String(),
            'updated_at'             => $solicitacao->updated_at?->toIso8601String(),
            'email_institucional'    => $solicitacao->email_institucional,
            'telefone_institucional' => $solicitacao->telefone_institucional,
            'telefone_pessoal'       => $solicitacao->telefone_pessoal,
            'esfera_atuacao'         => $solicitacao->esfera_atuacao,
            'uf'                     => $solicitacao->uf,
            'municipio'              => $solicitacao->municipio,
            'orgao'                  => $solicitacao->orgao,
            'cargo'                  => $solicitacao->cargo,
            'perfis_vinculados'      => $perfisVinculados,
            'pode_avaliar'           => $solicitacao->status === SolicitacaoCadastro::STATUS_EM_ANALISE,
        ];
    }

    // ------------------------------------------------------------------
    // Criar (store)
    // ------------------------------------------------------------------

    public function criar(?User $user, array $dados): array
    {
        $cpfDigits = preg_replace('/\D/', '', $dados['CPF'] ?? '');
        $cpfHash   = strlen($cpfDigits) === 11
            ? User::hashCpf($cpfDigits)
            : ($user?->cpf_hash ?? null);
        $cpfExibicao = strlen($cpfDigits) === 11 ? CpfHelper::mascarar($cpfDigits) : null;

        if (!$cpfHash) {
            throw ApiException::unprocessable('CPF é obrigatório para solicitação sem autenticação.');
        }

        $existente = SolicitacaoCadastro::where('cpf_hash', $cpfHash)
            ->where('status', SolicitacaoCadastro::STATUS_EM_ANALISE)
            ->exists();

        if ($existente) {
            throw ApiException::unprocessable('Já existe uma solicitação em análise para este CPF.');
        }

        $esfera    = strtolower((string) ($dados['esferaAtuacao'] ?? ''));
        $uf        = strtoupper((string) ($dados['uf'] ?? ''));
        $municipio = trim((string) ($dados['municipio'] ?? ''));

        $this->verificarDuplicidade($cpfHash, $esfera, $uf, $municipio);

        if ($user) {
            $this->validarRegrasHierarquiaCadastro(
                $user,
                (int) ($dados['perfilId'] ?? 0),
                $esfera,
                $uf,
                $municipio
            );
        }

        $solicitacao = SolicitacaoCadastro::create([
            'cpf_hash'                   => $cpfHash,
            'cpf_exibicao'               => $cpfExibicao,
            'nome'                       => $dados['nome'],
            'email_institucional'        => $dados['emailInstitucional'],
            'telefone_institucional'     => $dados['telefoneInstitucional'] ?? null,
            'telefone_pessoal'           => $dados['telefonePessoal'] ?? null,
            'esfera_atuacao'             => $dados['esferaAtuacao'],
            'uf'                         => $dados['uf'],
            'municipio'                  => $dados['municipio'],
            'orgao'                      => $dados['orgao'],
            'cargo'                      => $dados['cargo'] ?? null,
            'perfil_id_solicitado'       => $dados['perfilId'] ?? null,
            'vigencia_inicio_solicitada' => $dados['vigenciaInicio'] ?? null,
            'vigencia_fim_solicitada'    => $dados['vigenciaFim'] ?? null,
            'status'                     => SolicitacaoCadastro::STATUS_EM_ANALISE,
            'aceite_termo_at'            => Carbon::now(),
        ]);

        $this->audit->log(
            $user ? 'gerenciar_cadastros.solicitacao_interna_criada' : 'solicitacao_cadastro.criada',
            $user?->id,
            [
                'solicitacao_id'       => $solicitacao->id,
                'perfil_id_solicitado' => $solicitacao->perfil_id_solicitado,
                'esfera_atuacao'       => $solicitacao->esfera_atuacao,
                'uf'                   => $solicitacao->uf,
                'municipio'            => $solicitacao->municipio,
                'origem'               => $user ? 'painel_interno' : 'formulario_publico',
            ],
            AuditLog::TIPO_INSERT,
            'solicitacoes_cadastro',
            $solicitacao->id
        );

        try {
            \Illuminate\Support\Facades\Mail::to($dados['emailInstitucional'])
                ->send(new \App\Mail\SolicitacaoCadastroEnviada($solicitacao));
        } catch (\Throwable $e) {
            \Log::warning('[Cadastro] Falha ao enviar e-mail de confirmação', [
                'solicitacao_id' => $solicitacao->id,
                'erro'           => $e->getMessage(),
            ]);
        }

        return [
            'message'        => 'Solicitação registrada com sucesso.',
            'solicitacao_id' => $solicitacao->id,
        ];
    }

    // ------------------------------------------------------------------
    // Avaliar (aprovar / reprovar)
    // ------------------------------------------------------------------

    public function avaliar(User $user, int $id, array $dados): array
    {
        $solicitacao = SolicitacaoCadastro::find($id);
        if (!$solicitacao) {
            throw ApiException::notFound('Solicitação não encontrada.');
        }

        if ($solicitacao->status !== SolicitacaoCadastro::STATUS_EM_ANALISE) {
            throw ValidationException::withMessages([
                'status' => ['Apenas solicitações em análise podem ser aprovadas ou reprovadas.'],
            ]);
        }

        $status = $dados['status'];

        DB::transaction(function () use ($solicitacao, $status, $dados): void {
            $updateData = ['status' => $status];
            if ($status === SolicitacaoCadastro::STATUS_REPROVADO) {
                $updateData['justificativa_reprovacao'] = $dados['justificativa'] ?? null;
            }
            $solicitacao->update($updateData);

            if ($status === SolicitacaoCadastro::STATUS_APROVADO) {
                $usuarioProvisionado = $this->provisionarUsuarioAprovado($solicitacao);

                PerfilUsuario::updateOrCreate(
                    [
                        'usuario_id' => $usuarioProvisionado->id,
                        'perfil_id'  => (int) $dados['perfil_id'],
                    ],
                    [
                        'data_inicio_vigencia' => $dados['vigencia_inicio'] ?? null,
                        'data_fim_vigencia'    => $dados['vigencia_fim'] ?? null,
                    ]
                );
            }
        });

        $this->audit->log('gerenciar_cadastros.avaliacao', $user->id, [
            'solicitacao_id' => $solicitacao->id,
            'status'         => $status,
            'perfil_id'      => $dados['perfil_id'] ?? null,
        ], AuditLog::TIPO_UPDATE, 'solicitacoes_cadastro', $solicitacao->id);

        return [
            'message' => $status === SolicitacaoCadastro::STATUS_APROVADO
                ? 'Solicitação aprovada.'
                : 'Solicitação reprovada.',
            'data' => [
                'id'     => $solicitacao->id,
                'status' => $solicitacao->status,
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

        if (!CpfHelper::validar($cpf)) {
            return ['disponivel' => false, 'mensagem' => 'CPF inválido. Verifique os dígitos informados.'];
        }

        $cpfHash = User::hashCpf($cpf);

        if (User::where('cpf_hash', $cpfHash)->exists()) {
            return ['disponivel' => false, 'mensagem' => 'Este CPF já possui cadastro ativo no sistema.'];
        }

        $emAnalise = SolicitacaoCadastro::where('cpf_hash', $cpfHash)
            ->where('status', SolicitacaoCadastro::STATUS_EM_ANALISE)
            ->exists();

        if ($emAnalise) {
            return ['disponivel' => false, 'mensagem' => 'Já existe uma solicitação em análise para este CPF.'];
        }

        return ['disponivel' => true, 'mensagem' => 'CPF disponível para cadastro.'];
    }

    // ------------------------------------------------------------------
    // Ativar perfil vinculado
    // ------------------------------------------------------------------

    public function ativarPerfil(User $user, int $solicitacaoId, int $perfilUsuarioId): array
    {
        $solicitacao = $this->buscarSolicitacao($solicitacaoId);
        $usuarioSolicitante = $this->obterUsuarioDaSolicitacaoAprovada($solicitacao);
        if (!$usuarioSolicitante) {
            throw ApiException::notFound('Usuário da solicitação não encontrado.');
        }

        $vinculo = PerfilUsuario::where('id', $perfilUsuarioId)
            ->where('usuario_id', $usuarioSolicitante->id)
            ->first();

        if (!$vinculo) {
            throw ApiException::notFound('Vínculo de perfil não encontrado.');
        }

        $vinculo->update([
            'data_inicio_vigencia' => $vinculo->data_inicio_vigencia ?: now()->toDateString(),
            'data_fim_vigencia'    => null,
        ]);

        $this->audit->log('gerenciar_cadastros.perfil_ativado', $user->id, [
            'solicitacao_id'    => $solicitacao->id,
            'perfil_usuario_id' => $vinculo->id,
            'usuario_id'        => $usuarioSolicitante->id,
        ], AuditLog::TIPO_UPDATE, 'perfil_usuario', $vinculo->id);

        return ['message' => 'Perfil vinculado ativado com sucesso.', 'data' => ['id' => $vinculo->id]];
    }

    // ------------------------------------------------------------------
    // Desativar perfil vinculado
    // ------------------------------------------------------------------

    public function desativarPerfil(User $user, int $solicitacaoId, int $perfilUsuarioId): array
    {
        $solicitacao = $this->buscarSolicitacao($solicitacaoId);
        $usuarioSolicitante = $this->obterUsuarioDaSolicitacaoAprovada($solicitacao);
        if (!$usuarioSolicitante) {
            throw ApiException::notFound('Usuário da solicitação não encontrado.');
        }

        $vinculo = PerfilUsuario::where('id', $perfilUsuarioId)
            ->where('usuario_id', $usuarioSolicitante->id)
            ->first();

        if (!$vinculo) {
            throw ApiException::notFound('Vínculo de perfil não encontrado.');
        }

        $vinculo->update([
            'data_fim_vigencia' => now()->subDay()->toDateString(),
        ]);

        $this->audit->log('gerenciar_cadastros.perfil_desativado', $user->id, [
            'solicitacao_id'    => $solicitacao->id,
            'perfil_usuario_id' => $vinculo->id,
            'usuario_id'        => $usuarioSolicitante->id,
        ], AuditLog::TIPO_UPDATE, 'perfil_usuario', $vinculo->id);

        return ['message' => 'Perfil vinculado desativado com sucesso.', 'data' => ['id' => $vinculo->id]];
    }

    // ------------------------------------------------------------------
    // Adicionar perfil vinculado
    // ------------------------------------------------------------------

    public function adicionarPerfil(User $user, int $solicitacaoId, array $dados): array
    {
        $solicitacao = $this->buscarSolicitacao($solicitacaoId);

        if ($solicitacao->status !== SolicitacaoCadastro::STATUS_APROVADO) {
            throw ValidationException::withMessages([
                'status' => ['Apenas solicitações aprovadas permitem adicionar novos perfis vinculados.'],
            ]);
        }

        $usuarioSolicitante = $this->obterUsuarioDaSolicitacaoAprovada($solicitacao);
        if (!$usuarioSolicitante) {
            throw ApiException::notFound('Usuário da solicitação não encontrado.');
        }

        $vinculoExistente = PerfilUsuario::where('usuario_id', $usuarioSolicitante->id)
            ->where('perfil_id', (int) $dados['perfil_id'])
            ->first();

        if ($vinculoExistente) {
            throw ValidationException::withMessages([
                'perfil_id' => ['Este perfil já está vinculado ao usuário.'],
            ]);
        }

        $vinculo = PerfilUsuario::create([
            'usuario_id'           => $usuarioSolicitante->id,
            'perfil_id'            => (int) $dados['perfil_id'],
            'data_inicio_vigencia' => $dados['vigencia_inicio'] ?? null,
            'data_fim_vigencia'    => $dados['vigencia_fim'] ?? null,
        ]);

        $this->audit->log('gerenciar_cadastros.perfil_adicionado', $user->id, [
            'solicitacao_id'    => $solicitacao->id,
            'perfil_usuario_id' => $vinculo->id,
            'perfil_id'         => $vinculo->perfil_id,
            'usuario_id'        => $usuarioSolicitante->id,
        ], AuditLog::TIPO_INSERT, 'perfil_usuario', $vinculo->id);

        return ['message' => 'Perfil vinculado adicionado com sucesso.', 'data' => ['id' => $vinculo->id]];
    }

    // ==================================================================
    // Metodos privados auxiliares
    // ==================================================================

    private function buscarSolicitacao(int $id): SolicitacaoCadastro
    {
        $solicitacao = SolicitacaoCadastro::find($id);
        if (!$solicitacao) {
            throw ApiException::notFound('Solicitação não encontrada.');
        }
        return $solicitacao;
    }

    private function provisionarUsuarioAprovado(SolicitacaoCadastro $solicitacao): User
    {
        $usuario = User::where('cpf_hash', $solicitacao->cpf_hash)->first();

        if (!$usuario) {
            $usuario = new User();
            $usuario->govbr_sub = 'pending-' . $solicitacao->id . '-' . substr($solicitacao->cpf_hash, 0, 12);
        }

        $usuario->fill([
            'cpf_hash'          => $solicitacao->cpf_hash,
            'name'              => $solicitacao->nome,
            'email'             => $solicitacao->email_institucional,
            'role'              => $usuario->role ?: 'user',
            'esfera_atuacao'    => $solicitacao->esfera_atuacao,
            'uf_lotacao'        => $solicitacao->uf,
            'municipio_lotacao' => $solicitacao->municipio,
        ]);

        $usuario->save();

        return $usuario;
    }

    private function obterPerfisVinculados(SolicitacaoCadastro $solicitacao): array
    {
        if ($solicitacao->status !== SolicitacaoCadastro::STATUS_APROVADO) {
            return [];
        }

        $usuario = User::where('cpf_hash', $solicitacao->cpf_hash)->first();
        if (!$usuario) {
            return [];
        }

        $hoje    = now()->toDateString();
        $vinculos = $usuario->perfis()
            ->withPivot(['id', 'data_inicio_vigencia', 'data_fim_vigencia'])
            ->get();

        $perfis = $vinculos->map(function ($perfil, $index) use ($hoje, $usuario, $solicitacao): array {
            $inicio  = $this->normalizarDataPivot($perfil->pivot->data_inicio_vigencia);
            $fim     = $this->normalizarDataPivot($perfil->pivot->data_fim_vigencia);
            $vigente = (!$inicio || $inicio <= $hoje) && (!$fim || $fim >= $hoje);
            $id      = (int) ($perfil->pivot->id ?? (($solicitacao->id * 1000) + $perfil->id + $index));

            return [
                'id'              => $id,
                'perfil'          => $perfil->nome,
                'vigencia_inicio' => $inicio ?? '—',
                'vigencia_fim'    => $fim ?? '—',
                'vigente'         => $vigente,
                'esfera'          => $this->labelEsfera($usuario->esfera_atuacao),
                'uf'              => $usuario->uf_lotacao ?? '—',
                'municipio'       => $usuario->municipio_lotacao ?? '—',
                'orgao'           => $solicitacao->orgao ?? '—',
                'cargo'           => $solicitacao->cargo ?? '—',
            ];
        })->all();

        usort($perfis, fn ($a, $b) => ($b['vigente'] ? 1 : 0) - ($a['vigente'] ? 1 : 0));

        return $perfis;
    }

    private function obterUsuarioDaSolicitacaoAprovada(SolicitacaoCadastro $solicitacao): ?User
    {
        if ($solicitacao->status !== SolicitacaoCadastro::STATUS_APROVADO) {
            return null;
        }

        return User::where('cpf_hash', $solicitacao->cpf_hash)->first();
    }

    private function verificarDuplicidade(string $cpfHash, string $esfera, string $uf, string $municipio): void
    {
        $query = SolicitacaoCadastro::where('cpf_hash', $cpfHash)
            ->where('status', SolicitacaoCadastro::STATUS_APROVADO);

        if ($esfera === 'federal') {
            $query->where('esfera_atuacao', 'federal');
        } elseif ($esfera === 'estadual') {
            $query->where('esfera_atuacao', 'estadual')->where('uf', $uf);
        } elseif ($esfera === 'municipal') {
            $query->where('esfera_atuacao', 'municipal')
                ->where('uf', $uf)
                ->where('municipio', $municipio);
        }

        if ($query->exists()) {
            $msg = match ($esfera) {
                'federal'   => 'Você já possui um perfil ativo para a esfera Federal.',
                'estadual'  => "Você já possui um perfil ativo para a esfera Estadual na UF {$uf}.",
                'municipal' => "Você já possui um perfil ativo para a esfera Municipal em {$municipio}/{$uf}.",
                default     => 'Você já possui um perfil ativo para a combinação informada.',
            };
            throw ApiException::unprocessable($msg);
        }
    }

    private function validarRegrasHierarquiaCadastro(
        User $user,
        int $perfilId,
        string $esferaAtuacao,
        string $uf,
        string $municipio
    ): void {
        $perfil = Perfil::find($perfilId);
        if (!$perfil) {
            throw ApiException::unprocessable('Perfil informado é inválido.');
        }

        $tipoPerfil     = $this->inferirTipoPerfilPorNome($perfil->nome);
        $esferaUser     = strtolower((string) ($user->esfera_atuacao ?? 'federal'));
        $esferaAtuacao  = strtolower(trim($esferaAtuacao));
        $uf             = strtoupper(trim($uf));
        $municipio      = trim($municipio);

        if ($esferaUser === 'federal') {
            return;
        }

        if ($esferaUser === 'estadual') {
            if ($tipoPerfil !== 'estadual' || $esferaAtuacao !== 'estadual') {
                throw ApiException::forbidden('Acesso não permitido.');
            }
            if (($user->uf_lotacao ?? '') !== $uf) {
                throw ApiException::forbidden('Acesso não permitido.');
            }
            return;
        }

        if ($esferaUser === 'municipal') {
            if ($tipoPerfil !== 'municipal' || $esferaAtuacao !== 'municipal') {
                throw ApiException::forbidden('Acesso não permitido.');
            }
            if (($user->uf_lotacao ?? '') !== $uf) {
                throw ApiException::forbidden('Acesso não permitido.');
            }
            if (Str::lower(trim((string) $user->municipio_lotacao)) !== Str::lower($municipio)) {
                throw ApiException::forbidden('Acesso não permitido.');
            }
            return;
        }

        throw ApiException::forbidden('Acesso não permitido.');
    }

    private function labelEsfera(?string $esfera): string
    {
        return match ($esfera) {
            'federal'   => 'Federal',
            'estadual'  => 'Estadual',
            'municipal' => 'Municipal',
            default     => $esfera ?? '—',
        };
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

    private function inferirTipoPerfilPorNome(string $nomePerfil): string
    {
        $nome = Str::lower($nomePerfil);
        if (str_contains($nome, 'nacional')) return 'federal';
        if (str_contains($nome, 'estadual')) return 'estadual';
        if (str_contains($nome, 'municipal')) return 'municipal';
        return 'desconhecido';
    }

    /**
     * LIKE case-insensitive seguro (PostgreSQL e SQLite).
     *
     * @param \Illuminate\Database\Eloquent\Builder<SolicitacaoCadastro> $query
     */
    private function aplicarFiltroLikeInsensitive(\Illuminate\Database\Eloquent\Builder $query, string $column, string $value): void
    {
        $allowed = ['nome', 'municipio', 'orgao'];
        if (!in_array($column, $allowed, true)) {
            throw new \InvalidArgumentException('Coluna inválida para filtro.');
        }
        $term = mb_strtolower(trim($value), 'UTF-8');
        if ($term === '') {
            return;
        }
        $query->whereRaw('LOWER(' . $column . ') LIKE ?', ['%' . $term . '%']);
    }
}
