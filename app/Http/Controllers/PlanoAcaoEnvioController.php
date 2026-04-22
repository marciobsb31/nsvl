<?php

namespace App\Http\Controllers;

use App\Enums\TipoAuditoria;
use App\Http\Requests\PlanoAcaoEnvioRequest;
use App\Http\Resources\PlanoAcaoEnvioResource;
use App\Models\PlanoAcaoAnexo;
use App\Models\PlanoAcaoEixo;
use App\Models\PlanoAcaoEnvio;
use App\Models\PlanoAcaoIdentificacao;
use App\Models\PlanoAcaoDiagnostico;
use App\Services\Audit\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Plano de Ação - Envio', description: 'Seção 9: responsável técnico e envio do plano')]
class PlanoAcaoEnvioController extends Controller
{
    public function __construct(
        private readonly AuditLogService $audit
    ) {}

    #[OA\Get(
        path: '/api/plano-acao/envio',
        summary: 'Retorna os dados do envio (responsável técnico) do plano de ação',
        security: [['BearerAuth' => []]],
        tags: ['Plano de Ação - Envio'],
        responses: [
            new OA\Response(response: 200, description: 'Dados do envio ou null'),
            new OA\Response(response: 401, description: 'Não autenticado'),
        ]
    )]
    public function show(Request $request): JsonResponse
    {
        $usuario = $request->user();

        $registro = PlanoAcaoEnvio::query()
            ->where('usuario_id', $usuario->id)
            ->first();

        $this->audit->log(
            'plano_acao.envio.visualizar',
            $usuario->id,
            ['usuario_id' => $usuario->id],
            TipoAuditoria::VIEW->name,
            'plano_acao_envios',
            $registro?->id
        );

        if (! $registro) {
            return response()->json(['data' => null]);
        }

        return response()->json([
            'data' => PlanoAcaoEnvioResource::make($registro),
        ]);
    }

    #[OA\Post(
        path: '/api/plano-acao/envio',
        summary: 'Salva ou atualiza o rascunho dos dados de envio',
        security: [['BearerAuth' => []]],
        tags: ['Plano de Ação - Envio'],
        responses: [
            new OA\Response(response: 200, description: 'Rascunho salvo com sucesso'),
            new OA\Response(response: 422, description: 'Dados inválidos'),
            new OA\Response(response: 401, description: 'Não autenticado'),
        ]
    )]
    public function store(PlanoAcaoEnvioRequest $request): JsonResponse
    {
        $usuario = $request->user();
        $dados = $request->validated();
        $dados['usuario_id'] = $usuario->id;

        $registro = PlanoAcaoEnvio::updateOrCreate(
            ['usuario_id' => $usuario->id],
            $dados
        );

        $tipoOperacao = $registro->wasRecentlyCreated
            ? TipoAuditoria::INSERT->name
            : TipoAuditoria::UPDATE->name;

        $this->audit->log(
            'plano_acao.envio.salvar',
            $usuario->id,
            $dados,
            $tipoOperacao,
            'plano_acao_envios',
            $registro->id
        );

        return response()->json([
            'data'    => PlanoAcaoEnvioResource::make($registro->fresh()),
            'message' => 'Rascunho salvo com sucesso.',
        ]);
    }

    #[OA\Post(
        path: '/api/plano-acao/envio/enviar',
        summary: 'Envia o plano de ação para análise',
        security: [['BearerAuth' => []]],
        tags: ['Plano de Ação - Envio'],
        responses: [
            new OA\Response(response: 200, description: 'Plano enviado com sucesso'),
            new OA\Response(response: 422, description: 'Validação falhou'),
            new OA\Response(response: 401, description: 'Não autenticado'),
        ]
    )]
    public function submit(Request $request): JsonResponse
    {
        $usuario = $request->user();

        // Verificar identificação
        $identificacao = PlanoAcaoIdentificacao::query()
            ->where('usuario_id', $usuario->id)
            ->first();

        if (! $identificacao || ! filled($identificacao->orgao_gestor) || ! $identificacao->vigencia_inicio || ! $identificacao->vigencia_fim) {
            return response()->json(['message' => 'A Seção 1 (Identificação do Plano) deve estar completa antes do envio.'], 422);
        }

        // Verificar diagnóstico
        $diagnostico = PlanoAcaoDiagnostico::query()
            ->where('usuario_id', $usuario->id)
            ->first();

        if (! $diagnostico || ! filled($diagnostico->caracterizacao_populacao)) {
            return response()->json(['message' => 'A Seção 2 (Diagnóstico) deve estar completa antes do envio.'], 422);
        }

        // Verificar que ao menos um eixo tem ações
        $eixos = PlanoAcaoEixo::query()
            ->where('usuario_id', $usuario->id)
            ->get()
            ->keyBy('eixo_numero');

        $temAcoes = $eixos->contains(fn ($e) => ! empty($e->acoes));
        if (! $temAcoes) {
            return response()->json(['message' => 'É necessário ao menos uma ação cadastrada em qualquer eixo para enviar o plano.'], 422);
        }

        // Verificar envio (responsável + justificativas)
        $envio = PlanoAcaoEnvio::query()
            ->where('usuario_id', $usuario->id)
            ->first();

        if (! $envio
            || ! filled($envio->responsavel_nome)
            || ! filled($envio->responsavel_cargo)
            || ! filled($envio->responsavel_orgao)
            || ! filled($envio->responsavel_contato)
        ) {
            return response()->json(['message' => 'Os dados do Responsável Técnico devem estar completamente preenchidos.'], 422);
        }

        // Verificar justificativas para eixos sem ações
        for ($n = 1; $n <= 4; $n++) {
            $eixo = $eixos->get($n);
            if (! $eixo || empty($eixo->acoes)) {
                $campo = "justificativa_eixo_{$n}";
                if (! filled($envio->$campo)) {
                    return response()->json([
                        'message' => "O Eixo {$n} não possui ações cadastradas. Preencha a justificativa obrigatória para este eixo.",
                    ], 422);
                }
            }
        }

        // Enviar
        $envio->update([
            'status'      => 'em_analise',
            'enviado_em'  => now(),
        ]);

        $this->audit->log(
            'plano_acao.envio.enviar',
            $usuario->id,
            ['usuario_id' => $usuario->id, 'enviado_em' => now()->toIso8601String()],
            TipoAuditoria::UPDATE->name,
            'plano_acao_envios',
            $envio->id
        );

        return response()->json([
            'data'    => PlanoAcaoEnvioResource::make($envio->fresh()),
            'message' => 'Plano de ação enviado para análise com sucesso.',
        ]);
    }
}
