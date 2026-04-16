<?php

namespace App\Http\Controllers;

use App\Http\Resources\EstadoResource;
use App\Http\Resources\MunicipioResource;
use App\Models\Municipio;
use App\Models\Uf;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Localidades', description: 'UFs e municípios (replica IBGE)')]
class LocalidadeController extends Controller
{
    #[OA\Get(
        path: '/api/localidades/ufs',
        summary: 'Lista todas as UFs',
        tags: ['Localidades'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'UFs ordenadas por nome',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'value', type: 'string'),
                                    new OA\Property(property: 'label', type: 'string'),
                                ],
                                type: 'object'
                            )
                        ),
                    ]
                )
            ),
        ]
    )]
    public function ufs()
    {
        return EstadoResource::collection(Uf::all());
    }

    #[OA\Get(
        path: '/api/localidades/completo',
        summary: 'UFs e municípios por UF (payload único)',
        tags: ['Localidades'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'JSON com `data.ufs` (array) e `data.municipios_por_uf` (objeto sigla → municípios value/label)'
            ),
        ]
    )]
    public function completo(): JsonResponse
    {
        $ufs = Uf::orderBy('nome')
            ->get()
            ->map(fn (Uf $e) => [
                'value' => $e->sigla,
                'label' => $e->sigla.' - '.$e->nome,
                'id'    => $e->id,
            ])
            ->values()
            ->all();

        $municipiosPorUf = [];
        foreach (Uf::with(['municipios' => fn ($q) => $q->orderBy('nome')])->orderBy('nome')->get() as $uf) {
            $municipiosPorUf[$uf->sigla] = $uf->municipios->map(fn (Municipio $m) => [
                'value' => $m->nome,
                'label' => $m->nome,
            ])->values()->all();
        }

        return response()->json([
            'data' => [
                'ufs'               => $ufs,
                'municipios_por_uf' => $municipiosPorUf,
            ],
        ]);
    }

    #[OA\Get(
        path: '/api/localidades/municipios',
        summary: 'Municípios por sigla da UF',
        tags: ['Localidades'],
        parameters: [
            new OA\Parameter(name: 'uf', in: 'query', required: true, schema: new OA\Schema(type: 'string', example: 'GO')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Lista de municípios',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'value', type: 'string'),
                                    new OA\Property(property: 'label', type: 'string'),
                                ],
                                type: 'object'
                            )
                        ),
                    ]
                )
            ),
            new OA\Response(response: 422, description: 'Parâmetro uf inválido'),
        ]
    )]
    public function municipios(string $ufParam)
    {
        $ufModel = ctype_digit($ufParam)
            ? Uf::query()->where('id', (int) $ufParam)->firstOrFail()
            : Uf::query()->where('sigla', strtoupper($ufParam))->firstOrFail();

        $municipios = $ufModel->municipios()->orderBy('nome')->get();

        return MunicipioResource::collection($municipios);
    }
}
