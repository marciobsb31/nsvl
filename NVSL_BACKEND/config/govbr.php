<?php

$baseUrl = rtrim(env('GOVBR_SSO_URL', 'https://sso.staging.acesso.gov.br'), '/');

/*
 * redirect_uri: deve corresponder EXATAMENTE ao cadastrado no MGI para o client_id.
 * Para homologação (client_id h-nvsl.dev.mdh.gov.br), use a URL do ambiente homologado.
 * Para testes em localhost: solicite ao MGI a inclusão de http://localhost:8081/redirect-gov
 * nos redirect URIs permitidos. Roteiro: https://acesso.gov.br/roteiro-tecnico
 */
$redirectUri = env('GOVBR_REDIRECT_URI');
if (empty($redirectUri)) {
    $appUrl = rtrim(env('APP_URL', ''), '/');
    $redirectUri = $appUrl ? $appUrl . '/redirect-gov' : 'https://h-nvsl.dev.mdh.gov.br/redirect-gov';
}

/*
 * frontend_url: para onde o navegador volta APÓS o callback em /redirect-gov (fragment #govbr_*).
 * Deve ser a MESMA origem onde o usuário abriu o NVSL (Docker: http://localhost ; Vite dev: http://localhost:5176).
 * Use GOVBR_FRONTEND_URL se FRONTEND_URL estiver desatualizado em relação ao dev server.
 */
$frontendUrl = env('GOVBR_FRONTEND_URL');
if (empty($frontendUrl)) {
    $frontendUrl = env('FRONTEND_URL', 'http://localhost');
}

return [
    'client_id' => env('GOVBR_CLIENT_ID'),
    'client_secret' => env('GOVBR_CLIENT_SECRET'),
    'redirect_uri' => $redirectUri,
    'frontend_url' => rtrim((string) $frontendUrl, '/'),
    'frontend_login_path' => env('GOVBR_FRONTEND_LOGIN_PATH', '/login'),
    'sso_url' => $baseUrl,
    'authorize_url' => env('GOVBR_AUTHORIZE_URL', $baseUrl . '/authorize'),
    'token_url' => env('GOVBR_TOKEN_URL', $baseUrl . '/token'),
    'userinfo_url' => env('GOVBR_USERINFO_URL', $baseUrl . '/userinfo'),
    'scopes' => preg_split('/\s+/', trim((string) env('GOVBR_SCOPES', 'openid email profile govbr_confiabilidades govbr_confiabilidades_idtoken')), -1, PREG_SPLIT_NO_EMPTY),
    'oauth_ttl_seconds' => (int) env('GOVBR_OAUTH_TTL_SECONDS', 600),
    'login_code_ttl_seconds' => (int) env('GOVBR_LOGIN_CODE_TTL_SECONDS', 120),
];
