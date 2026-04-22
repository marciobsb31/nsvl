<?php

namespace App\Http\Controllers;

use App\Enums\TipoAuditoria;
use App\Http\Requests\PlanoAcaoEixoRequest;
use App\Http\Resources\PlanoAcaoEixoResource;
use App\Models\PlanoAcaoEixo;
use App\Services\Audit\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Plano de Ação - Eixos', description: 'Seções 3-6: ações por eixo temático')]
class PlanoAcaoEixoController extends Controller
{
    private const EIXOS_VALIDOS = [1, 2, 3, 4];

    public function __construct(
        private readonly AuditLogService $audit
    ) {}

    #[OA\Get(
        path: '/api/plano-acao/eixo/{eixo}',
        summary: 'Retorna as ações do eixo do plano de ação do usuário autenticado',
        security: [['BearerAuth' => []]],
        tags: ['Plano de Ação - Eixos'],
        responses: [
            new OA\Response(response: 200, description: 'Dados do eixo ou null se não preenchido'),
            new OA\Response(response: 401, description: 'Não autenticado'),
            new OA\Response(response: 422, description: 'Eixo inválido'),
        ]
    )]
    public function show(Request $request, int $eixo): JsonResponse
    {
        if (!in_array($eixo, self::EIXOS_VALIDOS, true)) {
            return response()->json(['message' => 'Eixo inválido.'], 422);
        }

        $usuario = $request->user();

        $registro = PlanoAcaoEixo::query()
            ->where('usuario_id', $usuario->id)
            ->where('eixo_numero', $eixo)
            ->first();

        $this->audit->log(
            "plano_acao.eixo{$eixo}.visualizar",
            $usuario->id,
            ['usuario_id' => $usuario->id, 'eixo_numero' => $eixo],
            TipoAuditoria::VIEW->name,
            'plano_acao_eixos',
            $registro?->id
        );

        if (! $registro) {
            return response()->json(['data' => null]);
        }

        return response()->json([
            'data' => PlanoAcaoEixoResource::make($registro),
        ]);
    }

    #[OA\Post(
        path: '/api/plano-acao/eixo/{eixo}',
        summary: 'Cria ou atualiza as ações do eixo do plano de ação',
        security: [['BearerAuth' => []]],
        tags: ['Plano de Ação - Eixos'],
        responses: [
            new OA\Response(response: 200, description: 'Rascunho salvo com sucesso'),
            new OA\Response(response: 422, description: 'Dados inválidos'),
            new OA\Response(response: 401, description: 'Não autenticado'),
        ]
    )]
    public function store(PlanoAcaoEixoRequest $request, int $eixo): JsonResponse
    {
        if (!in_array($eixo, self::EIXOS_VALIDOS, true)) {
            return response()->json(['message' => 'Eixo inválido.'], 422);
        }

        $usuario = $request->user();
        $dados = $request->validated();

        $registro = PlanoAcaoEixo::updateOrCreate(
            ['usuario_id' => $usuario->id, 'eixo_numero' => $eixo],
            ['acoes' => $dados['acoes'] ?? []]
        );

        $tipoOperacao = $registro->wasRecentlyCreated
            ? TipoAuditoria::INSERT->name
            : TipoAuditoria::UPDATE->name;

        $this->audit->log(
            "plano_acao.eixo{$eixo}.salvar",
            $usuario->id,
            ['usuario_id' => $usuario->id, 'eixo_numero' => $eixo, 'total_acoes' => count($dados['acoes'] ?? [])],
            $tipoOperacao,
            'plano_acao_eixos',
            $registro->id
        );

        return response()->json([
            'message' => 'Rascunho salvo com sucesso.',
            'data'    => PlanoAcaoEixoResource::make($registro),
        ]);
    }
}
