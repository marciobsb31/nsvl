<?php

namespace Tests\Unitario\Http\Controllers\Auth;

use App\Exceptions\ApiException;
use App\Http\Controllers\Auth\GovBrAuthController;
use App\Services\Audit\AuditLogService;
use App\Services\Auth\AuthValidationService;
use App\Services\Auth\GovBrService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use Tests\Unitario\TestCase;

class GovBrAuthControllerTest extends TestCase
{
    #[Test]
    public function redirect_gera_url_e_persistencia_temporaria_do_estado_oauth(): void
    {
        $this->configurarGovbr();

        $govbr = $this->createMock(GovBrService::class);
        $govbr->expects($this->once())->method('gerarState')->willReturn('estado-unitario');
        $govbr->expects($this->once())->method('gerarNonce')->willReturn('nonce-unitario');
        $govbr->expects($this->once())->method('gerarCodeVerifier')->willReturn('verifier-unitario');
        $govbr->expects($this->once())->method('gerarCodeChallenge')->with('verifier-unitario')->willReturn('challenge-unitario');
        $govbr->expects($this->once())->method('montarUrlAutorizacao')
            ->with('estado-unitario', 'nonce-unitario', 'challenge-unitario')
            ->willReturn('https://sso.exemplo.gov.br/authorize?...');

        $controller = new GovBrAuthController(
            $govbr,
            $this->createStub(AuthValidationService::class),
            $this->createStub(AuditLogService::class),
        );

        $response = $controller->redirect();

        $this->assertSame('https://sso.exemplo.gov.br/authorize?...', $response->getData(true)['url']);
        $this->assertSame([
            'nonce'         => 'nonce-unitario',
            'code_verifier' => 'verifier-unitario',
        ], Cache::get('govbr:oauth:estado-unitario'));
    }

    #[Test]
    public function redirect_gov_redireciona_o_navegador_para_a_url_gerada(): void
    {
        $this->configurarGovbr();

        $govbr = $this->createMock(GovBrService::class);
        $govbr->expects($this->once())->method('gerarState')->willReturn('estado-browser');
        $govbr->expects($this->once())->method('gerarNonce')->willReturn('nonce-browser');
        $govbr->expects($this->once())->method('gerarCodeVerifier')->willReturn('verifier-browser');
        $govbr->expects($this->once())->method('gerarCodeChallenge')->with('verifier-browser')->willReturn('challenge-browser');
        $govbr->expects($this->once())->method('montarUrlAutorizacao')
            ->with('estado-browser', 'nonce-browser', 'challenge-browser')
            ->willReturn('https://sso.exemplo.gov.br/authorize?browser=1');

        $controller = new GovBrAuthController(
            $govbr,
            $this->createStub(AuthValidationService::class),
            $this->createStub(AuditLogService::class),
        );

        $response = $controller->redirectGov();

        $this->assertSame('https://sso.exemplo.gov.br/authorize?browser=1', $response->getTargetUrl());
    }

    #[Test]
    public function exchange_retorna_payload_quando_o_codigo_existe_no_cache(): void
    {
        Cache::put('govbr:login-code:codigo-unitario', [
            'token' => 'token-unitario',
            'user'  => ['sub' => '11144477735'],
        ], now()->addMinute());

        $response = $this->controller()->exchange(Request::create('/api/auth/exchange', 'POST', [
            'code' => 'codigo-unitario',
        ]));

        $this->assertSame('token-unitario', $response->getData(true)['token']);
        $this->assertSame('11144477735', $response->getData(true)['user']['sub']);
    }

    #[Test]
    public function exchange_lanca_erro_quando_o_codigo_nao_existe_no_cache(): void
    {
        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Código de autenticação GOV.BR inválido ou expirado.');

        $this->controller()->exchange(Request::create('/api/auth/exchange', 'POST', [
            'code' => 'codigo-ausente',
        ]));
    }

    #[Test]
    public function logout_retorna_sucesso_mesmo_sem_usuario_autenticado(): void
    {
        $request = Request::create('/api/auth/logout', 'POST');
        $request->setUserResolver(fn () => null);

        $response = $this->controller()->logout($request);

        $this->assertSame('Logout realizado com sucesso.', $response->getData(true)['message']);
    }

    #[Test]
    public function validar_configuracao_lanca_erro_quando_ha_campo_obrigatorio_ausente(): void
    {
        config()->set('govbr.client_id', null);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Configuração GOV.BR incompleta no ambiente.');

        $this->invocarMetodoPrivado('garantirConfiguracao');
    }

    #[Test]
    public function monta_chaves_de_cache_e_redirect_para_o_frontend(): void
    {
        config()->set('govbr.frontend_url', 'http://frontend.local/');
        config()->set('govbr.frontend_login_path', '/login');

        $oauthKey = $this->invocarMetodoPrivado('oauthCacheKey', 'estado-1');
        $loginKey = $this->invocarMetodoPrivado('loginCodeCacheKey', 'codigo-1');
        $redirect = $this->invocarMetodoPrivado('redirectToFrontend', [
            'govbr_error' => 'falha',
            'govbr_nome'  => 'Maria',
        ]);

        $this->assertSame('govbr:oauth:estado-1', $oauthKey);
        $this->assertSame('govbr:login-code:codigo-1', $loginKey);
        $this->assertStringContainsString(
            'http://frontend.local/login#govbr_error=falha&govbr_nome=Maria',
            $redirect->getTargetUrl(),
        );
    }

    #[Test]
    public function callback_redireciona_com_erro_quando_o_provedor_retorna_falha(): void
    {
        $this->configurarGovbr();

        $response = $this->controller()->callback(Request::create('/api/auth/redirect', 'GET', [
            'error'             => 'access_denied',
            'error_description' => 'Usuário cancelou o login',
        ]));

        $this->assertStringContainsString(
            'govbr_error=Falha+no+retorno+do+GOV.BR%3A+Usu%C3%A1rio+cancelou+o+login',
            $response->getTargetUrl(),
        );
    }

    #[Test]
    public function callback_redireciona_com_erro_quando_state_ou_code_nao_foram_recebidos(): void
    {
        $this->configurarGovbr();

        $response = $this->controller()->callback(Request::create('/api/auth/redirect', 'GET'));

        $this->assertStringContainsString(
            'govbr_error=Resposta+do+GOV.BR+incompleta.+O+c%C3%B3digo+de+autoriza%C3%A7%C3%A3o+n%C3%A3o+foi+recebido.',
            $response->getTargetUrl(),
        );
    }

    #[Test]
    public function callback_redireciona_com_erro_quando_o_estado_expirou(): void
    {
        $this->configurarGovbr();

        $response = $this->controller()->callback(Request::create('/api/auth/redirect', 'GET', [
            'state' => 'estado-ausente',
            'code'  => 'codigo-ausente',
        ]));

        $this->assertStringContainsString(
            'govbr_error=O+estado+da+autentica%C3%A7%C3%A3o+GOV.BR+expirou+ou+%C3%A9+inv%C3%A1lido.',
            $response->getTargetUrl(),
        );
    }

    private function controller(): GovBrAuthController
    {
        return new GovBrAuthController(
            $this->createStub(GovBrService::class),
            $this->createStub(AuthValidationService::class),
            $this->createStub(AuditLogService::class),
        );
    }

    private function invocarMetodoPrivado(string $metodo, mixed ...$argumentos): mixed
    {
        $controller = $this->controller();

        $closure = \Closure::bind(
            fn (...$args) => $this->{$metodo}(...$args),
            $controller,
            $controller
        );

        return $closure(...$argumentos);
    }

    private function configurarGovbr(): void
    {
        config()->set('govbr.client_id', 'cliente-teste');
        config()->set('govbr.client_secret', 'segredo-teste');
        config()->set('govbr.redirect_uri', 'http://localhost:8081/api/auth/redirect');
        config()->set('govbr.authorize_url', 'https://sso.exemplo.gov.br/authorize');
        config()->set('govbr.token_url', 'https://sso.exemplo.gov.br/token');
        config()->set('govbr.userinfo_url', 'https://sso.exemplo.gov.br/userinfo');
        config()->set('govbr.frontend_url', 'http://frontend.local');
        config()->set('govbr.frontend_login_path', '/login');
    }
}
