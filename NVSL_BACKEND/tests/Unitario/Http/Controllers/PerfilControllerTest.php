<?php

namespace Tests\Unitario\Http\Controllers;

use App\Exceptions\ApiException;
use App\Http\Controllers\PerfilController;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\Test;
use Tests\Unitario\TestCase;

class PerfilControllerTest extends TestCase
{
    #[Test]
    public function lanca_erro_quando_nao_ha_usuario_autenticado(): void
    {
        Auth::shouldReceive('user')
            ->once()
            ->andReturn(null);

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Não autenticado.');

        (new PerfilController())->index();
    }
}
