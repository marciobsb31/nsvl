<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Auditoria — configurações gerais (tabela auditoria_log)
    |--------------------------------------------------------------------------
    */

    // Ações que devem ser registradas
    'log_actions' => [
        'auth.login',
        'auth.logout',
        'auth.callback',
        'auth.callback_failed',
        'contexto.troca',
    ],

    // Campos que NUNCA aparecem em logs de auditoria (mascarados)
    'masked_fields' => [
        'password',
        'client_secret',
        'access_token',
        'id_token',
        'refresh_token',
        'cpf',
    ],

    // Retenção dos registros de auditoria em dias (0 = infinito)
    'retention_days' => 0,
];
