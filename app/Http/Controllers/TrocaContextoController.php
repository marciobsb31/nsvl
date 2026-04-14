<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContextoRequest;
use Illuminate\Http\Response;
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

        $perfil = $usuario->perfisUsuario()
            ->where('id', $request->contexto_id)
            ->first();

        if (! $perfil) {
            return response()->json(['message' => 'Perfil não encontrado ou não pertence ao usuário.'],
                Response::HTTP_FORBIDDEN);
        }

        $usuario->contextoAtivo()->updateOrCreate(
            ['usuario_id' => $usuario->id],
            [
                'perfil_usuario_id'      => $perfil->id,
                'usuario_abrangencia_id' => $perfil->usuario_abrangencia_id,
            ]
        );

        return response()->json(['message' => 'Contexto alterado com sucesso!']);
    }
}
