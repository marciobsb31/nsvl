<?php

namespace Tests\Unit\Models;

use App\Models\Perfil;
use App\Models\PerfilUsuario;
use App\Models\User;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class PerfilUsuarioTest extends TestCase
{
    #[Test]
    public function pertence_a_um_usuario(): void
    {
        $user = User::factory()->create();
        $perfil = Perfil::factory()->create();
        $pu = PerfilUsuario::create([
            'usuario_id' => $user->id,
            'perfil_id'  => $perfil->id,
        ]);

        $this->assertEquals($user->id, $pu->usuario->id);
    }

    #[Test]
    public function pertence_a_um_perfil(): void
    {
        $user = User::factory()->create();
        $perfil = Perfil::factory()->create();
        $pu = PerfilUsuario::create([
            'usuario_id' => $user->id,
            'perfil_id'  => $perfil->id,
        ]);

        $this->assertEquals($perfil->id, $pu->perfil->id);
    }

    #[Test]
    public function datas_sao_castadas_como_date(): void
    {
        $user = User::factory()->create();
        $perfil = Perfil::factory()->create();
        $pu = PerfilUsuario::create([
            'usuario_id' => $user->id,
            'perfil_id'  => $perfil->id,
            'data_inicio_vigencia' => '2026-01-01',
            'data_fim_vigencia'    => '2026-12-31',
        ]);

        $this->assertInstanceOf(\Carbon\Carbon::class, $pu->data_inicio_vigencia);
        $this->assertInstanceOf(\Carbon\Carbon::class, $pu->data_fim_vigencia);
    }

    #[Test]
    public function aceita_uf_municipio_orgao(): void
    {
        $user = User::factory()->create();
        $perfil = Perfil::factory()->create();
        $pu = PerfilUsuario::create([
            'usuario_id' => $user->id,
            'perfil_id'  => $perfil->id,
            'uf' => 'GO',
            'municipio' => 'Goiânia',
            'orgao' => 'Secretaria de Saúde',
        ]);

        $this->assertEquals('GO', $pu->uf);
        $this->assertEquals('Goiânia', $pu->municipio);
        $this->assertEquals('Secretaria de Saúde', $pu->orgao);
    }
}
