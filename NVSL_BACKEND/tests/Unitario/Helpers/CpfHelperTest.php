<?php

namespace Tests\Unitario\Helpers;

use App\Helpers\CpfHelper;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class CpfHelperTest extends TestCase
{
    #[Test]
    public function valida_cpf_correto(): void
    {
        $this->assertTrue(CpfHelper::validar('52998224725'));
        $this->assertTrue(CpfHelper::validar('529.982.247-25'));
    }

    #[Test]
    public function rejeita_cpf_com_digitos_invalidos(): void
    {
        $this->assertFalse(CpfHelper::validar('12345678901'));
        $this->assertFalse(CpfHelper::validar('52998224726'));
    }

    #[Test]
    public function mascara_cpf_corretamente(): void
    {
        $this->assertSame('529.982.247-25', CpfHelper::mascarar('52998224725'));
    }
}
