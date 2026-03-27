<?php

namespace App\Http\Controllers;

use App\Exceptions\ApiException;
use App\Http\Requests\AdicionarPerfilVinculadoRequest;
use App\Http\Requests\AvaliarSolicitacaoRequest;
use App\Http\Requests\ListarSolicitacoesRequest;
use App\Http\Requests\SolicitacaoCadastroRequest;
use App\Models\SolicitacaoCadastro;
use App\Models\Usuario;
use App\Services\SolicitacaoCadastro\SolicitacaoCadastroService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class SolicitacaoCadastroController extends Controller
{
    public function __construct(
        private readonly SolicitacaoCadastroService $service
    ) {}

    public function index(ListarSolicitacoesRequest $request): JsonResponse
    {
        $user = $this->usuarioAutenticado();
        $filtros = $request->validated();

        return response()->json(['data' => $this->service->listar($user, $filtros)]);
    }

    public function show(int $id): JsonResponse
    {
        $user = $this->usuarioAutenticado();
        $solicitacao = SolicitacaoCadastro::find($id);
        if (!$solicitacao) {
            throw ApiException::notFound('Solicitação não encontrada.');
        }

        Gate::authorize('view', $solicitacao);

        return response()->json($this->service->detalhar($user, $id));
    }

    public function store(SolicitacaoCadastroRequest $request): JsonResponse
    {
        $user = Auth::guard('sanctum')->user();
        $result = $this->service->criar($user, $request->all());

        return response()->json($result, 201);
    }

    public function update(AvaliarSolicitacaoRequest $request, int $id): JsonResponse
    {
        $user = $this->usuarioAutenticado();
        $solicitacao = SolicitacaoCadastro::find($id);
        if (!$solicitacao) {
            throw ApiException::notFound('Solicitação não encontrada.');
        }

        Gate::authorize('update', $solicitacao);

        return response()->json($this->service->avaliar($user, $id, $request->validated()));
    }

    public function verificarCpf(Request $request): JsonResponse
    {
        return response()->json(
            $this->service->verificarCpf((string) $request->query('cpf', ''))
        );
    }

    public function ativarPerfilVinculado(int $id, int $perfilUsuarioId): JsonResponse
    {
        $user = $this->usuarioAutenticado();
        $solicitacao = SolicitacaoCadastro::find($id);
        if (!$solicitacao) {
            throw ApiException::notFound('Solicitação não encontrada.');
        }

        Gate::authorize('update', $solicitacao);

        return response()->json($this->service->ativarPerfil($user, $id, $perfilUsuarioId));
    }

    public function desativarPerfilVinculado(int $id, int $perfilUsuarioId): JsonResponse
    {
        $user = $this->usuarioAutenticado();
        $solicitacao = SolicitacaoCadastro::find($id);
        if (!$solicitacao) {
            throw ApiException::notFound('Solicitação não encontrada.');
        }

        Gate::authorize('update', $solicitacao);

        return response()->json($this->service->desativarPerfil($user, $id, $perfilUsuarioId));
    }

    public function adicionarPerfilVinculado(AdicionarPerfilVinculadoRequest $request, int $id): JsonResponse
    {
        $user = $this->usuarioAutenticado();
        $solicitacao = SolicitacaoCadastro::find($id);
        if (!$solicitacao) {
            throw ApiException::notFound('Solicitação não encontrada.');
        }

        Gate::authorize('update', $solicitacao);

        return response()->json(
            $this->service->adicionarPerfil($user, $id, $request->validated()),
            201
        );
    }

    private function usuarioAutenticado(): Usuario
    {
        $user = Auth::user();
        if (!$user instanceof Usuario) {
            throw ApiException::unauthenticated();
        }
        return $user;
    }
}
