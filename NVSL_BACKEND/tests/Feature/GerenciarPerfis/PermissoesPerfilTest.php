<?php

namespace Tests\Feature\GerenciarPerfis;

use Tests\TestCase;
use Tests\Traits\ActingAsUserTrait;
use PHPUnit\Framework\Attributes\Test;

/**
 * Permissões foram removidas do schema.
 * As tabelas permissoes e perfil_permissao não existem mais.
 */
class PermissoesPerfilTest extends TestCase
{
    use ActingAsUserTrait;

    #[Test]
    public function funcionalidade_depreciada(): void
    {
        $this->assertTrue(true);
    }
}
