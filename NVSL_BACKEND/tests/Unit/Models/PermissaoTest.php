<?php

namespace Tests\Unit\Models;

use App\Models\Perfil;
use App\Models\Permissao;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class PermissaoTest extends TestCase
{
    #[Test]
    public function pode_ser_criada(): void
    {
        $permissao = Permissao::create([
            'modulo'    => 'Gerenciar Perfis',
            'acao'      => 'Criar',
            'descricao' => 'Cadastrar novos perfis',
        ]);

        $this->assertDatabaseHas('permissoes', [
            'modulo' => 'Gerenciar Perfis',
            'acao'   => 'Criar',
        ]);
    }

    #[Test]
    public function pertence_a_muitos_perfis(): void
    {
        $permissao = Permissao::create(['modulo' => 'Mod', 'acao' => 'Act']);
        $perfil1 = Perfil::factory()->create();
        $perfil2 = Perfil::factory()->create();

        $permissao->perfis()->sync([$perfil1->id, $perfil2->id]);

        $this->assertCount(2, $permissao->fresh()->perfis);
    }

    #[Test]
    public function tabela_customizada_esta_correta(): void
    {
        $this->assertEquals('permissoes', (new Permissao())->getTable());
    }
}
