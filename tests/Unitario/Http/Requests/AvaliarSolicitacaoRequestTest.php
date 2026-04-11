<?php

namespace Tests\Unitario\Http\Requests;

use App\Http\Requests\AvaliarSolicitacaoRequest;
use App\Models\StatusSolicitacao;
use PHPUnit\Framework\Attributes\Test;
use Tests\Unitario\TestCase;

class AvaliarSolicitacaoRequestTest extends TestCase
{
    #[Test]
    public function monta_regras_para_aprovacao_e_reprovacao(): void
    {
        $requestAprovado = new AvaliarSolicitacaoRequest;
        $requestAprovado->merge(['status' => StatusSolicitacao::APROVADO]);

        $requestReprovado = new AvaliarSolicitacaoRequest;
        $requestReprovado->merge(['status' => StatusSolicitacao::REPROVADO]);

        $this->assertArrayHasKey('perfil_id', $requestAprovado->rules());
        $this->assertArrayHasKey('vigencia_inicio', $requestAprovado->rules());
        $this->assertArrayHasKey('justificativa', $requestReprovado->rules());
        $this->assertTrue($requestAprovado->authorize());
        $this->assertSame(
            'A justificativa é obrigatória para reprovação.',
            $requestReprovado->messages()['justificativa.required'],
        );
    }
}
