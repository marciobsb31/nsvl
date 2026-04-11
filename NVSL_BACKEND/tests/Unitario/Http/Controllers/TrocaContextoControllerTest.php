<?php

namespace Tests\Unitario\Http\Controllers;

use App\Exceptions\ApiException;
use App\Http\Controllers\TrocaContextoController;
use App\Models\Usuario;
use App\Services\Audit\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use Tests\Unitario\TestCase;

class TrocaContextoControllerTest extends TestCase
{
    #[Test]
    public function listar_perfis_ativos_exige_autenticacao(): void
    {
        Auth::shouldReceive('user')
            ->once()
            ->andReturn(null);

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Não autenticado.');

        $this->controller()->listarPerfisAtivos();
    }

    #[Test]
    public function trocar_contexto_exige_autenticacao(): void
    {
        Auth::shouldReceive('user')
            ->once()
            ->andReturn(null);

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Não autenticado.');

        $this->controller()->trocarContexto(Request::create('/api/user/trocar-contexto', 'POST'));
    }

    #[Test]
    public function lista_perfis_ativos_mapeando_o_payload_do_usuario(): void
    {
        $usuario = $this->createMock(Usuario::class);
        $usuario->expects($this->once())
            ->method('toSafeArray')
            ->willReturn([
                'perfis_vigentes' => [[
                    'perfil_usuario_id' => 99,
                    'perfil_id' => 2,
                    'nome' => 'Gestor Estadual',
                    'esfera' => 'Estadual',
                    'uf' => 'GO',
                    'municipio' => 'Goiânia',
                    'orgao' => 'Secretaria Estadual',
                    'ativo' => true,
                ]],
            ]);

        Auth::shouldReceive('user')
            ->once()
            ->andReturn($usuario);

        $response = $this->controller()->listarPerfisAtivos();

        $this->assertSame(99, $response->getData(true)['data'][0]['perfil_usuario_id']);
        $this->assertSame('Gestor Estadual', $response->getData(true)['data'][0]['nome']);
    }

    #[Test]
    public function trocar_contexto_valida_campo_obrigatorio_antes_do_fluxo_de_troca(): void
    {
        $usuario = $this->createStub(Usuario::class);

        Auth::shouldReceive('user')
            ->once()
            ->andReturn($usuario);

        $this->expectException(ValidationException::class);

        try {
            $this->controller()->trocarContexto(Request::create('/api/user/trocar-contexto', 'POST'));
        } catch (ValidationException $exception) {
            $this->assertSame(
                'Informe o perfil a ser ativado.',
                $exception->errors()['perfil_usuario_id'][0] ?? null,
            );

            throw $exception;
        }
    }

    #[Test]
    public function trocar_contexto_bloqueia_quando_o_perfil_nao_pertence_ao_usuario(): void
    {
        $usuario = $this->createMock(Usuario::class);
        $usuario->expects($this->once())
            ->method('perfisVigentes')
            ->willReturn(collect());

        Auth::shouldReceive('user')
            ->once()
            ->andReturn($usuario);

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('O perfil selecionado não está ativo ou não pertence ao seu cadastro.');

        $this->controller()->trocarContexto(Request::create('/api/user/trocar-contexto', 'POST', [
            'perfil_usuario_id' => 999,
        ]));
    }

    private function controller(): TrocaContextoController
    {
        return new TrocaContextoController(
            $this->createStub(AuditLogService::class)
        );
    }
}
