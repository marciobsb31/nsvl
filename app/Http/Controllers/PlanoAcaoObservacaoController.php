<?php

namespace App\Http\Controllers;

use App\Enums\TipoAuditoria;
use App\Http\Requests\PlanoAcaoObservacaoRequest;
use App\Http\Resources\PlanoAcaoObservacaoResource;
use App\Models\PlanoAcaoObservacao;
use App\Services\Audit\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Plano de Ação - Observações', description: 'Seção 7: observações complementares')]
class PlanoAcaoObservacaoController extends Controller
{
    public function __construct(
        private readonly AuditLogService $audit
    ) {}

    #[OA\Get(
        path: '/api/plano-acao/observacoes',
        summary: 'Retorna as observações complementares do plano de ação',
        security: [['BearerAuth' => []]],
        tags: ['Plano de Ação - Observações'],
        responses: [
            new OA\Response(response: 200, description: 'Observações ou null'),
            new OA\Response(response: 401, description: 'Não autenticado'),
        ]
    )]
    public function show(Request $request): JsonResponse
    {
        $usuario = $request->user();

        $registro = PlanoAcaoObservacao::query()
            ->where('usuario_id', $usuario->id)
            ->first();

        $this->audit->log(
            'plano_acao.observacoes.visualizar',
            $usuario->id,
            ['usuario_id' => $usuario->id],
            TipoAuditoria::VIEW->name,
            'plano_acao_observacoes',
            $registro?->id
        );

        if (! $registro) {
            return response()->json(['data' => null]);
        }

        return response()->json([
            'data' => PlanoAcaoObservacaoResource::make($registro),
        ]);
    }

    #[OA\Post(
        path: '/api/plano-acao/observacoes',
        summary: 'Salva ou atualiza as observações complementares',
        security: [['BearerAuth' => []]],
        tags: ['Plano de Ação - Observações'],
        responses: [
            new OA\Response(response: 200, description: 'Rascunho salvo com sucesso'),
            new OA\Response(response: 422, description: 'Dados inválidos'),
            new OA\Response(response: 401, description: 'Não autenticado'),
        ]
    )]
    public function store(PlanoAcaoObservacaoRequest $request): JsonResponse
    {
        $usuario = $request->user();
        $dados = $request->validated();
        $dados['usuario_id'] = $usuario->id;

        $registro = PlanoAcaoObservacao::updateOrCreate(
            ['usuario_id' => $usuario->id],
            $dados
        );

        $tipoOperacao = $registro->wasRecentlyCreated
            ? TipoAuditoria::INSERT->name
            : TipoAuditoria::UPDATE->name;

        $this->audit->log(
            'plano_acao.observacoes.salvar',
            $usuario->id,
            $dados,
            $tipoOperacao,
            'plano_acao_observacoes',
            $registro->id
        );

        return response()->json([
            'data'    => PlanoAcaoObservacaoResource::make($registro),
            'message' => 'Rascunho salvo com sucesso.',
        ]);
    }
}
