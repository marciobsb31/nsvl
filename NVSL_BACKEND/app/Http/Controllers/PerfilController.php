<?php

namespace App\Http\Controllers;

use App\Exceptions\ApiException;
use App\Models\Perfil;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class PerfilController extends Controller
{
    private const PERFIS_CADASTRO = [
        'Administrador Nacional',
        'Administrador Estadual',
        'Administrador Municipal',
        'Gestor Nacional',
        'Gestor Estadual',
        'Gestor Municipal',
    ];

    /**
     * GET /api/perfis
     *
     * Lista perfis disponíveis para vinculação.
     */
    public function index(): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            throw ApiException::unauthenticated();
        }

        $perfis = Perfil::query()
            ->whereIn('nome', self::PERFIS_CADASTRO)
            ->get(['id', 'nome', 'descricao'])
            ->sortBy(function (Perfil $perfil): int {
                $idx = array_search($perfil->nome, self::PERFIS_CADASTRO, true);
                return $idx === false ? PHP_INT_MAX : $idx;
            })
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
