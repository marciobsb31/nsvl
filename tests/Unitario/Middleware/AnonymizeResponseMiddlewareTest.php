<?php

namespace Tests\Unitario\Middleware;

use App\Http\Middleware\AnonymizeResponseMiddleware;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\Unitario\TestCase;

class AnonymizeResponseMiddlewareTest extends TestCase
{
    private AnonymizeResponseMiddleware $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new AnonymizeResponseMiddleware();
    }

    #[Test]
    public function remove_campos_sensiveis_da_resposta_json(): void
    {
        $request = Request::create('/teste');
        $response = $this->middleware->handle($request, fn () => new JsonResponse([
            'nome' => 'Joao',
            'password' => 'segredo',
            'client_secret' => 'token-interno',
        ]));

        $data = json_decode($response->getContent(), true);

        $this->assertSame('Joao', $data['nome']);
        $this->assertArrayNotHasKey('password', $data);
        $this->assertArrayNotHasKey('client_secret', $data);
    }

    #[Test]
    public function ignora_resposta_nao_json_e_remove_campos_sensiveis_em_estrutura_aninhada(): void
    {
        $request = Request::create('/teste');

        $plainResponse = response('texto puro', 200, ['Content-Type' => 'text/plain']);
        $unchanged = $this->middleware->handle($request, fn () => $plainResponse);
        $this->assertSame('texto puro', $unchanged->getContent());

        $jsonResponse = $this->middleware->handle($request, fn () => new JsonResponse([
            'usuario' => [
                'nome' => 'Maria',
                'remember_token' => 'segredo',
                'dados' => [
                    'govbr_refresh_token' => 'refresh',
                ],
            ],
        ]));

        $data = json_decode($jsonResponse->getContent(), true);

        $this->assertSame('Maria', $data['usuario']['nome']);
        $this->assertArrayNotHasKey('remember_token', $data['usuario']);
        $this->assertArrayNotHasKey('govbr_refresh_token', $data['usuario']['dados']);
    }
}
