<?php

namespace Tests\Unitario\Models;

use App\Models\Perfil;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class PerfilTest extends TestCase
{
    #[Test]
    public function retorna_indice_no_catalogo_oficial(): void
    {
        $this->assertSame(0, Perfil::indiceNoCatalogo('Gestor Nacional'));
        $this->assertSame(5, Perfil::indiceNoCatalogo('Administrador Municipal'));
    }

    #[Test]
    public function retorna_valor_maximo_para_perfil_desconhecido(): void
    {
        $this->assertSame(PHP_INT_MAX, Perfil::indiceNoCatalogo('Perfil Inexistente'));
    }
}
