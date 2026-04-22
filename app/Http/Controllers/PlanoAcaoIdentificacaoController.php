<?php

namespace App\Http\Controllers;

use App\Enums\TipoAuditoria;
use App\Http\Requests\PlanoAcaoIdentificacaoRequest;
use App\Http\Resources\PlanoAcaoIdentificacaoResource;
use App\Models\PlanoAcaoIdentificacao;
use App\Services\Audit\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Plano de Ação - Identificação', description: 'Seção 1: identificação geral do plano de ação')]
class PlanoAcaoIdentificacaoController extends Controller
{
    public function __construct(
        private readonly AuditLogService $audit
    ) {}

    #[OA\Get(
        path: '/api/plano-acao/identificacao',
        summary: 'Retorna a identificação do plano de ação do usuário autenticado',
        security: [['BearerAuth' => []]],
        tags: ['Plano de Ação - Identificação'],
        responses: [
            new OA\Response(response: 200, description: 'Dados da identificação ou null se não preenchida'),
            new OA\Response(response: 401, description: 'Não autenticado'),
        ]
    )]
    public function show(Request $request): JsonResponse
    {
        $usuario = $request->user();

        $identificacao = PlanoAcaoIdentificacao::query()
            ->where('usuario_id', $usuario->id)
            ->first();

        $this->audit->log(
            'plano_acao.identificacao.visualizar',
            $usuario->id,
            ['usuario_id' => $usuario->id],
            TipoAuditoria::VIEW->name,
            'plano_acao_identificacoes',
            $identificacao?->id
        );

        if (! $identificacao) {
            return response()->json(['data' => null]);
        }

        return response()->json([
            'data' => PlanoAcaoIdentificacaoResource::make($identificacao),
        ]);
    }

    #[OA\Post(
        path: '/api/plano-acao/identificacao',
        summary: 'Cria ou atualiza a identificação do plano de ação',
        security: [['BearerAuth' => []]],
        tags: ['Plano de Ação - Identificação'],
        responses: [
            new OA\Response(response: 200, description: 'Rascunho salvo com sucesso'),
            new OA\Response(response: 422, description: 'Dados inválidos'),
            new OA\Response(response: 401, description: 'Não autenticado'),
        ]
    )]
    public function store(PlanoAcaoIdentificacaoRequest $request): JsonResponse
    {
        $usuario = $request->user();
        $dados = $request->validated();

        $identificacao = PlanoAcaoIdentificacao::updateOrCreate(
            ['usuario_id' => $usuario->id],
            $dados
        );

        $tipoOperacao = $identificacao->wasRecentlyCreated
            ? TipoAuditoria::INSERT->name
            : TipoAuditoria::UPDATE->name;

        $this->audit->log(
            'plano_acao.identificacao.salvar',
            $usuario->id,
            ['usuario_id' => $usuario->id],
            $tipoOperacao,
            'plano_acao_identificacoes',
            $identificacao->id
        );

        return response()->json([
            'message' => 'Rascunho salvo com sucesso.',
            'data'    => PlanoAcaoIdentificacaoResource::make($identificacao),
        ]);
    }
}
