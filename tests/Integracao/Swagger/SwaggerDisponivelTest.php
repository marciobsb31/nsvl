<?php

namespace Tests\Integracao\Swagger;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use Tests\Integracao\TestCase;

class SwaggerDisponivelTest extends TestCase
{
    #[Test]
    public function retorna_a_interface_do_swagger(): void
    {
        $response = $this->get('/api/docs');

        $response->assertOk();
        $response->assertSee('swagger-ui', false);
        $response->assertSee('NVSL API', false);
    }

    #[Test]
    public function documenta_todos_os_endpoints_http_da_aplicacao(): void
    {
        Artisan::call('l5-swagger:generate');

        $arquivo = storage_path('api-docs/api-docs.json');

        $this->assertFileExists($arquivo);

        $spec = json_decode((string) file_get_contents($arquivo), true, 512, JSON_THROW_ON_ERROR);
        $pathsDocumentados = array_keys($spec['paths'] ?? []);

        $pathsDaAplicacao = collect(Route::getRoutes()->getRoutes())
            ->filter(fn ($route) => str_starts_with($route->uri(), 'api/'))
            ->reject(fn ($route) => in_array($route->uri(), ['api/docs', 'api/oauth2-callback'], true))
            ->map(fn ($route) => '/' . $route->uri())
            ->unique()
            ->values()
            ->all();

        $faltantes = array_values(array_diff($pathsDaAplicacao, $pathsDocumentados));

        $this->assertSame([], $faltantes, 'Swagger nao documentou alguns endpoints da aplicacao.');
    }
}
