<?php

namespace Tests\Unitario\Http\Controllers;

use App\Exceptions\ApiException;
use App\Http\Controllers\SolicitacaoCadastroController;
use App\Http\Requests\SolicitacaoCadastroRequest;
use App\Services\SolicitacaoCadastro\SolicitacaoCadastroService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\Test;
use Tests\Unitario\TestCase;

class SolicitacaoCadastroControllerTest extends TestCase
{
    #[Test]
    public function usuario_autenticado_lanca_erro_quando_o_usuario_nao_e_do_tipo_esperado(): void
    {
        Auth::shouldReceive('user')
            ->once()
            ->andReturn(null);

        $controller = new SolicitacaoCadastroController(
            $this->createStub(SolicitacaoCadastroService::class)
        );

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Não autenticado.');

        $closure = \Closure::bind(
            fn () => $this->usuarioAutenticado(),
            $controller,
            $controller
        );

        $closure();
    }

    #[Test]
    public function verificar_cpf_delega_para_o_service_e_retorna_json(): void
    {
        $service = $this->createMock(SolicitacaoCadastroService::class);
        $service->expects($this->once())
            ->method('verificarCpf')
            ->with('11144477735', null, null, null)
            ->willReturn([
                'disponivel' => true,
                'mensagem'   => 'CPF disponível para cadastro.',
            ]);

        $controller = new SolicitacaoCadastroController($service);

        $response = $controller->verificarCpf(Request::create('/api/solicitacoes-cadastro/verificar-cpf', 'GET', [
            'cpf' => '11144477735',
        ]));

        $this->assertTrue($response->getData(true)['disponivel']);
    }

    #[Test]
    public function store_delega_para_o_service_e_retorna_status_201(): void
    {
        Auth::shouldReceive('guard->user')
            ->once()
            ->andReturn(null);

        $service = $this->createMock(SolicitacaoCadastroService::class);
        $service->expects($this->once())
            ->method('criar')
            ->with(null, ['CPF' => '11144477735'])
            ->willReturn([
                'message'        => 'Solicitação registrada com sucesso!',
                'solicitacao_id' => 123,
            ]);

        $request = \Mockery::mock(SolicitacaoCadastroRequest::class)->makePartial();
        $request->shouldReceive('all')
            ->once()
            ->andReturn(['CPF' => '11144477735']);

        $controller = new SolicitacaoCadastroController($service);
        $response = $controller->store($request);

        $this->assertSame(201, $response->status());
        $this->assertSame(123, $response->getData(true)['solicitacao_id']);
    }
}
