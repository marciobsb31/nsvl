<?php

namespace App\Http\Controllers;

use App\Enums\TipoAuditoria;
use App\Http\Requests\PlanoAcaoDiagnosticoRequest;
use App\Http\Resources\PlanoAcaoDiagnosticoResource;
use App\Models\PlanoAcaoDiagnostico;
use App\Services\Audit\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Plano de Ação - Diagnóstico', description: 'Seção 2: diagnóstico de barreiras')]
class PlanoAcaoDiagnosticoController extends Controller
{
    public function __construct(
        private readonly AuditLogService $audit
    ) {}

    #[OA\Get(
        path: '/api/plano-acao/diagnostico',
        summary: 'Retorna o diagnóstico do plano de ação do usuário autenticado',
        security: [['BearerAuth' => []]],
        tags: ['Plano de Ação - Diagnóstico'],
        responses: [
            new OA\Response(response: 200, description: 'Dados do diagnóstico ou null se não preenchido'),
            new OA\Response(response: 401, description: 'Não autenticado'),
        ]
    )]
    public function show(Request $request): JsonResponse
    {
        $usuario = $request->user();

        $diagnostico = PlanoAcaoDiagnostico::query()
            ->where('usuario_id', $usuario->id)
            ->first();

        $this->audit->log(
            'plano_acao.diagnostico.visualizar',
            $usuario->id,
            ['usuario_id' => $usuario->id],
            TipoAuditoria::VIEW->name,
            'plano_acao_diagnosticos',
            $diagnostico?->id
        );

        if (! $diagnostico) {
            return response()->json(['data' => null]);
        }

        return response()->json([
            'data' => PlanoAcaoDiagnosticoResource::make($diagnostico),
        ]);
    }

    #[OA\Post(
        path: '/api/plano-acao/diagnostico',
        summary: 'Cria ou atualiza o diagnóstico do plano de ação',
        security: [['BearerAuth' => []]],
        tags: ['Plano de Ação - Diagnóstico'],
        responses: [
            new OA\Response(response: 200, description: 'Rascunho salvo com sucesso'),
            new OA\Response(response: 422, description: 'Dados inválidos'),
            new OA\Response(response: 401, description: 'Não autenticado'),
        ]
    )]
    public function store(PlanoAcaoDiagnosticoRequest $request): JsonResponse
    {
        $usuario = $request->user();
        $dados = $request->validated();

        $diagnostico = PlanoAcaoDiagnostico::updateOrCreate(
            ['usuario_id' => $usuario->id],
            $dados
        );

        $tipoOperacao = $diagnostico->wasRecentlyCreated
            ? TipoAuditoria::INSERT->name
            : TipoAuditoria::UPDATE->name;

        $this->audit->log(
            'plano_acao.diagnostico.salvar',
            $usuario->id,
            ['usuario_id' => $usuario->id],
            $tipoOperacao,
            'plano_acao_diagnosticos',
            $diagnostico->id
        );

        return response()->json([
            'message' => 'Rascunho salvo com sucesso.',
            'data'    => PlanoAcaoDiagnosticoResource::make($diagnostico),
        ]);
    }
}
