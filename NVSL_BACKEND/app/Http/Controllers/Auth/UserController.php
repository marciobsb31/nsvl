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
 *     @OA\Property(property="role", type="string", example="user"),
 *     @OA\Property(property="sub", type="string", description="Identificador GOV.BR")
 * )
 */
class UserController extends Controller
{
    // -------------------------------------------------------
    // GET /api/user
    // -------------------------------------------------------

    #[OA\Get(
        path: '/api/user',
        summary: 'Retorna os dados do usuário autenticado',
        description: 'Campos sensíveis (CPF hash) são omitidos da resposta.',
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
