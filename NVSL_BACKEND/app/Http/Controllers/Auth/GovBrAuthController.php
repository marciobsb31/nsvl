<?php

namespace App\Http\Controllers\Auth;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Services\Audit\AuditLogService;
use App\Services\Auth\AuthValidationService;
use App\Services\Auth\GovBrService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Throwable;

class GovBrAuthController extends Controller
{
    public function __construct(
        private readonly GovBrService $govBrService,
        private readonly AuthValidationService $authValidationService,
        private readonly AuditLogService $auditLogService,
    ) {}

    public function redirect(): JsonResponse
    {
        $this->garantirConfiguracao();

        $state = $this->govBrService->gerarState();
        $nonce = $this->govBrService->gerarNonce();
        $codeVerifier = $this->govBrService->gerarCodeVerifier();
        $codeChallenge = $this->govBrService->gerarCodeChallenge($codeVerifier);

        Cache::put(
            $this->oauthCacheKey($state),
            [
                'nonce' => $nonce,
                'code_verifier' => $codeVerifier,
            ],
            now()->addSeconds((int) config('govbr.oauth_ttl_seconds', 600))
        );

        $url = $this->govBrService->montarUrlAutorizacao($state, $nonce, $codeChallenge);

        $this->auditLogService->log('auth.redirect', null, ['state' => $state]);

        return response()->json(['url' => $url]);
    }

    public function callback(Request $request): RedirectResponse
    {
        $govBrUser = null;

        try {
            $this->garantirConfiguracao();

            if ($request->filled('error')) {
                $descricao = (string) ($request->input('error_description') ?: $request->input('error'));
                throw ValidationException::withMessages([
                    'auth' => 'Falha no retorno do GOV.BR: ' . $descricao,
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
            if (!is_array($oauthData)) {
                throw ValidationException::withMessages([
                    'auth' => 'O estado da autenticação GOV.BR expirou ou é inválido.',
                ]);
            }

            $tokens = $this->govBrService->trocarCodePorToken($code, (string) $oauthData['code_verifier']);

            if (!$this->govBrService->validarNonce($tokens['id_token'] ?? null, (string) $oauthData['nonce'])) {
                throw ValidationException::withMessages([
                    'auth' => 'Falha na validação de segurança da resposta do GOV.BR.',
                ]);
            }

            $govBrUser = $this->govBrService->obterUsuario($tokens['access_token']);
            $user = $this->authValidationService->validarOuFalhar($govBrUser);
            $plainTextToken = $user->createToken('govbr-login')->plainTextToken;

            $loginCode = $this->govBrService->gerarState();
            Cache::put(
                $this->loginCodeCacheKey($loginCode),
                [
                    'token' => $plainTextToken,
                    'user' => $user->toSafeArray(),
                ],
                now()->addSeconds((int) config('govbr.login_code_ttl_seconds', 120))
            );

            $this->auditLogService->log('auth.callback', $user->id, [
                'sub' => $govBrUser->sub,
                'login_code' => $loginCode,
            ]);
            $this->auditLogService->log('auth.login', $user->id, [
                'provider' => 'govbr',
            ], AuditLog::TIPO_LOGIN);

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

    public function exchange(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'code' => ['required', 'string'],
        ]);

        $data = Cache::pull($this->loginCodeCacheKey($payload['code']));
        if (!is_array($data) || empty($data['token']) || empty($data['user'])) {
            throw ApiException::unprocessable('Código de autenticação GOV.BR inválido ou expirado.');
        }

        return response()->json([
            'token' => $data['token'],
            'user' => $data['user'],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();
        $request->user()?->currentAccessToken()?->delete();

        $this->auditLogService->log('auth.logout', $user?->id, [
            'provider' => 'sanctum',
        ], AuditLog::TIPO_LOGOUT);

        return response()->json([
            'message' => 'Logout realizado com sucesso.',
        ]);
    }

    private function garantirConfiguracao(): void
    {
        foreach (['client_id', 'client_secret', 'redirect_uri', 'authorize_url', 'token_url', 'userinfo_url'] as $campo) {
            if (!config('govbr.' . $campo)) {
                throw ValidationException::withMessages([
                    'auth' => 'Configuração GOV.BR incompleta no ambiente.',
                ]);
            }
        }
    }

    private function redirectToFrontend(array $fragmentParams): RedirectResponse
    {
        $base = rtrim((string) config('govbr.frontend_url'), '/')
            . (string) config('govbr.frontend_login_path', '/login');

        return redirect()->away($base . '#' . http_build_query($fragmentParams));
    }

    private function oauthCacheKey(string $state): string
    {
        return 'govbr:oauth:' . $state;
    }

    private function loginCodeCacheKey(string $code): string
    {
        return 'govbr:login-code:' . $code;
    }
}
