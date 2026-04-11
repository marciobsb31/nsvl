<?php

namespace App\Providers;

use App\Models\SolicitacaoCadastro;
use App\Policies\SolicitacaoCadastroPolicy;
use App\Services\Audit\AuditLogService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
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
        Gate::policy(SolicitacaoCadastro::class, SolicitacaoCadastroPolicy::class);

        // Força HTTPS em produção
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }
}
