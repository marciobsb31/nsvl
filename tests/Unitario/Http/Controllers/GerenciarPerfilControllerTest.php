<?php

namespace Tests\Unitario\Http\Controllers;

use App\Http\Controllers\GerenciarPerfilController;
use App\Models\AuditLog;
use App\Models\Perfil;
use App\Services\Audit\AuditLogService;
use PHPUnit\Framework\Attributes\Test;
use Tests\Unitario\TestCase;

class GerenciarPerfilControllerTest extends TestCase
{
    private GerenciarPerfilController $controller;

    protected function setUp(): void
    {
        parent::setUp();

        $this->controller = new GerenciarPerfilController(
            $this->createStub(AuditLogService::class)
        );
    }

    #[Test]
    public function descreve_alteracoes_de_nome_e_status_ou_retorna_texto_padrao(): void
    {
        $perfil = new Perfil([
            'nome' => 'Administrador Estadual',
            'ativo' => false,
        ]);

        $descricaoCompleta = $this->invocarMetodoPrivado('descreverAlteracoes', [
            ['nome' => 'Gestor Estadual', 'ativo' => true],
            $perfil,
        ]);

        $descricaoPadrao = $this->invocarMetodoPrivado('descreverAlteracoes', [
            ['nome' => 'Administrador Estadual', 'ativo' => false],
            $perfil,
        ]);

        $this->assertSame(
            'Alterando o Nome de Perfil para Administrador Estadual; Alterando Situação para Não Vigente',
            $descricaoCompleta,
        );
        $this->assertSame('Atualização de dados do perfil', $descricaoPadrao);
    }

    #[Test]
    public function descreve_acao_do_log_priorizando_contexto_mapa_ou_fallback(): void
    {
        $comContexto = new AuditLog([
            'acao' => 'qualquer.acao',
            'contexto' => ['alteracoes' => 'Mudança detalhada'],
        ]);

        $mapeado = new AuditLog([
            'acao' => 'gerenciar_perfis.cadastrar',
            'contexto' => [],
        ]);

        $desconhecido = new AuditLog([
            'acao' => 'acao.desconhecida',
            'contexto' => [],
        ]);

        $this->assertSame(
            'Mudança detalhada',
            $this->invocarMetodoPrivado('descreverAcaoLog', [$comContexto]),
        );
        $this->assertSame(
            'Perfil cadastrado',
            $this->invocarMetodoPrivado('descreverAcaoLog', [$mapeado]),
        );
        $this->assertSame(
            'acao.desconhecida',
            $this->invocarMetodoPrivado('descreverAcaoLog', [$desconhecido]),
        );
    }

    #[Test]
    public function retorna_esferas_permitidas_conforme_a_esfera_do_usuario(): void
    {
        $this->assertSame(
            ['federal', 'estadual', 'municipal'],
            $this->invocarMetodoPrivado('esferasPermitidas', ['federal']),
        );
        $this->assertSame(
            ['estadual'],
            $this->invocarMetodoPrivado('esferasPermitidas', ['estadual']),
        );
        $this->assertSame(
            ['municipal'],
            $this->invocarMetodoPrivado('esferasPermitidas', ['municipal']),
        );
        $this->assertSame(
            [],
            $this->invocarMetodoPrivado('esferasPermitidas', ['desconhecida']),
        );
    }

    #[Test]
    public function formata_o_payload_de_resposta_do_perfil(): void
    {
        $perfil = new Perfil([
            'nome' => 'Gestor Estadual',
            'descricao' => 'Perfil de teste',
            'ativo' => true,
        ]);
        $perfil->setAttribute('id', 10);
        $perfil->created_at = now();

        $payload = $this->invocarMetodoPrivado('formatarPerfil', [$perfil]);

        $this->assertSame(10, $payload['id']);
        $this->assertSame('Gestor Estadual', $payload['nome']);
        $this->assertSame('Perfil de teste', $payload['descricao']);
        $this->assertTrue($payload['ativo']);
        $this->assertSame('ativo', $payload['status']);
    }

    private function invocarMetodoPrivado(string $metodo, array $argumentos): mixed
    {
        $closure = \Closure::bind(
            fn (array $args) => $this->{$metodo}(...$args),
            $this->controller,
            $this->controller
        );

        return $closure($argumentos);
    }
}
