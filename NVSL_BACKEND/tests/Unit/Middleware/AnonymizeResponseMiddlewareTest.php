<?php

namespace Tests\Unit\Middleware;

use App\Http\Middleware\AnonymizeResponseMiddleware;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class AnonymizeResponseMiddlewareTest extends TestCase
{
    private AnonymizeResponseMiddleware $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new AnonymizeResponseMiddleware();
    }

    #[Test]
    public function remove_cpf_hash_da_resposta(): void
    {
        $request = Request::create('/test');
        $response = $this->middleware->handle($request, function () {
            return new JsonResponse(['name' => 'João', 'cpf_hash' => 'abc123']);
        });

        $data = json_decode($response->getContent(), true);
        $this->assertArrayNotHasKey('cpf_hash', $data);
        $this->assertArrayHasKey('name', $data);
    }

    #[Test]
    public function preserva_cpf_mascarado_em_dados_aninhados(): void
    {
        $request = Request::create('/test');
        $response = $this->middleware->handle($request, function () {
            return new JsonResponse([
                'data' => [
                    ['id' => 1, 'nome' => 'Maria', 'cpf' => '529.982.247-25'],
                ],
            ]);
        });

        $data = json_decode($response->getContent(), true);
        $this->assertSame('529.982.247-25', $data['data'][0]['cpf']);
    }

    #[Test]
    public function remove_campos_sensiveis_aninhados(): void
    {
        $request = Request::create('/test');
        $response = $this->middleware->handle($request, function () {
            return new JsonResponse([
                'user' => [
                    'name' => 'João',
                    'password' => 'secret',
                    'remember_token' => 'token123',
                ],
            ]);
        });

        $data = json_decode($response->getContent(), true);
        $this->assertArrayNotHasKey('password', $data['user']);
        $this->assertArrayNotHasKey('remember_token', $data['user']);
        $this->assertEquals('João', $data['user']['name']);
    }

    #[Test]
    public function nao_altera_resposta_nao_json(): void
    {
        $request = Request::create('/test');
        $response = $this->middleware->handle($request, function () {
            return response('texto simples', 200, ['Content-Type' => 'text/plain']);
        });

        $this->assertEquals('texto simples', $response->getContent());
    }

    #[Test]
    public function remove_govbr_access_token(): void
    {
        $request = Request::create('/test');
        $response = $this->middleware->handle($request, function () {
            return new JsonResponse(['govbr_access_token' => 'secret-token', 'data' => 'ok']);
        });

        $data = json_decode($response->getContent(), true);
        $this->assertArrayNotHasKey('govbr_access_token', $data);
        $this->assertEquals('ok', $data['data']);
    }

    #[Test]
    public function remove_client_secret(): void
    {
        $request = Request::create('/test');
        $response = $this->middleware->handle($request, function () {
            return new JsonResponse(['client_secret' => 'my-secret', 'status' => 'ok']);
        });

        $data = json_decode($response->getContent(), true);
        $this->assertArrayNotHasKey('client_secret', $data);
    }
}
