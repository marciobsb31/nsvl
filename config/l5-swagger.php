<?php

return [
    'default' => 'default',

    'documentations' => [
        'default' => [
            'api' => [
                'title' => 'NVSL API',
                'description' => 'API do Sistema NVSL — documentação interativa via OpenAPI 3.0',
                'version' => '1.0.0',
            ],

            'routes' => [
                // URL da UI Swagger
                'api' => 'api/docs',
            ],

            'paths' => [
                // Onde o arquivo openapi.json será gerado
                'docs'                => storage_path('api-docs'),
                'docs_json'           => 'api-docs.json',
                'docs_yaml'           => 'api-docs.yaml',
                'annotations'         => [
                    base_path('app'),
                ],
                'base'                => null,
                'swagger_ui_assets_path' => 'vendor/swagger-api/swagger-ui/dist/',
                'excludes'            => [],
            ],

            'security' => [
                'BearerAuth' => [
                    'type'         => 'http',
                    'scheme'       => 'bearer',
                    'bearerFormat' => 'Sanctum',
                    'description'  => 'Token Sanctum obtido após autenticação GOV.BR. Formato: Bearer {token}',
                ],
            ],

            'generate_always' => env('L5_SWAGGER_GENERATE_ALWAYS', true),

            'proxy'           => false,
            'additional_config_url' => null,
            'operations_sort' => null,
            'validator_url'   => null,
        ],
    ],

    'defaults' => [
        'routes' => [
            'docs'     => 'docs',
            'oauth2_callback' => 'api/oauth2-callback',
            'middleware' => [
                'api' => [
                    \App\Http\Middleware\DisallowSwaggerInProduction::class,
                ],
                'asset' => [
                    \App\Http\Middleware\DisallowSwaggerInProduction::class,
                ],
                'docs' => [
                    \App\Http\Middleware\DisallowSwaggerInProduction::class,
                ],
                'oauth2_callback' => [],
            ],
            'group_options' => [],
        ],

        'paths' => [
            'docs'                => storage_path('api-docs'),
            'docs_json'           => 'api-docs.json',
            'docs_yaml'           => 'api-docs.yaml',
            'format_to_use_for_docs' => env('L5_FORMAT_TO_USE_FOR_DOCS', 'json'),
            'annotations'         => base_path('app'),
            'views'               => base_path('resources/views/vendor/l5-swagger'),
            'base'                => null,
            'swagger_ui_assets_path' => 'vendor/swagger-api/swagger-ui/dist/',
            'excludes'            => [],
        ],

        'scanOptions' => [
            'analyser'            => null,
            'analysis'            => null,
            'crawler'             => null,
            'exclude'             => null,
            'pattern'             => null,
            'processors'          => [],
        ],

        'securityDefinitions' => [
            'securitySchemes' => [
                'BearerAuth' => [
                    'type'         => 'http',
                    'scheme'       => 'bearer',
                    'bearerFormat' => 'Sanctum',
                ],
            ],
            'security' => [
                ['BearerAuth' => []],
            ],
        ],

        'generate_always' => env('L5_SWAGGER_GENERATE_ALWAYS', true),
        'generate_yaml_copy' => false,
        'swagger_version' => env('SWAGGER_VERSION', '3.0'),
        'proxy' => false,
        'additional_config_url' => null,
        'operations_sort' => env('L5_SWAGGER_OPERATIONS_SORT', null),
        'validator_url' => null,
        'ui' => [
            'display' => [
                'doc_expansion' => env('L5_SWAGGER_UI_DOC_EXPANSION', 'none'),
                'filter'        => env('L5_SWAGGER_UI_FILTERS', true),
                'show_extensions' => false,
                'show_common_extensions' => false,
                'try_it_out_enabled' => env('L5_SWAGGER_UI_TRY_IT_OUT', true),
            ],
            'authorization' => [
                'persist_authorization' => env('L5_SWAGGER_UI_PERSIST_AUTHORIZATION', true),
            ],
        ],
        'constants' => [
            'L5_SWAGGER_CONST_HOST' => env('L5_SWAGGER_CONST_HOST', env('APP_URL', 'http://localhost:8081')),
        ],
    ],
];
