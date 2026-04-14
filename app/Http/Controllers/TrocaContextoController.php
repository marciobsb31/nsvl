<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContextoRequest;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Contexto', description: 'Perfil ativo do usuário')]
class TrocaContextoController extends Controller
{
    public function index()
    {
        $contextos = auth()->user()
            ->perfisUsuario()
            ->with([
                'perfil.esfera',
                'abrangencia.esfera',
            ])
            ->where('ativo', true)
            ->get();

        $data = $contextos->map(function ($ctx) {
            return [
                'id'         => $ctx->id,
                'perfil'     => $ctx->perfil?->nome,
                'esfera'     => $ctx->perfil?->esfera?->nome,
                'localidade' => $ctx->abrangencia?->nome,
            ];
        });

        return response()->json($data);
    }

    public function selecionar(ContextoRequest $request)
    {
        $usuario = auth()->user();

        $contexto = $usuario->perfisUsuario()
            ->where('id', $request->contexto_id)
            ->where('ativo', true)
            ->first();

        if (! $contexto) {
            return response()->json(['message' => 'Contexto inválido.'], 403);
        }

        $usuario->contextoAtivo()->updateOrCreate(
            ['usuario_id' => $usuario->id],
            [
                'perfil_usuario_id'      => $contexto->id,
                'usuario_abrangencia_id' => $contexto->usuario_abrangencia_id,
            ]
        );

        return response()->json(['message' => 'Contexto alterado com sucesso!']);
    }
}
