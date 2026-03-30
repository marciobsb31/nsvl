<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

/**
 * @OA\Schema(
 *     schema="UserResource",
 *     type="object",
 *     @OA\Property(property="id", type="integer"),
 *     @OA\Property(property="name", type="string"),
 *     @OA\Property(property="email", type="string", nullable=true),
 *     @OA\Property(property="sub", type="string", description="Identificador GOV.BR"),
 *     @OA\Property(property="esfera_atuacao", type="string", nullable=true),
 *     @OA\Property(property="uf_lotacao", type="string", nullable=true),
 *     @OA\Property(property="municipio_lotacao", type="string", nullable=true),
 *     @OA\Property(property="perfil_ativo_id", type="integer", nullable=true),
 *     @OA\Property(property="perfis_vigentes", type="array", @OA\Items(type="object"))
 * )
 */
#[OA\Tag(name: 'Usuário')]
class UserController extends Controller
{
    #[OA\Get(
        path: '/api/user',
        summary: 'Retorna os dados do usuário autenticado',
        description: 'Campos sensíveis são omitidos da resposta.',
        tags: ['Usuário'],
        security: [['BearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Dados seguros do usuário',
                content: new OA\JsonContent(ref: '#/components/schemas/UserResource')
            ),
            new OA\Response(response: 401, description: 'Não autenticado'),
        ]
    )]
    public function me(Request $request): JsonResponse
    {
        return response()->json($request->user()->toSafeArray());
    }
}
