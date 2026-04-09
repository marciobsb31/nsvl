<?php

$frontendUrl  = rtrim((string) env('GOVBR_FRONTEND_URL', env('FRONTEND_URL', 'http://localhost:5176')), '/');
$appUrl       = rtrim((string) env('APP_URL', 'http://localhost:8081'), '/');

$origins = array_values(array_unique(array_filter([
    $frontendUrl,
    $appUrl,
    'http://localhost',
    'http://localhost:80',
    'http://localhost:5173',
    'https://nvsl.mdh.gov.br',
    'https://nvsl.hlog.mdh.gov.br',
    'https://nvsl.dev.mdh.gov.br',
    
])));

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],

    'allowed_origins' => $origins,

    'allowed_origins_patterns' => [],

    'allowed_headers' => [
        'Content-Type',
        'Authorization',
        'Accept',
        'X-Requested-With',
        'X-XSRF-TOKEN',
    ],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
