<?php

namespace App\Http\Controllers\Auth;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Models\Usuario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Autenticação')]
class TokenDeTesteController extends Controller
{
    #[OA\Post(
        path: '/api/auth/token-de-teste',
        summary: 'Emite token Sanctum para usuário de teste',
        description: 'Disponível apenas com APP_ENV=local ou testing (desabilitado em produção).',
        tags: ['Autenticação'],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(
                        property: 'perfil',
                        type: 'string',
                        enum: ['federal', 'estadual', 'municipal'],
                        description: 'Qual usuário fixture usar'
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Token e usuário',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'token', type: 'string'),
                        new OA\Property(property: 'user', ref: '#/components/schemas/UserResource'),
                    ]
                )
            ),
            new OA\Response(response: 403, description: 'Ambiente não permitido'),
            new OA\Response(response: 404, description: 'Usuário de teste não encontrado (execute seeders)'),
            new OA\Response(response: 429, description: 'Limite de requisições'),
        ]
    )]
    public function store(Request $request): JsonResponse
    {
        if (! in_array(config('app.env'), ['local', 'testing'], true)) {
            throw ApiException::forbidden('Acesso não permitido.');
        }

        $perfil = $request->input('perfil', 'federal');
        $perfil = in_array($perfil, ['federal', 'estadual', 'municipal']) ? $perfil : 'federal';

        $user = match ($perfil) {
            'federal'   => Usuario::where('govbr_sub', '11144477735')->first(),
            'estadual'  => Usuario::where('govbr_sub', '52998224725')->first(),
            'municipal' => Usuario::where('govbr_sub', '98765432100')->first(),
            default     => null,
        };

        if (! $user) {
            throw ApiException::notFound('Usuário de teste não encontrado.');
        }

        $token = $user->createToken('teste-'.$perfil)->plainTextToken;

        return response()->json([
            'token' => $token,
            'user'  => $user->toSafeArray(),
        ]);
    }
}
