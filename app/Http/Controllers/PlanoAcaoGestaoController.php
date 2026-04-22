<?php

namespace App\Http\Controllers;

use App\Enums\TipoAuditoria;
use App\Http\Resources\PlanoAcaoGestaoResource;
use App\Models\AuditLog;
use App\Models\PlanoAcaoEnvio;
use App\Models\Usuario;
use App\Services\Audit\AuditLogService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlanoAcaoGestaoController extends Controller
{
    public function __construct(
        private readonly AuditLogService $audit
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filtros = $request->validate([
            'esfera' => ['nullable', 'string', 'in:Estadual,Municipal'],
            'uf_id' => ['nullable', 'integer'],
            'municipio_id' => ['nullable', 'integer'],
            'status' => ['nullable', 'string'],
        ]);

        $usuario = $request->user();

        $query = $this->baseQuery($usuario)
            ->when(! empty($filtros['esfera']), function (Builder $q) use ($filtros) {
                $q->where('esferas.nome', $filtros['esfera']);
            })
            ->when(! empty($filtros['uf_id']), function (Builder $q) use ($filtros) {
                $q->where('usuario_abrangencia.uf_id', (int) $filtros['uf_id']);
            })
            ->when(! empty($filtros['municipio_id']), function (Builder $q) use ($filtros) {
                $q->where('usuario_abrangencia.municipio_id', (int) $filtros['municipio_id']);
            })
            ->when(! empty($filtros['status']), function (Builder $q) use ($filtros) {
                $statusBanco = self::statusDoBancoPorFiltro($filtros['status']);
                if (! empty($statusBanco)) {
                    $q->whereIn('plano_acao_envios.status', $statusBanco);
                }
            })
            ->orderByRaw('CASE WHEN plano_acao_envios.enviado_em IS NULL THEN 1 ELSE 0 END')
            ->orderByDesc('plano_acao_envios.enviado_em');

        $planos = $query->get();

        $this->audit->log(
            'plano_acao.gestao.listar',
            $usuario->id,
            $filtros,
            TipoAuditoria::VIEW->name,
            'plano_acao_envios',
            null
        );

        return response()->json([
            'data' => PlanoAcaoGestaoResource::collection($planos),
        ]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $usuario = $request->user();

        $plano = $this->baseQuery($usuario)
            ->where('plano_acao_envios.id', $id)
            ->firstOrFail();

        $this->audit->log(
            'plano_acao.gestao.detalhar',
            $usuario->id,
            ['plano_envio_id' => $id],
            TipoAuditoria::VIEW->name,
            'plano_acao_envios',
            $id
        );

        return response()->json([
            'data' => new PlanoAcaoGestaoResource($plano, true),
        ]);
    }

    public function aprovar(Request $request, int $id): JsonResponse
    {
        $usuario = $request->user();

        $plano = $this->baseQuery($usuario)
            ->where('plano_acao_envios.id', $id)
            ->firstOrFail();

        $statusAnterior = (string) ($plano->status ?? '');
        $dataHoraAprovacao = now()->toIso8601String();

        \App\Models\PlanoAcaoEnvio::where('id', $id)
            ->update(['status' => 'sei_abertura_processo']);

        $this->audit->log(
            'plano_acao.gestao.aprovar',
            $usuario->id,
            [
                'plano_envio_id' => $id,
                'status_anterior' => $statusAnterior,
                'novo_status' => 'sei_abertura_processo',
                'data_hora_aprovacao' => $dataHoraAprovacao,
            ],
            TipoAuditoria::UPDATE->name,
            'plano_acao_envios',
            $id
        );

        return response()->json([
            'message' => 'Plano aprovado. Status alterado para SEI - Abertura de processo.',
            'status' => 'sei_abertura_processo',
        ]);
    }

    public function solicitarAjustes(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'observacao' => ['nullable', 'string', 'max:4000'],
        ]);

        $usuario = $request->user();

        $this->baseQuery($usuario)
            ->where('plano_acao_envios.id', $id)
            ->firstOrFail();

        \App\Models\PlanoAcaoEnvio::where('id', $id)
            ->update(['status' => 'em_ajustes']);

        $this->audit->log(
            'plano_acao.gestao.solicitar_ajustes',
            $usuario->id,
            ['plano_envio_id' => $id, 'novo_status' => 'em_ajustes', 'observacao' => $request->observacao],
            TipoAuditoria::UPDATE->name,
            'plano_acao_envios',
            $id
        );

        return response()->json([
            'message' => 'Ajustes solicitados. Status do plano alterado para Em ajustes.',
            'status' => 'em_ajustes',
        ]);
    }

    public function historico(Request $request, int $id): JsonResponse
    {
        $usuario = $request->user();

        // Reaproveita a mesma validação de acesso do detalhamento.
        $this->baseQuery($usuario)
            ->where('plano_acao_envios.id', $id)
            ->firstOrFail();

        $logs = AuditLog::query()
            ->with('usuario.contextoAtivo.perfilUsuario.perfil')
            ->where('tabela_afetada', 'plano_acao_envios')
            ->where('registro_id', $id)
            ->orderByDesc('created_at')
            ->get();

        $historico = $logs->map(function (AuditLog $log) {
            $perfil = (string) ($log->usuario?->contextoAtivo?->perfilUsuario?->perfil?->nome ?? '—');

            return [
                'id' => $log->id,
                'data_hora' => $log->created_at?->toIso8601String(),
                'usuario' => (string) ($log->usuario?->nome ?? 'Sistema'),
                'perfil' => $perfil !== '' ? $perfil : '—',
                'evento' => self::eventoLabel($log->acao),
            ];
        })->values();

        $this->audit->log(
            'plano_acao.gestao.historico',
            $usuario->id,
            ['plano_envio_id' => $id],
            TipoAuditoria::VIEW->name,
            'plano_acao_envios',
            $id
        );

        return response()->json([
            'data' => $historico,
        ]);
    }

    private function baseQuery(Usuario $usuario): Builder
    {
        $query = PlanoAcaoEnvio::query()
            ->leftJoin('plano_acao_identificacoes', 'plano_acao_identificacoes.usuario_id', '=', 'plano_acao_envios.usuario_id')
            ->join('usuarios', 'usuarios.id', '=', 'plano_acao_envios.usuario_id')
            ->leftJoin('usuario_contexto', 'usuario_contexto.usuario_id', '=', 'usuarios.id')
            ->leftJoin('usuario_abrangencia', 'usuario_abrangencia.id', '=', 'usuario_contexto.usuario_abrangencia_id')
            ->leftJoin('esferas', 'esferas.id', '=', 'usuario_abrangencia.esfera_id')
            ->leftJoin('ufs', 'ufs.id', '=', 'usuario_abrangencia.uf_id')
            ->leftJoin('municipios', 'municipios.id', '=', 'usuario_abrangencia.municipio_id')
            ->select([
                'plano_acao_envios.*',
                'plano_acao_identificacoes.orgao_gestor',
                'plano_acao_identificacoes.vigencia_inicio',
                'plano_acao_identificacoes.vigencia_fim',
                'esferas.nome as esfera_nome',
                'ufs.id as uf_id',
                'ufs.sigla as uf_sigla',
                'ufs.nome as uf_nome',
                'municipios.id as municipio_id',
                'municipios.nome as municipio_nome',
            ]);

        $contexto = $usuario->loadMissing('contextoAtivo.abrangencia.esfera')->contextoAtivo;
        $nomeEsferaContexto = mb_strtolower((string) ($contexto?->abrangencia?->esfera?->nome ?? ''));

        if ($nomeEsferaContexto === 'estadual') {
            $query->where('usuario_abrangencia.uf_id', $contexto?->abrangencia?->uf_id)
                ->where('esferas.nome', 'Estadual');
        }

        if ($nomeEsferaContexto === 'municipal') {
            $query->where('usuario_abrangencia.municipio_id', $contexto?->abrangencia?->municipio_id)
                ->where('usuario_abrangencia.uf_id', $contexto?->abrangencia?->uf_id)
                ->where('esferas.nome', 'Municipal');
        }

        return $query;
    }

    /**
     * Traduz o texto do filtro para os valores persistidos em banco.
     */
    private static function statusDoBancoPorFiltro(string $status): array
    {
        return match ($status) {
            'Não enviado' => ['rascunho'],
            'Enviado para análise' => ['em_analise', 'enviado_para_analise'],
            'Em análise pela Comissão Técnica' => ['em_analise', 'em_analise_comissao_tecnica'],
            'Devolvido para ajustes' => ['devolvido_ajustes'],
            'Aprovado' => ['aprovado'],
            'Não iniciado' => ['nao_iniciado'],
            default => [$status],
        };
    }

    private static function eventoLabel(string $acao): string
    {
        return match ($acao) {
            'plano_acao.gestao.listar' => 'Listagem de planos',
            'plano_acao.gestao.detalhar' => 'Visualização do plano',
            'plano_acao.gestao.historico' => 'Acesso ao histórico do plano',
            'plano_acao.gestao.aprovar' => 'Aprovação do plano',
            'plano_acao.gestao.solicitar_ajustes' => 'Solicitação de ajustes',
            'plano_acao.envio.salvar' => 'Salvar envio do plano',
            'plano_acao.envio.submit' => 'Envio do plano para análise',
            default => $acao,
        };
    }
}
