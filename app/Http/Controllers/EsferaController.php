<?php

namespace App\Http\Controllers;

use App\Http\Resources\EsferaResource;
use App\Models\Esfera;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Cadastro', description: 'Dados auxiliares de formulário')]
class EsferaController extends Controller
{
    #[OA\Get(
        path: '/api/esferas',
        summary: 'Lista esferas de atuação',
        tags: ['Cadastro'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Lista value/label',
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
    public function index()
    {
        return EsferaResource::collection(Esfera::all());
    }
}
