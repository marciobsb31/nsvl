<?php

namespace Tests\Integracao\Sistema;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\Integracao\TestCase;

class HealthCheckTest extends TestCase
{
    #[Test]
    public function retorna_status_ok_no_endpoint_de_saude(): void
    {
        $response = $this->getJson('/api/health');

        $response
            ->assertOk()
            ->assertJson([
                'status' => 'ok',
                'database' => 'ok',
                'cache' => 'ok',
            ]);
    }

    #[Test]
    public function retorna_status_degradado_quando_o_banco_falha(): void
    {
        DB::shouldReceive('connection')
            ->once()
            ->andThrow(new \Exception('db indisponivel'));
        Cache::shouldReceive('put')->once();
        Cache::shouldReceive('forget')->once();

        $response = $this->getJson('/api/health');

        $response
            ->assertStatus(503)
            ->assertJson([
                'status' => 'degraded',
                'database' => 'error',
                'cache' => 'ok',
            ]);
    }

    #[Test]
    public function retorna_status_degradado_quando_o_cache_falha(): void
    {
        DB::shouldReceive('connection')->once()->andReturnSelf();
        DB::shouldReceive('getPdo')->once()->andReturn(new \stdClass());
        Cache::shouldReceive('put')
            ->once()
            ->andThrow(new \Exception('cache indisponivel'));

        $response = $this->getJson('/api/health');

        $response
            ->assertStatus(503)
            ->assertJson([
                'status' => 'degraded',
                'database' => 'ok',
                'cache' => 'error',
            ]);
    }
}
