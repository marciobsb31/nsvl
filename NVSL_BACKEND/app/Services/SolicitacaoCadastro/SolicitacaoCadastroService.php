<?php

namespace App\Services\SolicitacaoCadastro;

use App\Exceptions\ApiException;
use App\Helpers\CpfHelper;
use App\Models\AuditLog;
use App\Models\Esfera;
use App\Models\Municipio;
use App\Models\Perfil;
use App\Models\PerfilUsuario;
use App\Models\SolicitacaoCadastro;
use App\Models\StatusSolicitacao;
use App\Models\Uf;
use App\Models\Usuario;
use App\Services\Audit\AuditLogService;
use Illuminate\Support\Carbon;
use Illuminate\Database\QueryException;
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

    public function listar(Usuario $user, array $filtros): array
    {
        $query = SolicitacaoCadastro::query()
            ->with(['usuario', 'esfera', 'ufRelacao', 'municipioRelacao', 'statusSolicitacao'])
            ->visivelPara($user);

        if (!empty($filtros['cpf'])) {
            $cpfDigits = preg_replace('/\D/', '', $filtros['cpf']);
            $query->whereHas('usuario', fn ($q) => $q->where('cpf', $cpfDigits));
        }
        if (!empty($filtros['nome'])) {
            $term = mb_strtolower(trim($filtros['nome']), 'UTF-8');
            $query->whereHas('usuario', fn ($q) => $q->whereRaw('LOWER(nome) LIKE ?', ['%' . $term . '%']));
        }
        if (!empty($filtros['uf'])) {
            $ufId = Uf::where('sigla', strtoupper($filtros['uf']))->value('id');
            $query->where('uf_id', $ufId);
        }
        if (!empty($filtros['municipio'])) {
            $term = mb_strtolower(trim($filtros['municipio']), 'UTF-8');
            $query->whereHas('municipioRelacao', fn ($q) => $q->whereRaw('LOWER(nome) LIKE ?', ['%' . $term . '%']));
        }
        if (!empty($filtros['orgao'])) {
            $term = mb_strtolower(trim($filtros['orgao']), 'UTF-8');
            $query->whereRaw('LOWER(orgao) LIKE ?', ['%' . $term . '%']);
        }
        if (!empty($filtros['esfera'])) {
            $esferaId = Esfera::whereRaw('LOWER(nome) = ?', [strtolower($filtros['esfera'])])->value('id');
            $query->where('esfera_id', $esferaId);
        }
        if (!empty($filtros['status'])) {
            $statusId = StatusSolicitacao::where('nome', $filtros['status'])->value('id');
            $query->where('status_id', $statusId);
        }

        $solicitacoes = $query->orderBy('created_at', 'desc')->get();

        $itens = $solicitacoes->map(fn (SolicitacaoCadastro $s) => [
            'id'             => $s->id,
            'nome'           => $s->usuario?->nome ?? '',
            'status'         => $s->statusSolicitacao?->nome ?? '',
            'created_at'     => $s->created_at?->toIso8601String(),
            'cpf'            => CpfHelper::mascarar($s->usuario?->cpf ?? ''),
            'esfera_atuacao' => $s->esfera?->nome ?? '',
            'uf'             => $s->ufRelacao?->sigla ?? '',
            'municipio'      => $s->municipioRelacao?->nome ?? '',
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

    public function detalhar(Usuario $user, int $id): array
    {
        $solicitacao = SolicitacaoCadastro::with([
            'usuario', 'esfera', 'ufRelacao', 'municipioRelacao', 'statusSolicitacao',
        ])->find($id);

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
            'esfera_atuacao'             => $solicitacao->esfera?->nome ?? '',
            'uf'                         => $solicitacao->ufRelacao?->sigla ?? '',
            'municipio'                  => $solicitacao->municipioRelacao?->nome ?? '',
            'orgao'                      => $solicitacao->orgao,
            'cargo'                      => $solicitacao->cargo,
            'perfil_id_solicitado'       => $solicitacao->perfil_id_solicitado,
            'vigencia_inicio_solicitada' => $solicitacao->vigencia_inicio_solicitada?->format('Y-m-d'),
            'vigencia_fim_solicitada'    => $solicitacao->vigencia_fim_solicitada?->format('Y-m-d'),
            'perfis_vinculados'          => $perfisVinculados,
            'pode_avaliar'               => $solicitacao->status_id === $statusEmAnalise,
        ];
    }

    // ------------------------------------------------------------------
    // Criar (store)
    // ------------------------------------------------------------------

    public function criar(?Usuario $user, array $dados): array
    {
        $cpfDigits = preg_replace('/\D/', '', $dados['CPF'] ?? '');

        if (strlen($cpfDigits) !== 11 && !$user) {
            throw ApiException::unprocessable('CPF é obrigatório para solicitação sem autenticação.');
        }

        $cpf = strlen($cpfDigits) === 11 ? $cpfDigits : ($user?->cpf ?? null);

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
                    'cpf'       => $cpf,
                    'nome'      => $dados['nome'],
                    'email'     => $dados['emailInstitucional'],
                    'govbr_sub' => $govbrSub,
                    'telefone'  => $dados['telefoneInstitucional'] ?? null,
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
        }

        $existente = SolicitacaoCadastro::where('user_id', $usuario->id)
            ->where('status_id', $statusEmAnalise)
            ->exists();

        if ($existente) {
            throw ApiException::unprocessable('Já existe uma solicitação em análise para este CPF.');
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

        $this->assertOperadorPodeRegistrarSolicitacao(
            $user,
            $esferaNome,
            $ufSigla,
            $municipioId,
            $perfilIdSolicitado
        );

        $this->verificarDuplicidade($usuario->id, $esferaId, $ufId, $municipioId);

        $vigenciaInicioSol = $this->normalizarDataSolicitacaoOpcional($dados['vigenciaInicio'] ?? null);
        $vigenciaFimSol    = $this->normalizarDataSolicitacaoOpcional($dados['vigenciaFim'] ?? null);

        $solicitacao = SolicitacaoCadastro::create([
            'user_id'                    => $usuario->id,
            'email_institucional'        => $dados['emailInstitucional'],
            'telefone_institucional'     => $dados['telefoneInstitucional'] ?? null,
            'esfera_id'                  => $esferaId,
            'uf_id'                      => $ufId,
            'municipio_id'               => $municipioId,
            'orgao'                      => $dados['orgao'],
            'cargo'                      => $dados['cargo'] ?? null,
            'perfil_id_solicitado'       => $perfilIdSolicitado,
            'vigencia_inicio_solicitada' => $vigenciaInicioSol,
            'vigencia_fim_solicitada'    => $vigenciaFimSol,
            'status_id'                  => $statusEmAnalise,
            'aceite_termo_at'            => Carbon::now(),
        ]);

        $this->audit->log(
            $user ? 'gerenciar_cadastros.solicitacao_interna_criada' : 'solicitacao_cadastro.criada',
            $user?->id,
            [
                'solicitacao_id'       => $solicitacao->id,
                'perfil_id_solicitado' => $solicitacao->perfil_id_solicitado,
                'esfera_id'            => $solicitacao->esfera_id,
                'uf_id'                => $solicitacao->uf_id,
                'municipio_id'         => $solicitacao->municipio_id,
                'origem'               => $user ? 'painel_interno' : 'formulario_publico',
            ],
            AuditLog::TIPO_INSERT,
            'solicitacoes_cadastro',
            $solicitacao->id
        );

        return [
            'message'        => 'Solicitação registrada com sucesso.',
            'solicitacao_id' => $solicitacao->id,
        ];
    }

    // ------------------------------------------------------------------
    // Avaliar (aprovar / reprovar)
    // ------------------------------------------------------------------

    public function avaliar(Usuario $user, int $id, array $dados): array
    {
        $solicitacao = SolicitacaoCadastro::with(['usuario', 'esfera', 'ufRelacao', 'municipioRelacao'])->find($id);
        if (!$solicitacao) {
            throw ApiException::notFound('Solicitação não encontrada.');
        }

        $statusEmAnalise = StatusSolicitacao::idPorNome(StatusSolicitacao::EM_ANALISE);
        $statusAprovado  = StatusSolicitacao::idPorNome(StatusSolicitacao::APROVADO);
        $statusReprovado = StatusSolicitacao::idPorNome(StatusSolicitacao::REPROVADO);

        if ($solicitacao->status_id !== $statusEmAnalise) {
            throw ValidationException::withMessages([
                'status' => ['Apenas solicitações em análise podem ser aprovadas ou reprovadas.'],
            ]);
        }

        $statusNome = $dados['status'];
        $novoStatusId = $statusNome === 'aprovado' ? $statusAprovado : $statusReprovado;

        DB::transaction(function () use ($solicitacao, $novoStatusId, $statusAprovado, $dados): void {
            $updateData = ['status_id' => $novoStatusId];
            if ($novoStatusId !== $statusAprovado) {
                $updateData['justificativa_reprovacao'] = $dados['justificativa'] ?? null;
            }
            $solicitacao->update($updateData);

            if ($novoStatusId === $statusAprovado) {
                PerfilUsuario::updateOrCreate(
                    [
                        'usuario_id' => $solicitacao->user_id,
                        'perfil_id'  => (int) $dados['perfil_id'],
                    ],
                    [
                        'data_inicio_vigencia' => $dados['vigencia_inicio'] ?? null,
                        'data_fim_vigencia'    => $dados['vigencia_fim'] ?? null,
                        'ativo'                => true,
                    ]
                );
            }
        });

        $this->audit->log('gerenciar_cadastros.avaliacao', $user->id, [
            'solicitacao_id' => $solicitacao->id,
            'status'         => $statusNome,
            'perfil_id'      => $dados['perfil_id'] ?? null,
        ], AuditLog::TIPO_UPDATE, 'solicitacoes_cadastro', $solicitacao->id);

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

        if (!CpfHelper::validar($cpf)) {
            return ['disponivel' => false, 'mensagem' => 'CPF inválido. Verifique os dígitos informados.'];
        }

        if (Usuario::where('cpf', $cpf)->exists()) {
            return ['disponivel' => false, 'mensagem' => 'Este CPF já possui cadastro ativo no sistema.'];
        }

        $statusEmAnalise = StatusSolicitacao::idPorNome(StatusSolicitacao::EM_ANALISE);

        $emAnalise = SolicitacaoCadastro::whereHas('usuario', fn ($q) => $q->where('cpf', $cpf))
            ->where('status_id', $statusEmAnalise)
            ->exists();

        if ($emAnalise) {
            return ['disponivel' => false, 'mensagem' => 'Já existe uma solicitação em análise para este CPF.'];
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
            'ativo'                => true,
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

    public function desativarPerfil(Usuario $user, int $solicitacaoId, int $perfilUsuarioId): array
    {
        $solicitacao = $this->buscarSolicitacao($solicitacaoId);
        $usuarioSolicitante = $solicitacao->usuario;
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
            'ativo'             => false,
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

    public function adicionarPerfil(Usuario $user, int $solicitacaoId, array $dados): array
    {
        $solicitacao = $this->buscarSolicitacao($solicitacaoId);
        $statusAprovado = StatusSolicitacao::idPorNome(StatusSolicitacao::APROVADO);

        if ($solicitacao->status_id !== $statusAprovado) {
            throw ValidationException::withMessages([
                'status' => ['Apenas solicitações aprovadas permitem adicionar novos perfis vinculados.'],
            ]);
        }

        $usuarioSolicitante = $solicitacao->usuario;
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
            'ativo'                => true,
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
        $solicitacao = SolicitacaoCadastro::with('usuario')->find($id);
        if (!$solicitacao) {
            throw ApiException::notFound('Solicitação não encontrada.');
        }
        return $solicitacao;
    }

    private function obterPerfisVinculados(SolicitacaoCadastro $solicitacao): array
    {
        $statusAprovado = StatusSolicitacao::idPorNome(StatusSolicitacao::APROVADO);
        if ($solicitacao->status_id !== $statusAprovado) {
            return [];
        }

        $usuario = $solicitacao->usuario;
        if (!$usuario) {
            return [];
        }

        $hoje = now()->toDateString();
        $vinculos = $usuario->perfis()
            ->withPivot(['id', 'data_inicio_vigencia', 'data_fim_vigencia', 'ativo'])
            ->get();

        $perfis = $vinculos->map(function ($perfil, $index) use ($hoje, $solicitacao): array {
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
                'esfera'          => $solicitacao->esfera?->nome ?? '—',
                'uf'              => $solicitacao->ufRelacao?->sigla ?? '—',
                'municipio'       => $solicitacao->municipioRelacao?->nome ?? '—',
                'orgao'           => $solicitacao->orgao ?? '—',
                'cargo'           => $solicitacao->cargo ?? '—',
            ];
        })->all();

        usort($perfis, fn ($a, $b) => ($b['vigente'] ? 1 : 0) - ($a['vigente'] ? 1 : 0));

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

        $nome = Perfil::where('id', $perfilId)->value('nome');
        if (!$nome) {
            throw ApiException::unprocessable('Perfil informado é inválido.');
        }

        $n = mb_strtolower($nome);

        if ($tipo === 'estadual' && !str_contains($n, 'estadual')) {
            throw ApiException::unprocessable(
                'Seu nível de acesso só permite solicitar perfis do tipo estadual.'
            );
        }

        if ($tipo === 'municipal' && !str_contains($n, 'municipal')) {
            throw ApiException::unprocessable(
                'Seu nível de acesso só permite solicitar perfis do tipo municipal.'
            );
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

    private function verificarDuplicidade(int $userId, ?int $esferaId, ?int $ufId, ?int $municipioId): void
    {
        $statusAprovado = StatusSolicitacao::idPorNome(StatusSolicitacao::APROVADO);

        $query = SolicitacaoCadastro::where('user_id', $userId)
            ->where('status_id', $statusAprovado);

        $query->where('esfera_id', $esferaId);

        $esferaNome = $esferaId ? strtolower(Esfera::find($esferaId)?->nome ?? '') : '';

        if ($esferaNome === 'estadual' || $esferaNome === 'municipal') {
            $query->where('uf_id', $ufId);
        }
        if ($esferaNome === 'municipal') {
            $query->where('municipio_id', $municipioId);
        }

        if ($query->exists()) {
            $ufSigla = $ufId ? (Uf::find($ufId)?->sigla ?? '') : '';
            $munNome = $municipioId ? (Municipio::find($municipioId)?->nome ?? '') : '';

            $msg = match ($esferaNome) {
                'federal'   => 'Você já possui um perfil ativo para a esfera Federal.',
                'estadual'  => "Você já possui um perfil ativo para a esfera Estadual na UF {$ufSigla}.",
                'municipal' => "Você já possui um perfil ativo para a esfera Municipal em {$munNome}/{$ufSigla}.",
                default     => 'Você já possui um perfil ativo para a combinação informada.',
            };
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
}
