<?php

namespace App\Http\Controllers\Auth;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Models\Usuario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TokenDeTesteController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        if (!in_array(config('app.env'), ['local', 'testing'], true)) {
            throw ApiException::forbidden('Acesso não permitido.');
        }

        $perfil = $request->input('perfil', 'federal');
        $perfil = in_array($perfil, ['federal', 'estadual', 'municipal']) ? $perfil : 'federal';

        $user = match ($perfil) {
            'federal'   => Usuario::where('govbr_sub', 'teste-federal-001')->first(),
            'estadual'  => Usuario::where('govbr_sub', 'teste-estadual-go-002')->first(),
            'municipal' => Usuario::where('govbr_sub', 'teste-municipal-alexania-003')->first(),
            default     => null,
        };

        if (!$user) {
            throw ApiException::notFound('Usuário de teste não encontrado.');
        }

        $token = $user->createToken('teste-' . $perfil)->plainTextToken;

        return response()->json([
            'token' => $token,
            'user'  => $user->toSafeArray(),
        ]);
    }
}
