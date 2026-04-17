<?php

namespace Tests\Integracao\Autenticacao;

use App\DTOs\Auth\GovBrUserDTO;
use App\Models\Usuario;
use App\Services\Auth\AuthValidationService;
use App\Services\Auth\GovBrService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\Integracao\TestCase;

class AutenticacaoCallbackTest extends TestCase
{
    #[Test]
    public function conclui_callback_com_sucesso_e_redireciona_com_login_code(): void
    {
        $this->configurarGovbr();

        $usuario = Usuario::query()->where('govbr_sub', self::SUB_FEDERAL)->firstOrFail();

        Cache::put('govbr:oauth:estado-ok', [
            'nonce'         => 'nonce-ok',
            'code_verifier' => 'verifier-ok',
        ], now()->addMinute());

        $govBrService = Mockery::mock(GovBrService::class);
        $govBrService->shouldReceive('trocarCodePorToken')
            ->once()
            ->with('codigo-ok', 'verifier-ok')
            ->andReturn([
                'access_token' => 'token-govbr',
                'id_token'     => 'id-token',
            ]);
        $govBrService->shouldReceive('validarNonce')
            ->once()
            ->with('id-token', 'nonce-ok')
            ->andReturn(true);
        $govBrService->shouldReceive('obterUsuario')
            ->once()
            ->with('token-govbr')
            ->andReturn(new GovBrUserDTO(
                sub: self::SUB_FEDERAL,
                name: 'Maria Silva Federal',
                email: 'maria.federal@ministerio.gov.br',
                cpf: self::SUB_FEDERAL,
            ));
        $govBrService->shouldReceive('gerarState')
            ->once()
            ->andReturn('login-code-ok');

        $authValidationService = Mockery::mock(AuthValidationService::class);
        $authValidationService->shouldReceive('validarOuFalhar')
            ->once()
            ->andReturn($usuario);

        $this->app->instance(GovBrService::class, $govBrService);
        $this->app->instance(AuthValidationService::class, $authValidationService);

        $response = $this->get('/api/auth/redirect?state=estado-ok&code=codigo-ok');

        $response->assertRedirect('http://frontend.local/login#govbr_login_code=login-code-ok');

        $this->assertIsArray(Cache::get('govbr:login-code:login-code-ok'));
    }

    #[Test]
    public function redireciona_com_dados_do_usuario_quando_auth_validation_indica_nova_solicitacao(): void
    {
        $this->configurarGovbr();

        Cache::put('govbr:oauth:estado-pendente', [
            'nonce'         => 'nonce-pendente',
            'code_verifier' => 'verifier-pendente',
        ], now()->addMinute());

        $govBrService = Mockery::mock(GovBrService::class);
        $govBrService->shouldReceive('trocarCodePorToken')
            ->once()
            ->andReturn([
                'access_token' => 'token-govbr',
                'id_token'     => 'id-token',
            ]);
        $govBrService->shouldReceive('validarNonce')
            ->once()
            ->andReturn(true);
        $govBrService->shouldReceive('obterUsuario')
            ->once()
            ->andReturn(new GovBrUserDTO(
                sub: '44455566677',
                name: 'Novo Usuário',
                email: 'novo.usuario@teste.gov.br',
                cpf: '44455566677',
            ));

        $authValidationService = Mockery::mock(AuthValidationService::class);
        $authValidationService->shouldReceive('validarOuFalhar')
            ->once()
            ->andThrow(ValidationException::withMessages([
                'auth' => 'Solicitar acesso e aguardar avaliação',
            ]));

        $this->app->instance(GovBrService::class, $govBrService);
        $this->app->instance(AuthValidationService::class, $authValidationService);

        $response = $this->get('/api/auth/redirect?state=estado-pendente&code=codigo-pendente');

        $location = (string) $response->headers->get('Location');

        $response->assertRedirect();
        $this->assertStringContainsString('govbr_error=Solicitar+acesso+e+aguardar+avalia%C3%A7%C3%A3o', $location);
        $this->assertStringContainsString('govbr_nome=Novo+Usu%C3%A1rio', $location);
        $this->assertStringContainsString('govbr_cpf=44455566677', $location);
        $this->assertStringContainsString('govbr_email=novo.usuario%40teste.gov.br', $location);
    }

    #[Test]
    public function redireciona_para_a_tela_de_solicitacao_quando_o_fluxo_govbr_for_solicitacao(): void
    {
        $this->configurarGovbr();

        Cache::put('govbr:oauth:estado-solicitacao', [
            'flow'          => 'solicitacao',
            'nonce'         => 'nonce-solicitacao',
            'code_verifier' => 'verifier-solicitacao',
        ], now()->addMinute());

        $govBrService = Mockery::mock(GovBrService::class);
        $govBrService->shouldReceive('trocarCodePorToken')
            ->once()
            ->andReturn([
                'access_token' => 'token-govbr',
                'id_token'     => 'id-token',
            ]);
        $govBrService->shouldReceive('validarNonce')
            ->once()
            ->andReturn(true);
        $govBrService->shouldReceive('obterUsuario')
            ->once()
            ->andReturn(new GovBrUserDTO(
                sub: '99988877766',
                name: 'Solicitante GovBr',
                email: 'solicitante@teste.gov.br',
                cpf: '99988877766',
            ));

        $authValidationService = Mockery::mock(AuthValidationService::class);
        $authValidationService->shouldReceive('validarSolicitacaoOuFalhar')
            ->once()
            ->andReturn([
                'nome'  => 'Solicitante GovBr',
                'cpf'   => '99988877766',
                'email' => 'solicitante@teste.gov.br',
            ]);

        $this->app->instance(GovBrService::class, $govBrService);
        $this->app->instance(AuthValidationService::class, $authValidationService);

        $response = $this->get('/api/auth/redirect?state=estado-solicitacao&code=codigo-solicitacao');

        $location = (string) $response->headers->get('Location');

        $response->assertRedirect();
        $this->assertStringContainsString('govbr_flow=solicitacao', $location);
        $this->assertStringContainsString('govbr_nome=Solicitante+GovBr', $location);
        $this->assertStringContainsString('govbr_cpf=99988877766', $location);
        $this->assertStringContainsString('govbr_email=solicitante%40teste.gov.br', $location);
    }

    #[Test]
    public function redireciona_com_erro_quando_o_estado_expira_ou_e_invalido(): void
    {
        $this->configurarGovbr();

        Cache::forget('govbr:oauth:estado-ausente');

        $response = $this->get('/api/auth/redirect?state=estado-ausente&code=codigo-ausente');

        $response->assertRedirect();
        $this->assertStringContainsString(
            'govbr_error=O+estado+da+autentica%C3%A7%C3%A3o+GOV.BR+expirou+ou+%C3%A9+inv%C3%A1lido.',
            (string) $response->headers->get('Location')
        );
    }

    #[Test]
    public function redireciona_com_erro_quando_o_nonce_e_invalido(): void
    {
        $this->configurarGovbr();

        Cache::put('govbr:oauth:estado-nonce', [
            'nonce'         => 'nonce-esperado',
            'code_verifier' => 'verifier-nonce',
        ], now()->addMinute());

        $govBrService = Mockery::mock(GovBrService::class);
        $govBrService->shouldReceive('trocarCodePorToken')
            ->once()
            ->andReturn([
                'access_token' => 'token-govbr',
                'id_token'     => 'id-token',
            ]);
        $govBrService->shouldReceive('validarNonce')
            ->once()
            ->with('id-token', 'nonce-esperado')
            ->andReturn(false);

        $this->app->instance(GovBrService::class, $govBrService);

        $response = $this->get('/api/auth/redirect?state=estado-nonce&code=codigo-nonce');

        $response->assertRedirect();
        $this->assertStringContainsString(
            'govbr_error=Falha+na+valida%C3%A7%C3%A3o+de+seguran%C3%A7a+da+resposta+do+GOV.BR.',
            (string) $response->headers->get('Location')
        );
    }

    #[Test]
    public function redireciona_com_erro_interno_quando_ocorre_falha_inesperada(): void
    {
        $this->configurarGovbr();

        Cache::put('govbr:oauth:estado-interno', [
            'nonce'         => 'nonce-interno',
            'code_verifier' => 'verifier-interno',
        ], now()->addMinute());

        $govBrService = Mockery::mock(GovBrService::class);
        $govBrService->shouldReceive('trocarCodePorToken')
            ->once()
            ->andThrow(new \RuntimeException('falha inesperada'));

        $this->app->instance(GovBrService::class, $govBrService);

        $response = $this->get('/api/auth/redirect?state=estado-interno&code=codigo-interno');

        $response->assertRedirect();
        $this->assertStringContainsString(
            'govbr_error=Erro+interno+ao+concluir+o+login+GOV.BR.',
            (string) $response->headers->get('Location')
        );
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
