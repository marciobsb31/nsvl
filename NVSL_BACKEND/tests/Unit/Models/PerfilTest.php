<?php

namespace Tests\Unit\Models;

use App\Models\Perfil;
use App\Models\PerfilUsuario;
use App\Models\Usuario;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class PerfilTest extends TestCase
{
    #[Test]
    public function pode_ser_criado_com_factory(): void
    {
        $perfil = Perfil::factory()->create([
            'nome'  => 'Administrador Nacional',
            'ativo' => true,
        ]);

        $this->assertDatabaseHas('perfis', ['nome' => 'Administrador Nacional']);
        $this->assertTrue($perfil->ativo);
    }

    #[Test]
    public function possui_muitos_perfis_usuario(): void
    {
        $perfil = Perfil::factory()->create();
        $user = Usuario::factory()->create();

        PerfilUsuario::create([
            'usuario_id' => $user->id,
            'perfil_id'  => $perfil->id,
        ]);

        $this->assertCount(1, $perfil->perfisUsuario);
    }

    #[Test]
    public function tabela_customizada_esta_correta(): void
    {
        $perfil = new Perfil();
        $this->assertEquals('perfis', $perfil->getTable());
    }

    #[Test]
    public function fillable_contem_campos_esperados(): void
    {
        $perfil = new Perfil();
        $this->assertEquals(['nome', 'descricao', 'ativo'], $perfil->getFillable());
    }

    #[Test]
    public function factory_state_inativo_funciona(): void
    {
        $ativo = Perfil::factory()->create();
        $inativo = Perfil::factory()->inativo()->create();

        $this->assertTrue($ativo->ativo);
        $this->assertFalse($inativo->ativo);
    }
}
