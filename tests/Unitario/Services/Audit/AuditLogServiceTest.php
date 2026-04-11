<?php

namespace Tests\Unitario\Services\Audit;

use App\Services\Audit\AuditLogService;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\Unitario\TestCase;

class AuditLogServiceTest extends TestCase
{
    #[Test]
    public function mascara_apenas_os_campos_sensiveis_configurados(): void
    {
        config()->set('audit.masked_fields', ['cpf', 'token']);

        $service = new AuditLogService(Request::create('/teste', 'GET'));

        $closure = \Closure::bind(
            fn (array $contexto) => $this->maskSensitiveFields($contexto),
            $service,
            $service
        );

        $resultado = $closure([
            'cpf'   => '11144477735',
            'token' => 'segredo',
            'nome'  => 'Maria',
        ]);

        $this->assertSame('***', $resultado['cpf']);
        $this->assertSame('***', $resultado['token']);
        $this->assertSame('Maria', $resultado['nome']);
    }

    #[Test]
    public function nao_altera_contexto_quando_nao_ha_campos_configurados(): void
    {
        config()->set('audit.masked_fields', []);

        $service = new AuditLogService(Request::create('/teste', 'GET'));

        $closure = \Closure::bind(
            fn (array $contexto) => $this->maskSensitiveFields($contexto),
            $service,
            $service
        );

        $contexto = ['nome' => 'Maria'];

        $this->assertSame($contexto, $closure($contexto));
    }
}
