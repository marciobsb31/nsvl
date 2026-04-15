<?php

namespace Tests\Unitario\Providers;

use App\Models\SolicitacaoCadastro;
use App\Policies\SolicitacaoCadastroPolicy;
use App\Providers\AppServiceProvider;
use App\Services\Audit\AuditLogService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use PHPUnit\Framework\Attributes\Test;
use Tests\Unitario\TestCase;

class AppServiceProviderTest extends TestCase
{
    #[Test]
    public function registra_audit_log_service_como_singleton(): void
    {
        $provider = new AppServiceProvider($this->app);
        $provider->register();

        $servico1 = $this->app->make(AuditLogService::class);
        $servico2 = $this->app->make(AuditLogService::class);

        $this->assertSame($servico1, $servico2);
        $this->assertInstanceOf(AuditLogService::class, $servico1);
    }

    #[Test]
    public function registra_policy_e_forca_https_em_producao(): void
    {
        $provider = new AppServiceProvider($this->app);
        $provider->register();
        $this->app['env'] = 'production';

        URL::forceScheme(null);
        $provider->boot();

        $this->assertSame(
            SolicitacaoCadastroPolicy::class,
            Gate::getPolicyFor(SolicitacaoCadastro::class)::class,
        );
        $this->assertStringStartsWith('https://', URL::to('/teste'));
    }
}
