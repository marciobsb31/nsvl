<?php

namespace Tests\Integracao\Contexto;

use App\Models\PerfilUsuario;
use PHPUnit\Framework\Attributes\Test;
use Tests\Integracao\TestCase;

class ContextoUsuarioTest extends TestCase
{
    #[Test]
    public function retorna_dados_seguros_do_usuario_autenticado(): void
    {
        $this->autenticarComoFederal();

        $response = $this->getJson('/api/user');

        $response
            ->assertOk()
            ->assertJsonPath('sub', self::SUB_FEDERAL)
            ->assertJsonStructure([
                'id',
                'name',
                'email',
                'sub',
                'perfis_vigentes',
            ]);
    }

    #[Test]
    public function lista_catalogo_de_perfis_para_usuario_autenticado(): void
    {
        $this->autenticarComoFederal();

        $response = $this->getJson('/api/perfis');

        $response
            ->assertOk()
            ->assertJsonCount(6, 'data')
            ->assertJsonFragment(['nome' => 'Gestor Federal']);
    }

    #[Test]
    public function lista_perfis_vigentes_do_usuario(): void
    {
        $this->autenticarComoFederal();

        $response = $this->getJson('/api/user/perfis-ativos');

        $response
            ->assertOk()
            ->assertJsonCount(6, 'data');
    }

    #[Test]
    public function troca_o_contexto_para_outro_perfil_vigente(): void
    {
        $usuario = $this->autenticarComoFederal();

        $novoPerfil = PerfilUsuario::query()
            ->where('usuario_id', $usuario->id)
            ->where('ativo', false)
            ->orderBy('id')
            ->firstOrFail();

        $response = $this->postJson('/api/user/trocar-contexto', [
            'perfil_usuario_id' => $novoPerfil->id,
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Contexto alterado com sucesso.')
            ->assertJsonPath('user.perfil_ativo_id', $novoPerfil->id);

        $this->assertDatabaseHas('perfil_usuario', [
            'id' => $novoPerfil->id,
            'ativo' => true,
        ]);
    }
}

