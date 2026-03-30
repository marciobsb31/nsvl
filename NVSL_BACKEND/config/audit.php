<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Auditoria — configurações gerais (tabela auditoria_log)
    |--------------------------------------------------------------------------
    */

    // Referência de ações usadas no código (documentação / futura validação)
    'log_actions' => [
        'auth.redirect',
        'auth.callback',
        'auth.login',
        'auth.logout',
        'auth.callback_failed',
        'contexto.troca',
        'gerenciar_perfis.listagem',
        'gerenciar_perfis.cadastrar',
        'gerenciar_perfis.editar',
        'gerenciar_cadastros.listagem',
        'gerenciar_cadastros.detalhamento',
        'gerenciar_cadastros.solicitacao_interna_criada',
        'solicitacao_cadastro.criada',
        'gerenciar_cadastros.avaliacao',
        'gerenciar_cadastros.perfil_ativado',
        'gerenciar_cadastros.perfil_desativado',
        'gerenciar_cadastros.perfil_adicionado',
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
