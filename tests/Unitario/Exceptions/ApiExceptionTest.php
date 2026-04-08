<?php

namespace Tests\Unitario\Exceptions;

use App\Exceptions\ApiException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ApiExceptionTest extends TestCase
{
    #[Test]
    public function cria_excecao_nao_autenticado_com_valores_padrao(): void
    {
        $exception = ApiException::unauthenticated();

        $this->assertSame('Não autenticado.', $exception->getMessage());
        $this->assertSame(401, $exception->status());
        $this->assertSame('unauthenticated', $exception->error());
    }

    #[Test]
    public function cria_excecoes_semanticas_com_status_e_codigo_corretos(): void
    {
        $this->assertSame(403, ApiException::forbidden()->status());
        $this->assertSame('forbidden', ApiException::forbidden()->error());

        $this->assertSame(404, ApiException::notFound()->status());
        $this->assertSame('not_found', ApiException::notFound()->error());

        $this->assertSame(422, ApiException::unprocessable()->status());
        $this->assertSame('unprocessable_entity', ApiException::unprocessable()->error());

        $this->assertSame(409, ApiException::conflict()->status());
        $this->assertSame('conflict', ApiException::conflict()->error());
    }
}
