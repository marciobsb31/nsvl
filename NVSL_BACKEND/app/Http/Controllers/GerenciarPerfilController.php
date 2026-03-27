<?php

namespace App\Http\Controllers;

use App\Exceptions\ApiException;
use App\Http\Requests\CadastrarPerfilRequest;
use App\Models\AuditLog;
use App\Models\Perfil;
use App\Models\Usuario;
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

        $query = Perfil::query()->whereIn('nome', Perfil::CATALOGO_OFICIAL);

        if ($request->filled('nome')) {
            $query->where('nome', 'ilike', '%' . $request->input('nome') . '%');
        }
        if ($request->filled('status')) {
            $ativo = $request->input('status') === 'ativo';
            $query->where('ativo', $ativo);
        }

        $perfis = $query->get()
            ->sortBy(fn (Perfil $p) => Perfil::indiceNoCatalogo($p->nome))
            ->values();

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

        $perfil = Perfil::find($id);
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

        $perfil = DB::transaction(function () use ($request, $user) {
            $perfil = Perfil::create([
                'nome'      => $request->input('nome'),
                'descricao' => $request->input('descricao'),
                'ativo'     => $request->input('ativo', true),
            ]);

            $this->audit->log(
                'gerenciar_perfis.cadastrar',
                $user->id,
                [
                    'perfil_nome' => $perfil->nome,
                    'ativo'       => $perfil->ativo,
                ],
                AuditLog::TIPO_INSERT,
                'perfis',
                $perfil->id
            );

            return $perfil;
        });

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
        $perfil = Perfil::find($id);
        if (!$perfil) {
            throw ApiException::notFound('Perfil não encontrado.');
        }

        $validated = $request->validate([
            'nome'      => ['required', 'string', 'max:100', Rule::in(Perfil::CATALOGO_OFICIAL), Rule::unique('perfis', 'nome')->ignore($perfil->id)],
            'descricao' => ['nullable', 'string', 'max:255'],
            'ativo'     => ['required', 'boolean'],
        ], [
            'nome.required' => 'Preencha os campos obrigatórios.',
            'nome.in'       => 'O nome deve ser um dos perfis oficiais do sistema.',
            'nome.unique'   => 'Já existe um perfil com este nome.',
        ]);

        $anterior = [
            'nome'  => $perfil->nome,
            'ativo' => $perfil->ativo,
        ];

        DB::transaction(function () use ($perfil, $validated, $user, $anterior) {
            $perfil->update([
                'nome'      => $validated['nome'],
                'descricao' => $validated['descricao'] ?? null,
                'ativo'     => $validated['ativo'],
            ]);

            $alteracoes = $this->descreverAlteracoes($anterior, $perfil);

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
                'usuario'     => $log->user?->nome ?? 'Sistema',
                'perfil'      => $perfil->nome,
                'atualizacao' => $this->descreverAcaoLog($log),
                'action'      => $log->acao,
            ]),
        ]);
    }

    public function hierarquia(): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            throw ApiException::unauthenticated();
        }

        $esferaUsuario = $user->esfera_atuacao ?? 'federal';

        return response()->json([
            'esfera_usuario'     => $esferaUsuario,
            'esferas_permitidas' => $this->esferasPermitidas($esferaUsuario),
        ]);
    }

    private function formatarPerfil(Perfil $p): array
    {
        return [
            'id'         => $p->id,
            'nome'       => $p->nome,
            'descricao'  => $p->descricao,
            'ativo'      => $p->ativo,
            'status'     => $p->ativo ? 'ativo' : 'inativo',
            'created_at' => $p->created_at?->toIso8601String(),
        ];
    }

    private function descreverAlteracoes(array $anterior, Perfil $perfil): string
    {
        $partes = [];

        if ($anterior['nome'] !== $perfil->nome) {
            $partes[] = "Alterando o Nome de Perfil para {$perfil->nome}";
        }
        if ($anterior['ativo'] !== $perfil->ativo) {
            $partes[] = "Alterando Situação para " . ($perfil->ativo ? 'Vigente' : 'Não Vigente');
        }

        return implode('; ', $partes) ?: 'Atualização de dados do perfil';
    }

    private function descreverAcaoLog(AuditLog $log): string
    {
        $contexto = $log->contexto ?? [];

        if (!empty($contexto['alteracoes'])) {
            return $contexto['alteracoes'];
        }

        $map = [
            'gerenciar_perfis.cadastrar' => 'Perfil cadastrado',
            'gerenciar_perfis.editar'    => 'Perfil atualizado',
        ];

        return $map[$log->acao] ?? $log->acao;
    }

    private function esferasPermitidas(string $esferaUsuario): array
    {
        return match (strtolower($esferaUsuario)) {
            'federal'   => ['federal', 'estadual', 'municipal'],
            'estadual'  => ['estadual'],
            'municipal' => ['municipal'],
            default     => [],
        };
    }
}
