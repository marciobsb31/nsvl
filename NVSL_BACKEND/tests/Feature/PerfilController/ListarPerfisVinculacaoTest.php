<?php

namespace Tests\Feature\PerfilController;

use App\Models\Perfil;
use Tests\TestCase;
use Tests\Traits\ActingAsUserTrait;
use PHPUnit\Framework\Attributes\Test;

class ListarPerfisVinculacaoTest extends TestCase
{
    use ActingAsUserTrait;

    #[Test]
    public function usuario_nao_autenticado_recebe_401(): void
    {
        $this->getJson('/api/perfis')
            ->assertStatus(401);
    }

    #[Test]
    public function lista_apenas_perfis_de_cadastro_ativos(): void
    {
        Perfil::factory()->create(['nome' => 'Administrador Nacional', 'ativo' => true]);
        Perfil::factory()->create(['nome' => 'Administrador Estadual', 'ativo' => true]);
        Perfil::factory()->create(['nome' => 'Administrador Municipal', 'ativo' => false]);
        Perfil::factory()->create(['nome' => 'Perfil Qualquer', 'ativo' => true]);

        $user = $this->criarUsuarioFederal();

        $response = $this->autenticar($user)
            ->getJson('/api/perfis')
            ->assertOk();

        $nomes = collect($response->json('data'))->pluck('nome');

        $this->assertTrue($nomes->contains('Administrador Nacional'));
        $this->assertTrue($nomes->contains('Administrador Estadual'));
        $this->assertFalse($nomes->contains('Administrador Municipal'));
        $this->assertFalse($nomes->contains('Perfil Qualquer'));
    }

    #[Test]
    public function resposta_contem_campos_esperados(): void
    {
        Perfil::factory()->create(['nome' => 'Gestor Nacional', 'ativo' => true]);
        $user = $this->criarUsuarioFederal();

        $this->autenticar($user)
            ->getJson('/api/perfis')
            ->assertOk()
            ->assertJsonStructure(['data' => [['value', 'label', 'id', 'nome']]]);
    }
}
