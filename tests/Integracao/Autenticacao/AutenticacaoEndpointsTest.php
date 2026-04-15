<?php

namespace Tests\Integracao\Autenticacao;

use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\Integracao\TestCase;

class AutenticacaoEndpointsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ThrottleRequests::class);
    }

    #[Test]
    public function emite_token_de_teste_para_usuario_fixture(): void
    {
        $response = $this->postJson('/api/auth/token-de-teste', [
            'perfil' => 'federal',
        ]);

        $response
            ->assertOk()
            ->assertJsonStructure([
                'token',
                'user' => ['id', 'name', 'sub', 'perfis_vigentes'],
            ])
            ->assertJsonPath('user.sub', self::SUB_FEDERAL);
    }

    #[Test]
    public function gera_url_de_redirect_do_govbr_quando_a_configuracao_esta_preenchida(): void
    {
        config()->set('govbr.client_id', 'cliente-teste');
        config()->set('govbr.client_secret', 'segredo-teste');
        config()->set('govbr.redirect_uri', 'http://localhost:8081/api/auth/redirect');
        config()->set('govbr.authorize_url', 'https://sso.exemplo.gov.br/authorize');
        config()->set('govbr.token_url', 'https://sso.exemplo.gov.br/token');
        config()->set('govbr.userinfo_url', 'https://sso.exemplo.gov.br/userinfo');

        $response = $this->getJson('/api/auth/url');

        $response->assertOk();
        $this->assertStringContainsString('https://sso.exemplo.gov.br/authorize', $response->json('url'));
        $this->assertStringContainsString('state=', $response->json('url'));
        $this->assertStringContainsString('code_challenge=', $response->json('url'));
    }

    #[Test]
    public function redirect_gov_redireciona_o_navegador_para_o_sso(): void
    {
        config()->set('govbr.client_id', 'cliente-teste');
        config()->set('govbr.client_secret', 'segredo-teste');
        config()->set('govbr.redirect_uri', 'http://localhost:8081/api/auth/redirect');
        config()->set('govbr.authorize_url', 'https://sso.exemplo.gov.br/authorize');
        config()->set('govbr.token_url', 'https://sso.exemplo.gov.br/token');
        config()->set('govbr.userinfo_url', 'https://sso.exemplo.gov.br/userinfo');

        $response = $this->get('/redirect-gov');

        $response->assertRedirect();
        $this->assertStringContainsString('https://sso.exemplo.gov.br/authorize', (string) $response->headers->get('Location'));
    }

    #[Test]
    public function rejeita_redirect_quando_a_configuracao_do_govbr_esta_incompleta(): void
    {
        config()->set('govbr.client_id', null);
        config()->set('govbr.client_secret', null);
        config()->set('govbr.redirect_uri', null);
        config()->set('govbr.authorize_url', null);
        config()->set('govbr.token_url', null);
        config()->set('govbr.userinfo_url', null);

        $response = $this->getJson('/api/auth/url');

        $response
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Configuração GOV.BR incompleta no ambiente.');
    }

    #[Test]
    public function callback_sem_parametros_redireciona_para_o_frontend_com_erro(): void
    {
        config()->set('govbr.client_id', 'cliente-teste');
        config()->set('govbr.client_secret', 'segredo-teste');
        config()->set('govbr.redirect_uri', 'http://localhost:8081/api/auth/redirect');
        config()->set('govbr.authorize_url', 'https://sso.exemplo.gov.br/authorize');
        config()->set('govbr.token_url', 'https://sso.exemplo.gov.br/token');
        config()->set('govbr.userinfo_url', 'https://sso.exemplo.gov.br/userinfo');
        config()->set('govbr.frontend_url', 'http://frontend.local');
        config()->set('govbr.frontend_login_path', '/login');

        $response = $this->get('/api/auth/redirect');

        $response->assertRedirect();
        $this->assertStringContainsString(
            'http://frontend.local/login#govbr_error=',
            (string) $response->headers->get('Location')
        );
    }

    #[Test]
    public function exchange_rejeita_codigo_invalido_ou_expirado(): void
    {
        Cache::forget('govbr:login-code:invalido');

        $response = $this->postJson('/api/auth/exchange', [
            'code' => 'invalido',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Código de autenticação GOV.BR inválido ou expirado.');
    }

    #[Test]
    public function exchange_retorna_token_e_usuario_quando_o_codigo_e_valido(): void
    {
        Cache::put('govbr:login-code:codigo-valido', [
            'token' => 'token-sanctum-teste',
            'user'  => [
                'id'   => 1,
                'name' => 'Maria Silva Federal',
                'sub'  => self::SUB_FEDERAL,
            ],
        ], now()->addMinute());

        $response = $this->postJson('/api/auth/exchange', [
            'code' => 'codigo-valido',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('token', 'token-sanctum-teste')
            ->assertJsonPath('user.sub', self::SUB_FEDERAL);
    }

    #[Test]
    public function realiza_logout_do_usuario_autenticado(): void
    {
        $this->autenticarComoFederal();

        $response = $this->postJson('/api/auth/logout');

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Logout realizado com sucesso.');
    }
}
