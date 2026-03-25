<?php

namespace Tests\Unit\Helpers;

use App\Helpers\CpfHelper;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;

class CpfHelperTest extends TestCase
{
    #[Test]
    public function valida_cpf_correto(): void
    {
        $this->assertTrue(CpfHelper::validar('52998224725'));
        $this->assertTrue(CpfHelper::validar('529.982.247-25'));
    }

    #[Test]
    public function rejeita_cpf_com_digitos_repetidos(): void
    {
        $this->assertFalse(CpfHelper::validar('00000000000'));
        $this->assertFalse(CpfHelper::validar('11111111111'));
        $this->assertFalse(CpfHelper::validar('99999999999'));
    }

    #[Test]
    public function rejeita_cpf_com_digitos_invalidos(): void
    {
        $this->assertFalse(CpfHelper::validar('12345678901'));
        $this->assertFalse(CpfHelper::validar('52998224726'));
    }

    #[Test]
    public function rejeita_cpf_com_tamanho_incorreto(): void
    {
        $this->assertFalse(CpfHelper::validar('123'));
        $this->assertFalse(CpfHelper::validar(''));
        $this->assertFalse(CpfHelper::validar('123456789012'));
    }

    #[Test]
    public function mascara_cpf_corretamente(): void
    {
        $this->assertEquals('529.982.247-25', CpfHelper::mascarar('52998224725'));
    }

    #[Test]
    public function mascara_retorna_asteriscos_para_cpf_invalido(): void
    {
        $this->assertEquals('***.***.***-**', CpfHelper::mascarar('123'));
        $this->assertEquals('***.***.***-**', CpfHelper::mascarar(''));
    }
}
