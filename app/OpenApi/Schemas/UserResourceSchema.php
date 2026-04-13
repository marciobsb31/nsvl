<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'UserResource',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'name', type: 'string'),
        new OA\Property(property: 'email', type: 'string', nullable: true),
        new OA\Property(property: 'sub', type: 'string', description: 'Identificador GOV.BR'),
        new OA\Property(property: 'esfera_atuacao', type: 'string', nullable: true),
        new OA\Property(property: 'uf_lotacao', type: 'string', nullable: true),
        new OA\Property(property: 'municipio_lotacao', type: 'string', nullable: true),
        new OA\Property(property: 'perfil_ativo_id', type: 'integer', nullable: true),
        new OA\Property(property: 'perfis_vigentes', type: 'array', items: new OA\Items(type: 'object')),
    ]
)]
final class UserResourceSchema {}
