<?php

namespace Tests\Unitario\Services\Auth;

use App\DTOs\Auth\GovBrUserDTO;
use App\Services\Auth\GovBrService;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\Unitario\TestCase;

class GovBrServiceTest extends TestCase
{
    private GovBrService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new GovBrService();

        config()->set('govbr.client_id', 'cliente-teste');
        config()->set('govbr.client_secret', 'segredo-teste');
        config()->set('govbr.redirect_uri', 'http://localhost:8081/redirect-gov');
        config()->set('govbr.authorize_url', 'https://sso.exemplo.gov.br/authorize');
        config()->set('govbr.token_url', 'https://sso.exemplo.gov.br/token');
        config()->set('govbr.userinfo_url', 'https://sso.exemplo.gov.br/userinfo');
        config()->set('govbr.scopes', ['openid', 'email', 'profile']);
    }

    #[Test]
    public function gera_code_challenge_url_safe(): void
    {
        $challenge = $this->service->gerarCodeChallenge(str_repeat('a', 96));

        $this->assertNotSame('', $challenge);
        $this->assertStringNotContainsString('+', $challenge);
        $this->assertStringNotContainsString('/', $challenge);
        $this->assertStringNotContainsString('=', $challenge);
    }

    #[Test]
    public function gera_identificadores_aleatorios_com_tamanho_esperado(): void
    {
        $this->assertSame(64, strlen($this->service->gerarState()));
        $this->assertSame(64, strlen($this->service->gerarNonce()));
        $this->assertSame(96, strlen($this->service->gerarCodeVerifier()));
    }

    #[Test]
    public function monta_url_de_autorizacao_com_parametros_esperados(): void
    {
        $url = $this->service->montarUrlAutorizacao('estado', 'nonce', 'challenge');

        $this->assertStringContainsString('https://sso.exemplo.gov.br/authorize?', $url);
        $this->assertStringContainsString('client_id=cliente-teste', $url);
        $this->assertStringContainsString('redirect_uri=http%3A%2F%2Flocalhost%3A8081%2Fredirect-gov', $url);
        $this->assertStringContainsString('scope=openid+email+profile', $url);
        $this->assertStringContainsString('state=estado', $url);
        $this->assertStringContainsString('nonce=nonce', $url);
        $this->assertStringContainsString('code_challenge=challenge', $url);
    }

    #[Test]
    public function troca_code_por_token_com_sucesso(): void
    {
        Http::fake([
            'https://sso.exemplo.gov.br/token' => Http::response([
                'access_token' => 'token-de-acesso',
                'id_token' => 'id-token',
                'token_type' => 'Bearer',
                'expires_in' => 300,
            ], 200),
        ]);

        $tokens = $this->service->trocarCodePorToken('codigo', 'verifier');

        $this->assertSame('token-de-acesso', $tokens['access_token']);
        $this->assertSame('id-token', $tokens['id_token']);
        $this->assertSame('Bearer', $tokens['token_type']);
        $this->assertSame(300, $tokens['expires_in']);
    }

    #[Test]
    public function falha_ao_trocar_code_quando_o_http_retorna_erro(): void
    {
        Http::fake([
            'https://sso.exemplo.gov.br/token' => Http::response(['erro' => 'falha'], 500),
        ]);

        $this->expectException(RequestException::class);

        $this->service->trocarCodePorToken('codigo', 'verifier');
    }

    #[Test]
    public function falha_ao_trocar_code_quando_o_payload_nao_tem_access_token(): void
    {
        Http::fake([
            'https://sso.exemplo.gov.br/token' => Http::response([
                'token_type' => 'Bearer',
            ], 200),
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Resposta inválida ao obter token do GOV.BR.');

        $this->service->trocarCodePorToken('codigo', 'verifier');
    }

    #[Test]
    public function obtem_usuario_e_retorna_dto(): void
    {
        Http::fake([
            'https://sso.exemplo.gov.br/userinfo' => Http::response([
                'sub' => '12345678901',
                'name' => 'Maria da Silva',
                'email' => 'maria@exemplo.gov.br',
            ], 200),
        ]);

        $dto = $this->service->obterUsuario('token-de-acesso');

        $this->assertInstanceOf(GovBrUserDTO::class, $dto);
        $this->assertSame('12345678901', $dto->sub);
        $this->assertSame('Maria da Silva', $dto->name);
    }

    #[Test]
    public function falha_ao_obter_usuario_quando_http_retorna_erro(): void
    {
        Http::fake([
            'https://sso.exemplo.gov.br/userinfo' => Http::response(['erro' => 'falha'], 401),
        ]);

        $this->expectException(RequestException::class);

        $this->service->obterUsuario('token-de-acesso');
    }

    #[Test]
    public function falha_ao_obter_usuario_quando_payload_e_invalido(): void
    {
        Http::fake([
            'https://sso.exemplo.gov.br/userinfo' => Http::response([
                'sub' => '12345678901',
            ], 200),
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Resposta inválida do endpoint userinfo do GOV.BR.');

        $this->service->obterUsuario('token-de-acesso');
    }

    #[Test]
    public function valida_nonce_com_jwt_valido_e_compara_o_valor_esperado(): void
    {
        $jwt = $this->jwtComPayload(['nonce' => 'nonce-esperado']);

        $this->assertTrue($this->service->validarNonce($jwt, 'nonce-esperado'));
        $this->assertFalse($this->service->validarNonce($jwt, 'outro-nonce'));
    }

    #[Test]
    public function considera_nonce_valido_quando_id_token_nao_e_informado(): void
    {
        $this->assertTrue($this->service->validarNonce(null, 'qualquer'));
    }

    #[Test]
    public function considera_nonce_invalido_quando_jwt_nao_pode_ser_decodificado(): void
    {
        $this->assertFalse($this->service->validarNonce('jwt-invalido', 'nonce'));
    }

    private function jwtComPayload(array $payload): string
    {
        $header = rtrim(strtr(base64_encode(json_encode(['alg' => 'none'], JSON_THROW_ON_ERROR)), '+/', '-_'), '=');
        $body = rtrim(strtr(base64_encode(json_encode($payload, JSON_THROW_ON_ERROR)), '+/', '-_'), '=');

        return $header . '.' . $body . '.assinatura';
    }
}
