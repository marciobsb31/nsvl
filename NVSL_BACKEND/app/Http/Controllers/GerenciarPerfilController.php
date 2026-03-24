<?php

namespace App\Http\Controllers;

use App\Exceptions\ApiException;
use App\Http\Requests\CadastrarPerfilRequest;
use App\Models\AuditLog;
use App\Models\Perfil;
use App\Models\Permissao;
use App\Services\Audit\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class GerenciarPerfilController extends Controller
{
    public function __construct(
        private readonly AuditLogService $audit
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            throw ApiException::unauthenticated();
        }
        $this->verificarPermissaoGerenciar($user);

        $query = Perfil::with('permissoes');

        if ($request->filled('nome')) {
            $query->where('nome', 'ilike', '%' . $request->input('nome') . '%');
        }
        if ($request->filled('esfera')) {
            $query->where('esfera', $request->input('esfera'));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $perfis = $query->orderBy('nome')->get();

        $this->audit->log(
            'gerenciar_perfis.listagem',
            $user->id,
            ['total' => $perfis->count()],
            AuditLog::TIPO_VIEW,
            'perfis'
        );

        return response()->json([
            'data' => $perfis->map(fn (Perfil $p) => $this->formatarPerfil($p)),
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            throw ApiException::unauthenticated();
        }
        $this->verificarPermissaoGerenciar($user);

        $perfil = Perfil::with('permissoes')->find($id);
        if (!$perfil) {
            throw ApiException::notFound('Perfil não encontrado.');
        }

        return response()->json(['data' => $this->formatarPerfil($perfil)]);
    }

    public function store(CadastrarPerfilRequest $request): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            throw ApiException::unauthenticated();
        }
        $this->verificarPermissaoCadastrar($user);
        $this->validarHierarquiaEsfera($user, $request->input('esfera'));

        $perfil = DB::transaction(function () use ($request, $user) {
            $perfil = Perfil::create([
                'nome'      => $request->input('nome'),
                'descricao' => $request->input('descricao'),
                'esfera'    => $request->input('esfera'),
                'status'    => $request->input('status', 'ativo'),
            ]);

            $permissoes = $request->input('permissoes', []);
            if (!empty($permissoes)) {
                $perfil->permissoes()->sync($permissoes);
            }

            $this->audit->log(
                'gerenciar_perfis.cadastrar',
                $user->id,
                [
                    'perfil_nome' => $perfil->nome,
                    'esfera'      => $perfil->esfera,
                    'status'      => $perfil->status,
                    'permissoes'  => count($permissoes),
                ],
                AuditLog::TIPO_INSERT,
                'perfis',
                $perfil->id
            );

            return $perfil;
        });

        $perfil->load('permissoes');

        return response()->json([
            'message' => 'Perfil cadastrado com sucesso.',
            'data'    => $this->formatarPerfil($perfil),
        ], 201);
    }

    public function update(int $id, Request $request): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            throw ApiException::unauthenticated();
        }
        $this->verificarPermissaoCadastrar($user);

        $perfil = Perfil::with('permissoes')->find($id);
        if (!$perfil) {
            throw ApiException::notFound('Perfil não encontrado.');
        }

        $this->validarPermissaoEditarPerfil($user, $perfil);

        $validated = $request->validate([
            'nome'          => ['required', 'string', 'max:100', Rule::unique('perfis', 'nome')->ignore($perfil->id)],
            'descricao'     => ['nullable', 'string', 'max:255'],
            'esfera'        => ['required', Rule::in(['federal', 'estadual', 'municipal'])],
            'status'        => ['required', Rule::in(['ativo', 'inativo'])],
            'permissoes'    => ['nullable', 'array'],
            'permissoes.*'  => ['integer', 'exists:permissoes,id'],
        ], [
            'nome.required' => 'Preencha os campos obrigatórios.',
            'nome.unique'   => 'Já existe um perfil com este nome.',
        ]);

        $this->validarHierarquiaEsfera($user, $validated['esfera']);

        $anterior = [
            'nome'       => $perfil->nome,
            'esfera'     => $perfil->esfera,
            'status'     => $perfil->status,
            'permissoes' => $perfil->permissoes->pluck('acao', 'modulo')->toArray(),
        ];

        DB::transaction(function () use ($perfil, $validated, $user, $anterior) {
            $perfil->update([
                'nome'      => $validated['nome'],
                'descricao' => $validated['descricao'] ?? null,
                'esfera'    => $validated['esfera'],
                'status'    => $validated['status'],
            ]);

            $permissoes = $validated['permissoes'] ?? [];
            $perfil->permissoes()->sync($permissoes);

            $alteracoes = $this->descreverAlteracoes($anterior, $perfil, $permissoes);

            $this->audit->log(
                'gerenciar_perfis.editar',
                $user->id,
                [
                    'perfil_nome'  => $perfil->nome,
                    'alteracoes'   => $alteracoes,
                ],
                AuditLog::TIPO_UPDATE,
                'perfis',
                $perfil->id
            );
        });

        $perfil->load('permissoes');

        return response()->json([
            'message' => 'Perfil atualizado com sucesso.',
            'data'    => $this->formatarPerfil($perfil),
        ]);
    }

    public function historico(int $id): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            throw ApiException::unauthenticated();
        }
        $this->verificarPermissaoGerenciar($user);

        $perfil = Perfil::find($id);
        if (!$perfil) {
            throw ApiException::notFound('Perfil não encontrado.');
        }

        $logs = AuditLog::with('user')
            ->where('tabela_afetada', 'perfis')
            ->where('registro_id', $id)
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'data' => $logs->map(fn (AuditLog $log) => [
                'id'          => $log->id,
                'data_hora'   => $log->created_at?->format('d/m/Y H:i:s'),
                'usuario'     => $log->user?->name ?? 'Sistema',
                'perfil'      => $perfil->nome,
                'atualizacao' => $this->descreverAcaoLog($log),
                'action'      => $log->action,
            ]),
        ]);
    }

    public function permissoes(): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            throw ApiException::unauthenticated();
        }

        $permissoes = Permissao::orderBy('modulo')->orderBy('acao')->get();

        return response()->json([
            'data' => $permissoes->map(fn (Permissao $p) => [
                'id'             => $p->id,
                'funcionalidade' => $p->modulo,
                'nome_acao'      => $p->acao,
                'descricao'      => $p->descricao,
            ]),
        ]);
    }

    private function formatarPerfil(Perfil $p): array
    {
        return [
            'id'         => $p->id,
            'nome'       => $p->nome,
            'descricao'  => $p->descricao,
            'esfera'     => $p->esfera,
            'status'     => $p->status,
            'permissoes' => $p->permissoes->map(fn (Permissao $perm) => [
                'id'             => $perm->id,
                'funcionalidade' => $perm->modulo,
                'nome_acao'      => $perm->acao,
            ]),
            'created_at' => $p->created_at?->toIso8601String(),
        ];
    }

    private function descreverAlteracoes(array $anterior, Perfil $perfil, array $permissoesIds): string
    {
        $partes = [];

        if ($anterior['nome'] !== $perfil->nome) {
            $partes[] = "Alterando o Nome de Perfil para {$perfil->nome}";
        }
        if ($anterior['esfera'] !== $perfil->esfera) {
            $partes[] = "Alterando o Tipo de Perfil para " . ucfirst($perfil->esfera);
        }
        if ($anterior['status'] !== $perfil->status) {
            $partes[] = "Alterando Situação para " . ($perfil->status === 'ativo' ? 'Vigente' : 'Não Vigente');
        }

        if (!empty($permissoesIds)) {
            $nomes = Permissao::whereIn('id', $permissoesIds)
                ->get()
                ->map(fn ($p) => "{$p->modulo}: {$p->acao}")
                ->implode(', ');
            $partes[] = "Atualização de permissões ({$nomes})";
        }

        return implode('; ', $partes) ?: 'Atualização de dados do perfil';
    }

    private function descreverAcaoLog(AuditLog $log): string
    {
        $context = $log->context ?? [];

        if (!empty($context['alteracoes'])) {
            return $context['alteracoes'];
        }

        $map = [
            'gerenciar_perfis.cadastrar' => 'Perfil cadastrado',
            'gerenciar_perfis.editar'    => 'Perfil atualizado',
        ];

        return $map[$log->action] ?? $log->action;
    }

    private function verificarPermissaoGerenciar($user): void
    {
        $esfera = $user->esfera_atuacao ?? 'federal';
        if (!in_array($esfera, ['federal', 'estadual', 'municipal'], true)) {
            throw ApiException::forbidden('Acesso não permitido.');
        }
    }

    private function verificarPermissaoCadastrar($user): void
    {
        $esfera = $user->esfera_atuacao ?? 'federal';
        if (!in_array($esfera, ['federal', 'estadual', 'municipal'], true)) {
            throw ApiException::forbidden('Acesso não permitido.');
        }
    }

    private function validarHierarquiaEsfera($user, string $esferaPerfil): void
    {
        $esferaUsuario = $user->esfera_atuacao ?? 'federal';
        if ($esferaUsuario === 'federal') {
            return;
        }
        if ($esferaUsuario === 'estadual' && $esferaPerfil !== 'estadual') {
            throw ApiException::forbidden('Usuários estaduais podem cadastrar apenas perfis do tipo estadual.');
        }
        if ($esferaUsuario === 'municipal' && $esferaPerfil !== 'municipal') {
            throw ApiException::forbidden('Usuários municipais podem cadastrar apenas perfis do tipo municipal.');
        }
    }

    private function validarPermissaoEditarPerfil($user, Perfil $perfil): void
    {
        $esferaUsuario = $user->esfera_atuacao ?? 'federal';

        if ($esferaUsuario === 'federal') {
            return;
        }

        if ($esferaUsuario === 'estadual' && $perfil->esfera !== 'estadual') {
            throw ApiException::forbidden('Acesso não permitido.');
        }

        if ($esferaUsuario === 'municipal' && $perfil->esfera !== 'municipal') {
            throw ApiException::forbidden('Acesso não permitido.');
        }
    }
}
