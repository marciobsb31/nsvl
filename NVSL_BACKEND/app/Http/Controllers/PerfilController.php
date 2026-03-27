<?php

namespace App\Http\Controllers;

use App\Exceptions\ApiException;
use App\Models\Perfil;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class PerfilController extends Controller
{
    public function index(): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            throw ApiException::unauthenticated();
        }

        $perfis = Perfil::query()
            ->whereIn('nome', Perfil::CATALOGO_OFICIAL)
            ->where('ativo', true)
            ->get(['id', 'nome', 'descricao'])
            ->sortBy(fn (Perfil $perfil): int => Perfil::indiceNoCatalogo($perfil->nome))
            ->values();

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
