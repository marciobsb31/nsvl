<?php

namespace App\Providers;

use App\Services\Audit\AuditLogService;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use OpenApi\Attributes as OA;

/**
 * @OA\Info(
 *     version="1.0.0",
 *     title="NVSL API",
 *     description="API do Sistema NVSL — Plataforma de Gestão com autenticação GOV.BR",
 *     @OA\Contact(email="suporte@nvsl.gov.br"),
 *     @OA\License(name="Proprietário")
 * )
 *
 * @OA\Server(url=L5_SWAGGER_CONST_HOST, description="Servidor local de desenvolvimento")
 *
 * @OA\SecurityScheme(
 *     securityScheme="BearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="Sanctum",
 *     description="Token Sanctum obtido após login GOV.BR"
 * )
 */
class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Registra AuditLogService como singleton (compartilha o mesmo Request)
        $this->app->singleton(AuditLogService::class, function ($app) {
            return new AuditLogService($app['request']);
        });
    }

    public function boot(): void
    {
        // Força HTTPS em produção
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }
}
