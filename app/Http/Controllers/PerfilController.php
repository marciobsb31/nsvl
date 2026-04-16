<?php

namespace App\Http\Controllers;

use App\Http\Resources\PerfilMinResource;
use App\Models\Perfil;
use App\Support\MvpPerfilRules;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Perfis', description: 'Catálogo oficial para vínculos e formulários')]
class PerfilController extends Controller
{
    #[OA\Get(
        path: '/api/perfis',
        summary: 'Lista perfis oficiais ativos (select)',
        security: [['BearerAuth' => []]],
        tags: ['Perfis'],
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
    public function index(Request $request)
    {
        $usuario = $request->user();
        $codigoPerfilAtivo = MvpPerfilRules::resolveActiveProfileCode($usuario);

        $codigosPermitidos = $usuario?->hasPermissao('solicitacoes_cadastro.analisar')
            ? MvpPerfilRules::allowedTargetCodesForEvaluator($codigoPerfilAtivo)
            : [];

        $ordemMvp = MvpPerfilRules::mvpProfileCodes();
        $orderSql = 'CASE codigo '
            . collect($ordemMvp)
                ->values()
                ->map(fn (string $codigo, int $i) => "WHEN '{$codigo}' THEN {$i}")
                ->implode(' ')
            . ' ELSE 999 END';

        $perfis = Perfil::query()
            ->where('ativo', true)
            ->whereIn('codigo', $codigosPermitidos)
            ->orderByRaw($orderSql)
            ->get();

        return PerfilMinResource::collection($perfis);
    }
}
