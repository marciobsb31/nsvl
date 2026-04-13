<?php

namespace Tests\Integracao\Autenticacao;

use App\Models\Usuario;
use Illuminate\Routing\Middleware\ThrottleRequests;
use PHPUnit\Framework\Attributes\Test;
use Tests\Integracao\TestCase;

class TokenDeTesteControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ThrottleRequests::class);
    }

    #[Test]
    public function usa_perfil_federal_como_fallback_quando_o_perfil_informado_e_invalido(): void
    {
        $response = $this->postJson('/api/auth/token-de-teste', [
            'perfil' => 'perfil-inexistente',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('user.sub', self::SUB_FEDERAL);
    }

    #[Test]
    public function bloqueia_em_ambiente_nao_permitido(): void
    {
        config()->set('app.env', 'production');

        $response = $this->postJson('/api/auth/token-de-teste', [
            'perfil' => 'federal',
        ]);

        $response
            ->assertForbidden()
            ->assertJsonPath('message', 'Acesso não permitido.');
    }

    #[Test]
    public function retorna_nao_encontrado_quando_usuario_fixture_nao_existe(): void
    {
        Usuario::query()->where('govbr_sub', self::SUB_MUNICIPAL)->delete();

        $response = $this->postJson('/api/auth/token-de-teste', [
            'perfil' => 'municipal',
        ]);

        $response
            ->assertNotFound()
            ->assertJsonPath('message', 'Usuário de teste não encontrado.');
    }
}
