<?php

namespace Tests\Integracao\Banco;

use App\Models\Perfil;
use App\Models\PerfilUsuario;
use App\Models\Usuario;
use Illuminate\Database\QueryException;
use PHPUnit\Framework\Attributes\Test;
use Tests\Integracao\TestCase;

class PerfilUsuarioConstraintTest extends TestCase
{
    #[Test]
    public function impede_mais_de_um_perfil_ativo_para_o_mesmo_usuario(): void
    {
        $usuario = Usuario::factory()->create();
        $perfilUm = Perfil::factory()->create();
        $perfilDois = Perfil::factory()->create();

        PerfilUsuario::create([
            'usuario_id' => $usuario->id,
            'perfil_id' => $perfilUm->id,
            'ativo' => true,
        ]);

        $this->expectException(QueryException::class);

        PerfilUsuario::create([
            'usuario_id' => $usuario->id,
            'perfil_id' => $perfilDois->id,
            'ativo' => true,
        ]);
    }
}
