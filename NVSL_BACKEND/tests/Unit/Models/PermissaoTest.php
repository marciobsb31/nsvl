<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Permissao model foi descontinuado na refatoração do schema.
 * As tabelas permissoes e perfil_permissao foram removidas.
 */
class PermissaoTest extends TestCase
{
    #[Test]
    public function modelo_depreciado(): void
    {
        $this->assertTrue(true);
    }
}
