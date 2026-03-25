<?php

namespace Tests\Feature;

use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class HealthCheckTest extends TestCase
{
    #[Test]
    public function health_check_retorna_status_ok(): void
    {
        $this->getJson('/api/health')
            ->assertOk()
            ->assertJsonStructure(['status', 'database', 'cache', 'version'])
            ->assertJsonPath('status', 'ok');
    }

    #[Test]
    public function health_check_e_publico(): void
    {
        $this->getJson('/api/health')
            ->assertOk();
    }

    #[Test]
    public function database_check_retorna_ok(): void
    {
        $this->getJson('/api/health')
            ->assertJsonPath('database', 'ok');
    }

    #[Test]
    public function cache_check_retorna_ok(): void
    {
        $this->getJson('/api/health')
            ->assertJsonPath('cache', 'ok');
    }
}
