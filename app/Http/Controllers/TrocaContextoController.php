<?php

namespace App\Http\Controllers;

use App\Enums\TipoAuditoria;
use App\Http\Resources\UsuarioResource;
use App\Http\Requests\ContextoRequest;
use App\Services\Audit\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Contexto', description: 'Perfil ativo do usuário')]
class TrocaContextoController extends Controller
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
    ) {}

    public function index(): JsonResponse
    {
        $usuario = auth()->user();
        $perfilAtualId = $usuario?->contextoAtivo?->perfil_usuario_id;

        $hoje = now()->toDateString();

        $contextos = $usuario
            ->perfisUsuario()
            ->with([
                'perfil.esfera',
                'abrangencia.esfera',
                'abrangencia.uf',
                'abrangencia.municipio',
                'solicitacaoCadastroOrigem',
            ])
            ->where('ativo', true)
            ->where(function ($q) use ($hoje) {
                $q->whereNull('data_inicio_vigencia')
                    ->orWhereDate('data_inicio_vigencia', '<=', $hoje);
            })
            ->where(function ($q) use ($hoje) {
                $q->whereNull('data_fim_vigencia')
                    ->orWhereDate('data_fim_vigencia', '>=', $hoje);
            })
            ->get();

        $data = $contextos->map(function ($ctx) {
            return [
                'perfil_usuario_id' => $ctx->id,
                'nome'             => $ctx->perfil?->nome,
                'esfera'           => $ctx->abrangencia?->esfera?->nome,
                'uf'               => $ctx->abrangencia?->uf?->sigla,
                'municipio'        => $ctx->abrangencia?->municipio?->nome,
                'orgao'            => $ctx->solicitacaoCadastroOrigem?->orgao,
                'ativo'            => (bool) $ctx->ativo,
            ];
        });

        return response()->json([
            'data' => $data,
            'perfil_atual_id' => $perfilAtualId,
        ]);
    }

    public function selecionar(ContextoRequest $request): JsonResponse
    {
        $usuario = auth()->user();

        $hoje = now()->toDateString();
        $perfilAnteriorId = $usuario?->contextoAtivo?->perfil_usuario_id;

        $perfilAnterior = $usuario
            ->perfisUsuario()
            ->with('perfil')
            ->where('id', $perfilAnteriorId)
            ->first();

        $perfilUsuario = $usuario->perfisUsuario()
            ->with('perfil')
            ->where('id', $request->perfil_usuario_id)
            ->where('ativo', true)
            ->where(function ($q) use ($hoje) {
                $q->whereNull('data_inicio_vigencia')
                    ->orWhereDate('data_inicio_vigencia', '<=', $hoje);
            })
            ->where(function ($q) use ($hoje) {
                $q->whereNull('data_fim_vigencia')
                    ->orWhereDate('data_fim_vigencia', '>=', $hoje);
            })
            ->first();

        if (! $perfilUsuario) {
            return response()->json(
                ['message' => 'Contexto inválido ou não pertence ao usuário.'],
                Response::HTTP_FORBIDDEN
            );
        }

        $contexto = $usuario->contextoAtivo()->updateOrCreate(
            ['usuario_id' => $usuario->id],
            [
                'perfil_usuario_id'      => $perfilUsuario->id,
                'usuario_abrangencia_id' => $perfilUsuario->usuario_abrangencia_id,
            ]
        );

        $this->auditLogService->log(
            'contexto.troca',
            $usuario->id,
            [
                'perfil_anterior_id' => $perfilAnterior?->id,
                'perfil_anterior_nome' => $perfilAnterior?->perfil?->nome,
                'novo_perfil_id' => $perfilUsuario->id,
                'novo_perfil_nome' => $perfilUsuario->perfil?->nome,
                'data_hora_troca' => now()->toDateTimeString(),
            ],
            TipoAuditoria::UPDATE->name,
            'usuario_contexto',
            $contexto->id
        );

        $usuario->load([
            'perfisUsuario' => function ($q) use ($hoje) {
                $q->where('ativo', true)
                    ->where(function ($subQ) use ($hoje) {
                        $subQ->whereNull('data_inicio_vigencia')
                            ->orWhereDate('data_inicio_vigencia', '<=', $hoje);
                    })
                    ->where(function ($subQ) use ($hoje) {
                        $subQ->whereNull('data_fim_vigencia')
                            ->orWhereDate('data_fim_vigencia', '>=', $hoje);
                    });
            },
            'perfisUsuario.perfil',
            'perfisUsuario.abrangencia.esfera',
            'perfisUsuario.abrangencia.uf',
            'perfisUsuario.abrangencia.municipio',
            'perfisUsuario.solicitacaoCadastroOrigem',
            'contextoAtivo.perfilUsuario.perfil.permissoes',
            'contextoAtivo.abrangencia.esfera',
            'contextoAtivo.abrangencia.uf',
            'contextoAtivo.abrangencia.municipio',
        ]);

        return response()->json([
            'message' => 'Contexto alterado com sucesso!',
            'user' => UsuarioResource::make($usuario),
        ]);
    }
}
