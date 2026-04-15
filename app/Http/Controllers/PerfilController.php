<?php

namespace App\Http\Controllers;

use App\Exceptions\ApiException;
use App\Models\Perfil;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Perfis', description: 'Catálogo oficial para vínculos e formulários')]
class PerfilController extends Controller
{
    #[OA\Get(
        path: '/api/perfis',
        summary: 'Lista perfis oficiais ativos (select)',
        tags: ['Perfis'],
        security: [['BearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Lista de perfis',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(type: 'object')
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Não autenticado'),
        ]
    )]
    public function index(): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            throw ApiException::unauthenticated();
        }

        $perfis = Perfil::listarCatalogoPermitidoParaUsuario($user);

        return response()->json([
            'data' => $perfis->map(fn (Perfil $p) => [
                'value'     => $p->id,
                'label'     => $p->nome,
                'id'        => $p->id,
                'nome'      => $p->nome,
                'descricao' => $p->descricao,
            ]),
        ]);
    }
}
