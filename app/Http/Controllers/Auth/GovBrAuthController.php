<?php

namespace App\Http\Controllers\Auth;

use App\Enums\TipoAuditoria;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Resources\UsuarioResource;
use App\Support\UsuarioContextoResolver;
use App\Services\Audit\AuditLogService;
use App\Services\Auth\AuthValidationService;
use App\Services\Auth\GovBrService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use OpenApi\Attributes as OA;
use Throwable;

#[OA\Tag(name: 'Autenticação', description: 'GOV.BR OAuth2 + PKCE e sessão Sanctum')]
class GovBrAuthController extends Controller
{
    public function __construct(
        private readonly GovBrService $govBrService,
        private readonly AuthValidationService $authValidationService,
        private readonly AuditLogService $auditLogService,
    ) {}

    #[OA\Get(
        path: '/api/auth/url',
        description: 'Retorna a URL de autorização do SSO (PKCE + state em cache).',
        summary: 'Inicia login GOV.BR',
        tags: ['Autenticação'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'URL do SSO',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'url', type: 'string', format: 'uri'),
                    ]
                )
            ),
            new OA\Response(response: 422, description: 'Configuração GOV.BR incompleta'),
            new OA\Response(response: 429, description: 'Limite de requisições'),
        ]
    )]
    public function redirect(): JsonResponse
    {
        return response()->json(['url' => $this->gerarUrlDeAutorizacao()]);
    }

    #[OA\Get(
        path: '/api/auth/redirect',
        description: 'Processa o retorno do GOV.BR, valida state/code, cria token Sanctum e redireciona ao frontend com fragmento.',
        summary: 'Callback OAuth2 do GOV.BR',
        tags: ['Autenticação'],
        parameters: [
            new OA\Parameter(name: 'code', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'state', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'error', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'error_description', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 302, description: 'Redirecionamento para FRONTEND_URL/login#govbr_login_code=... ou govbr_error=...'),
            new OA\Response(response: 429, description: 'Limite de requisições'),
        ]
    )]
    public function callback(Request $request): RedirectResponse
    {
        $govBrUser = null;

        try {
            $this->garantirConfiguracao();

            if ($request->filled('error')) {
                $descricao = (string) ($request->input('error_description') ?: $request->input('error'));
                throw ValidationException::withMessages([
                    'auth' => 'Falha no retorno do GOV.BR: '.$descricao,
                ]);
            }

            $state = (string) $request->query('state', '');
            $code = (string) $request->query('code', '');

            if ($state === '' || $code === '') {
                throw ValidationException::withMessages([
                    'auth' => 'Resposta do GOV.BR incompleta. O código de autorização não foi recebido.',
                ]);
            }

            $oauthData = Cache::pull($this->oauthCacheKey($state));
            if (! is_array($oauthData)) {
                throw ValidationException::withMessages([
                    'auth' => 'O estado da autenticação GOV.BR expirou ou é inválido.',
                ]);
            }

            $tokens = $this->govBrService->trocarCodePorToken($code, (string) $oauthData['code_verifier']);

            if (! $this->govBrService->validarNonce($tokens['id_token'] ?? null, (string) $oauthData['nonce'])) {
                throw ValidationException::withMessages([
                    'auth' => 'Falha na validação de segurança da resposta do GOV.BR.',
                ]);
            }

            $govBrUser = $this->govBrService->obterUsuario($tokens['access_token']);
            $user = $this->authValidationService->validarOuFalhar($govBrUser);
            $plainTextToken = $user->createToken('govbr-login')->plainTextToken;

            $loginCode = $this->govBrService->gerarState();

            $hoje = now()->toDateString();

            $user->load([
                'perfisUsuario' => function ($q) use ($hoje) {
                    $q->where('ativo', true)
                        ->where(function ($subQ) use ($hoje) {
                            $subQ->whereNull('data_inicio_vigencia')
                                ->orWhereDate('data_inicio_vigencia', '<=', $hoje);
                        })
                        ->where(function ($subQ) use ($hoje) {
                            $subQ->whereNull('data_fim_vigencia')
                                ->orWhereDate('data_fim_vigencia', '>=', $hoje);
                        });
                },
                'perfisUsuario.perfil',
                'perfisUsuario.abrangencia.esfera',
                'perfisUsuario.abrangencia.uf',
                'perfisUsuario.abrangencia.municipio',
                'perfisUsuario.solicitacaoCadastroOrigem',
                'contextoAtivo.perfilUsuario.perfil.permissoes',
                'contextoAtivo.abrangencia.esfera',
                'contextoAtivo.abrangencia.uf',
                'contextoAtivo.abrangencia.municipio',
            ]);

            UsuarioContextoResolver::garantirContextoValido($user);

            $user->load([
                'contextoAtivo.perfilUsuario.perfil.permissoes',
                'contextoAtivo.abrangencia.esfera',
                'contextoAtivo.abrangencia.uf',
                'contextoAtivo.abrangencia.municipio',
            ]);

            $userResource = UsuarioResource::make($user);
            $userData = json_decode($userResource->toJson(), true);

            Cache::put(
                $this->loginCodeCacheKey($loginCode),
                [
                    'token'   => $plainTextToken,
                    'usuario' => $userData,
                ],
                now()->addSeconds((int) config('govbr.login_code_ttl_seconds', 120))
            );

            $this->auditLogService->log('auth.callback', $user->id, [
                'sub'        => $govBrUser->sub,
                'login_code' => $loginCode,
            ]);
            $this->auditLogService->log('auth.login', $user->id, [
                'provider' => 'govbr',
            ], TipoAuditoria::LOGIN->name);

            return $this->redirectToFrontend([
                'govbr_login_code' => $loginCode,
            ]);
        } catch (ValidationException $e) {
            $mensagem = $e->errors()['auth'][0] ?? 'Não foi possível autenticar com GOV.BR.';
            $this->auditLogService->log('auth.callback_failed', null, [
                'message' => $mensagem,
            ]);

            $params = ['govbr_error' => $mensagem];

            if ($govBrUser && $mensagem === 'Solicitar acesso e aguardar avaliação') {
                $cpf = preg_replace('/\D/', '', (string) ($govBrUser->cpf ?? $govBrUser->sub));
                $params['govbr_nome'] = $govBrUser->name ?? '';
                $params['govbr_cpf'] = $cpf;
                $params['govbr_email'] = $govBrUser->email ?? '';
            }

            return $this->redirectToFrontend($params);
        } catch (Throwable $e) {
            report($e);
            $this->auditLogService->log('auth.callback_failed', null, [
                'message' => 'Erro interno no callback GOV.BR.',
            ]);

            return $this->redirectToFrontend([
                'govbr_error' => 'Erro interno ao concluir o login GOV.BR.',
            ]);
        }
    }

    #[OA\Post(
        path: '/api/auth/exchange',
        description: 'Envia o `govbr_login_code` recebido no fragmento da URL após o redirect do callback.',
        summary: 'Troca código de login por token Sanctum',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['code'],
                properties: [
                    new OA\Property(property: 'code', type: 'string', description: 'Código descartável gerado após callback bem-sucedido'),
                ]
            )
        ),
        tags: ['Autenticação'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Token e usuário seguro',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'token', type: 'string'),
                        new OA\Property(property: 'user', ref: '#/components/schemas/UserResource'),
                    ]
                )
            ),
            new OA\Response(response: 422, description: 'Código inválido ou expirado'),
            new OA\Response(response: 429, description: 'Limite de requisições'),
        ]
    )]
    public function exchange(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'code' => ['required', 'string'],
        ]);

        $data = Cache::pull($this->loginCodeCacheKey($payload['code']));
        if (! is_array($data) || empty($data['token']) || empty($data['usuario'])) {
            throw ApiException::unprocessable('Código de autenticação GOV.BR inválido ou expirado.');
        }

        return response()->json([
            'token'   => $data['token'],
            'usuario' => $data['usuario'],
        ]);
    }

    #[OA\Post(
        path: '/api/auth/logout',
        summary: 'Encerra sessão Sanctum',
        security: [['BearerAuth' => []]],
        tags: ['Autenticação'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Logout realizado',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Logout realizado com sucesso.'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Não autenticado'),
            new OA\Response(response: 429, description: 'Limite de requisições'),
        ]
    )]
    public function logout(): JsonResponse
    {
        $user = auth()->user();
        $user->currentAccessToken()?->delete();

        $this->auditLogService->log(
            'auth.logout',
            $user->id,
            ['provider' => 'sanctum'],
            TipoAuditoria::LOGOUT->name
        );

        return response()->json(['message' => 'Logout realizado com sucesso.']);
    }

    private function garantirConfiguracao(): void
    {
        foreach (['client_id', 'client_secret', 'redirect_uri', 'authorize_url', 'token_url', 'userinfo_url'] as $campo) {
            if (! config('govbr.'.$campo)) {
                throw ValidationException::withMessages([
                    'auth' => 'Configuração GOV.BR incompleta no ambiente.',
                ]);
            }
        }
    }

    private function redirectToFrontend(array $fragmentParams): RedirectResponse
    {
        $base = rtrim((string) config('govbr.frontend_url'), '/')
            .(string) config('govbr.frontend_login_path', '/login');

        return redirect()->away($base.'#'.http_build_query($fragmentParams));
    }

    private function oauthCacheKey(string $state): string
    {
        return 'govbr:oauth:'.$state;
    }

    private function gerarUrlDeAutorizacao(): string
    {
        $this->garantirConfiguracao();

        $state = $this->govBrService->gerarState();
        $nonce = $this->govBrService->gerarNonce();
        $codeVerifier = $this->govBrService->gerarCodeVerifier();
        $codeChallenge = $this->govBrService->gerarCodeChallenge($codeVerifier);

        Cache::put(
            $this->oauthCacheKey($state),
            [
                'nonce'         => $nonce,
                'code_verifier' => $codeVerifier,
            ],
            now()->addSeconds((int) config('govbr.oauth_ttl_seconds', 600))
        );

        $url = $this->govBrService->montarUrlAutorizacao($state, $nonce, $codeChallenge);

        $this->auditLogService->log('auth.redirect', null, ['state' => $state]);

        return $url;
    }

    private function loginCodeCacheKey(string $code): string
    {
        return 'govbr:login-code:'.$code;
    }
}
