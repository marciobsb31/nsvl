<?php

namespace Tests\Feature\GerenciarPerfis;

use Tests\TestCase;
use Tests\Traits\ActingAsUserTrait;
use PHPUnit\Framework\Attributes\Test;

class HierarquiaPerfilTest extends TestCase
{
    use ActingAsUserTrait;

    #[Test]
    public function usuario_nao_autenticado_recebe_401(): void
    {
        $this->getJson('/api/gerenciar-perfis/hierarquia')
            ->assertStatus(401);
    }

    #[Test]
    public function usuario_federal_ve_todas_as_esferas(): void
    {
        $user = $this->criarUsuarioFederal();

        $this->autenticar($user)
            ->getJson('/api/gerenciar-perfis/hierarquia')
            ->assertOk()
            ->assertJsonPath('esfera_usuario', 'federal')
            ->assertJsonPath('esferas_permitidas', ['federal', 'estadual', 'municipal']);
    }

    #[Test]
    public function usuario_estadual_ve_apenas_estadual(): void
    {
        $user = $this->criarUsuarioEstadual();

        $this->autenticar($user)
            ->getJson('/api/gerenciar-perfis/hierarquia')
            ->assertOk()
            ->assertJsonPath('esfera_usuario', 'estadual')
            ->assertJsonPath('esferas_permitidas', ['estadual']);
    }

    #[Test]
    public function usuario_municipal_ve_apenas_municipal(): void
    {
        $user = $this->criarUsuarioMunicipal();

        $this->autenticar($user)
            ->getJson('/api/gerenciar-perfis/hierarquia')
            ->assertOk()
            ->assertJsonPath('esfera_usuario', 'municipal')
            ->assertJsonPath('esferas_permitidas', ['municipal']);
    }
}
