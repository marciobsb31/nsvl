<?php

namespace Tests\Unitario\Middleware;

use App\Http\Middleware\DisallowSwaggerInProduction;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\Unitario\TestCase;

class DisallowSwaggerInProductionTest extends TestCase
{
    #[Test]
    public function permite_continuar_fora_de_producao(): void
    {
        $this->app['env'] = 'local';

        $middleware = new DisallowSwaggerInProduction;
        $request = Request::create('/api/docs');

        $response = $middleware->handle($request, fn () => response('ok', 200));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('ok', $response->getContent());
    }

    #[Test]
    public function bloqueia_swagger_em_producao(): void
    {
        $this->app['env'] = 'production';

        $middleware = new DisallowSwaggerInProduction;
        $request = Request::create('/api/docs');

        $this->expectException(NotFoundHttpException::class);

        $middleware->handle($request, fn () => response('ok', 200));
    }

    #[Test]
    public function bloqueia_swagger_quando_o_ambiente_e_prod(): void
    {
        $this->app['env'] = 'prod';

        $middleware = new DisallowSwaggerInProduction;
        $request = Request::create('/api/docs');

        $this->expectException(NotFoundHttpException::class);

        $middleware->handle($request, fn () => response('ok', 200));
    }
}
