<?php

namespace Tests\Unitario\Models;

use App\Models\Uf;
use Illuminate\Database\Eloquent\Relations\HasMany;
use PHPUnit\Framework\Attributes\Test;
use Tests\Unitario\TestCase;

class UfTest extends TestCase
{
    #[Test]
    public function expoe_relacoes_has_many_esperadas(): void
    {
        $uf = new Uf;

        $this->assertInstanceOf(HasMany::class, $uf->municipios());
        $this->assertInstanceOf(HasMany::class, $uf->solicitacoesCadastro());
    }
}
