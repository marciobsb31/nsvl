<?php

namespace Tests\Unit\Models;

use App\Models\Perfil;
use App\Models\PerfilUsuario;
use App\Models\Permissao;
use App\Models\User;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class PerfilTest extends TestCase
{
    #[Test]
    public function pode_ser_criado_com_factory(): void
    {
        $perfil = Perfil::factory()->create([
            'nome'   => 'Administrador Nacional',
            'esfera' => 'federal',
            'status' => 'ativo',
        ]);

        $this->assertDatabaseHas('perfis', ['nome' => 'Administrador Nacional']);
        $this->assertEquals('federal', $perfil->esfera);
    }

    #[Test]
    public function possui_muitas_permissoes(): void
    {
        $perfil = Perfil::factory()->create();
        $perm1 = Permissao::create(['modulo' => 'Mod1', 'acao' => 'Acao1']);
        $perm2 = Permissao::create(['modulo' => 'Mod2', 'acao' => 'Acao2']);

        $perfil->permissoes()->sync([$perm1->id, $perm2->id]);

        $this->assertCount(2, $perfil->fresh()->permissoes);
    }

    #[Test]
    public function possui_muitos_perfis_usuario(): void
    {
        $perfil = Perfil::factory()->create();
        $user = User::factory()->create();

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
        $this->assertEquals(['nome', 'descricao', 'esfera', 'status'], $perfil->getFillable());
    }

    #[Test]
    public function factory_states_funcionam(): void
    {
        $federal = Perfil::factory()->federal()->create();
        $estadual = Perfil::factory()->estadual()->create();
        $municipal = Perfil::factory()->municipal()->create();
        $inativo = Perfil::factory()->inativo()->create();

        $this->assertEquals('federal', $federal->esfera);
        $this->assertEquals('estadual', $estadual->esfera);
        $this->assertEquals('municipal', $municipal->esfera);
        $this->assertEquals('inativo', $inativo->status);
    }
}
