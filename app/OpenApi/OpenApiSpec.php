<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'NVSL API',
    description: 'API do Sistema NVSL com autenticação GOV.BR e gestão de perfis.',
    contact: new OA\Contact(email: 'suporte@nvsl.gov.br'),
    license: new OA\License(name: 'Proprietário')
)]
#[OA\SecurityScheme(
    securityScheme: 'BearerAuth',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'Sanctum',
    description: 'Token Sanctum obtido após autenticação GOV.BR.'
)]
final class OpenApiSpec {}
