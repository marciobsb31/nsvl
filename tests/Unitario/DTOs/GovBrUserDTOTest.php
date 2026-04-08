<?php

namespace Tests\Unitario\DTOs;

use App\DTOs\Auth\GovBrUserDTO;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class GovBrUserDTOTest extends TestCase
{
    #[Test]
    public function monta_o_dto_a_partir_de_array_do_userinfo(): void
    {
        $dto = GovBrUserDTO::fromArray([
            'sub' => '12345678901',
            'name' => 'Maria da Silva',
            'email' => 'maria@exemplo.gov.br',
            'amr' => ['pwd'],
        ]);

        $this->assertSame('12345678901', $dto->sub);
        $this->assertSame('Maria da Silva', $dto->name);
        $this->assertSame('maria@exemplo.gov.br', $dto->email);
        $this->assertSame(['pwd'], $dto->amr);
        $this->assertSame('12345678901', $dto->cpf);
    }

    #[Test]
    public function preenche_valores_padrao_quando_campos_nao_sao_informados(): void
    {
        $dto = GovBrUserDTO::fromArray([]);

        $this->assertSame('', $dto->sub);
        $this->assertSame('', $dto->name);
        $this->assertNull($dto->email);
        $this->assertNull($dto->amr);
        $this->assertNull($dto->cpf);
    }
}
