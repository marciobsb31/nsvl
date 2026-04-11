<?php

namespace App\Http\Controllers;

use App\Exceptions\ApiException;
use App\Models\AuditLog;
use App\Models\PerfilUsuario;
use App\Models\Usuario;
use App\Services\Audit\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Contexto', description: 'Perfil ativo do usuário')]
class TrocaContextoController extends Controller
{
    public function __construct(
        private readonly AuditLogService $audit
    ) {}

    #[OA\Get(
        path: '/api/user/perfis-ativos',
        summary: 'Perfis vigentes do usuário',
        tags: ['Contexto'],
        security: [['BearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Lista em data[]'),
            new OA\Response(response: 401, description: 'Não autenticado'),
        ]
    )]
    public function listarPerfisAtivos(): JsonResponse
    {
        /** @var Usuario|null $user */
        $user = Auth::user();
        if (!$user) {
            throw ApiException::unauthenticated();
        }

        return response()->json([
            'data' => $user->toSafeArray()['perfis_vigentes'],
        ]);
    }

    #[OA\Post(
        path: '/api/user/trocar-contexto',
        summary: 'Define o perfil ativo (pivot perfil_usuario)',
        tags: ['Contexto'],
        security: [['BearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['perfil_usuario_id'],
                properties: [
                    new OA\Property(property: 'perfil_usuario_id', type: 'integer'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'user atualizado (toSafeArray)',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'user', ref: '#/components/schemas/UserResource'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Não autenticado'),
            new OA\Response(response: 403, description: 'Perfil inválido para o usuário'),
            new OA\Response(response: 422, description: 'Validação'),
        ]
    )]
    public function trocarContexto(Request $request): JsonResponse
    {
        /** @var Usuario|null $user */
        $user = Auth::user();
        if (!$user) {
            throw ApiException::unauthenticated();
        }

        $request->validate([
            'perfil_usuario_id' => ['required', 'integer'],
        ], [
            'perfil_usuario_id.required' => 'Informe o perfil a ser ativado.',
        ]);

        $perfilUsuarioId = (int) $request->input('perfil_usuario_id');

        $perfisVigentes = $user->perfisVigentes();
        $novoPerfilPivot = $perfisVigentes->first(
            fn ($p) => $p->pivot->id == $perfilUsuarioId
        );

        if (!$novoPerfilPivot) {
            throw ApiException::forbidden('O perfil selecionado não está ativo ou não pertence ao seu cadastro.');
        }

        $perfilAnterior = $perfisVigentes->first(fn ($p) => $p->pivot->ativo);

        // Desmarcar todos os perfis do usuário e ativar o selecionado
        PerfilUsuario::where('usuario_id', $user->id)->update(['ativo' => false]);
        PerfilUsuario::where('id', $perfilUsuarioId)->update(['ativo' => true]);

        $this->audit->log(
            'contexto.troca',
            $user->id,
            [
                'perfil_anterior_id'   => $perfilAnterior?->pivot->id,
                'perfil_anterior_nome' => $perfilAnterior?->nome,
                'novo_perfil_id'       => $perfilUsuarioId,
                'novo_perfil_nome'     => $novoPerfilPivot->nome,
            ],
            AuditLog::TIPO_UPDATE,
            'perfil_usuario',
            $perfilUsuarioId
        );

        $user->refresh();

        return response()->json([
            'message' => 'Contexto alterado com sucesso.',
            'user'    => $user->toSafeArray(),
        ]);
    }
}
