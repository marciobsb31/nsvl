<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\UsuarioResource;
use OpenApi\Attributes as OA;

/**
 * @OA\Schema(
 *     schema="UserResource",
 *     type="object",
 *
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
class UsuarioController extends Controller
{
    #[OA\Get(
        path: '/api/user',
        description: 'Campos sensíveis são omitidos da resposta.',
        summary: 'Retorna os dados do usuário autenticado',
        security: [['BearerAuth' => []]],
        tags: ['Usuário'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Dados seguros do usuário',
                content: new OA\JsonContent(ref: '#/components/schemas/UserResource')
            ),
            new OA\Response(response: 401, description: 'Não autenticado'),
        ]
    )]
    public function me()
    {
        return UsuarioResource::make(
            auth()->user()->load('perfis.permissoes')
        );
    }
}
