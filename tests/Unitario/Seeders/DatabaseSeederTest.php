<?php

namespace Tests\Unitario\Seeders;

use Database\Seeders\DatabaseSeeder;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class DatabaseSeederTest extends TestCase
{
    #[Test]
    public function mantem_o_perfil_solicitado_em_ambiente_local_quando_ele_e_valido(): void
    {
        $this->assertSame(
            DatabaseSeeder::PERFIL_DEMO,
            DatabaseSeeder::resolverPerfilEfetivo('local', DatabaseSeeder::PERFIL_DEMO),
        );

        $this->assertSame(
            DatabaseSeeder::PERFIL_TEST,
            DatabaseSeeder::resolverPerfilEfetivo('local', DatabaseSeeder::PERFIL_TEST),
        );

        $this->assertSame(
            DatabaseSeeder::PERFIL_DEMO,
            DatabaseSeeder::resolverPerfilEfetivo('hlog', DatabaseSeeder::PERFIL_DEMO),
        );
    }

    #[Test]
    public function volta_para_bootstrap_em_ambiente_local_quando_o_perfil_e_invalido(): void
    {
        $this->assertSame(
            DatabaseSeeder::PERFIL_BOOTSTRAP,
            DatabaseSeeder::resolverPerfilEfetivo('local', 'qualquer-coisa'),
        );
    }

    #[Test]
    public function força_bootstrap_em_qualquer_ambiente_diferente_de_local_e_hlog(): void
    {
        $this->assertSame(
            DatabaseSeeder::PERFIL_BOOTSTRAP,
            DatabaseSeeder::resolverPerfilEfetivo('production', DatabaseSeeder::PERFIL_DEMO),
        );

        $this->assertSame(
            DatabaseSeeder::PERFIL_BOOTSTRAP,
            DatabaseSeeder::resolverPerfilEfetivo('homolog', DatabaseSeeder::PERFIL_TEST),
        );

        $this->assertSame(
            DatabaseSeeder::PERFIL_BOOTSTRAP,
            DatabaseSeeder::resolverPerfilEfetivo('testing', DatabaseSeeder::PERFIL_TEST),
        );
    }
}
