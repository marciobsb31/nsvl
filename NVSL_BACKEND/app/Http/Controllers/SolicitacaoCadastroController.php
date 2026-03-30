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
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Solicitações de cadastro', description: 'Fluxo público e gestão autenticada')]
class SolicitacaoCadastroController extends Controller
{
    public function __construct(
        private readonly SolicitacaoCadastroService $service
    ) {}

    #[OA\Get(
        path: '/api/solicitacoes-cadastro',
        summary: 'Lista solicitações (filtros conforme política do usuário)',
        tags: ['Solicitações de cadastro'],
        security: [['BearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'cpf', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'nome', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'uf', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'municipio', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'orgao', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'esfera', in: 'query', schema: new OA\Schema(type: 'string', enum: ['federal', 'estadual', 'municipal'])),
            new OA\Parameter(name: 'status', in: 'query', schema: new OA\Schema(type: 'string', enum: ['em_analise', 'aprovado', 'reprovado'])),
        ],
        responses: [
            new OA\Response(response: 200, description: 'data[] — itens resumidos'),
            new OA\Response(response: 401, description: 'Não autenticado'),
        ]
    )]
    public function index(ListarSolicitacoesRequest $request): JsonResponse
    {
        $user = $this->usuarioAutenticado();
        $filtros = $request->validated();

        return response()->json(['data' => $this->service->listar($user, $filtros)]);
    }

    #[OA\Get(
        path: '/api/solicitacoes-cadastro/{id}',
        summary: 'Detalha solicitação',
        tags: ['Solicitações de cadastro'],
        security: [['BearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Payload de detalhamento'),
            new OA\Response(response: 401, description: 'Não autenticado'),
            new OA\Response(response: 403, description: 'Sem permissão'),
            new OA\Response(response: 404, description: 'Não encontrado'),
        ]
    )]
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

    #[OA\Post(
        path: '/api/solicitacoes-cadastro',
        summary: 'Cria solicitação (público ou autenticado)',
        tags: ['Solicitações de cadastro'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: 'object',
                description: 'Campos: nome, CPF, emailInstitucional, telefoneInstitucional, esferaAtuacao, uf, municipio, orgao, cargo; perfilId e vigências conforme autenticação. A ciência do termo é implícita no envio.'
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Criado'),
            new OA\Response(response: 422, description: 'Validação'),
        ]
    )]
    public function store(SolicitacaoCadastroRequest $request): JsonResponse
    {
        $user = Auth::guard('sanctum')->user();
        $result = $this->service->criar($user, $request->all());

        return response()->json($result, 201);
    }

    #[OA\Patch(
        path: '/api/solicitacoes-cadastro/{id}',
        summary: 'Avalia ou atualiza solicitação',
        tags: ['Solicitações de cadastro'],
        security: [['BearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                type: 'object',
                description: 'Ver AvaliarSolicitacaoRequest'
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Atualizado'),
            new OA\Response(response: 401, description: 'Não autenticado'),
            new OA\Response(response: 403, description: 'Sem permissão'),
            new OA\Response(response: 404, description: 'Não encontrado'),
            new OA\Response(response: 422, description: 'Validação'),
        ]
    )]
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

    #[OA\Get(
        path: '/api/solicitacoes-cadastro/verificar-cpf',
        summary: 'Verifica CPF em solicitações existentes',
        tags: ['Solicitações de cadastro'],
        parameters: [
            new OA\Parameter(name: 'cpf', in: 'query', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Resultado da verificação'),
            new OA\Response(response: 429, description: 'Limite de requisições'),
        ]
    )]
    public function verificarCpf(Request $request): JsonResponse
    {
        return response()->json(
            $this->service->verificarCpf((string) $request->query('cpf', ''))
        );
    }

    #[OA\Patch(
        path: '/api/solicitacoes-cadastro/{id}/perfis/{perfilUsuarioId}/ativar',
        summary: 'Ativa perfil vinculado à solicitação',
        tags: ['Solicitações de cadastro'],
        security: [['BearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'perfilUsuarioId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'OK'),
            new OA\Response(response: 401, description: 'Não autenticado'),
            new OA\Response(response: 403, description: 'Sem permissão'),
            new OA\Response(response: 404, description: 'Não encontrado'),
        ]
    )]
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

    #[OA\Patch(
        path: '/api/solicitacoes-cadastro/{id}/perfis/{perfilUsuarioId}/desativar',
        summary: 'Desativa perfil vinculado',
        tags: ['Solicitações de cadastro'],
        security: [['BearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'perfilUsuarioId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'OK'),
            new OA\Response(response: 401, description: 'Não autenticado'),
            new OA\Response(response: 403, description: 'Sem permissão'),
            new OA\Response(response: 404, description: 'Não encontrado'),
        ]
    )]
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

    #[OA\Post(
        path: '/api/solicitacoes-cadastro/{id}/perfis',
        summary: 'Adiciona perfil vinculado à solicitação aprovada',
        tags: ['Solicitações de cadastro'],
        security: [['BearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                type: 'object',
                description: 'Ver AdicionarPerfilVinculadoRequest'
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Criado'),
            new OA\Response(response: 401, description: 'Não autenticado'),
            new OA\Response(response: 403, description: 'Sem permissão'),
            new OA\Response(response: 404, description: 'Não encontrado'),
            new OA\Response(response: 422, description: 'Validação'),
        ]
    )]
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
