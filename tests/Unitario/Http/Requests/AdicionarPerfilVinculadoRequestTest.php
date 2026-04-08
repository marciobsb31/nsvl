<?php

namespace Tests\Unitario\Http\Requests;

use App\Http\Requests\AdicionarPerfilVinculadoRequest;
use PHPUnit\Framework\Attributes\Test;
use Tests\Unitario\TestCase;

class AdicionarPerfilVinculadoRequestTest extends TestCase
{
    #[Test]
    public function define_regras_e_mensagens_do_request(): void
    {
        $request = new AdicionarPerfilVinculadoRequest();
        $rules = $request->rules();
        $messages = $request->messages();

        $this->assertTrue($request->authorize());
        $this->assertArrayHasKey('perfil_id', $rules);
        $this->assertArrayHasKey('vigencia_inicio', $rules);
        $this->assertArrayHasKey('vigencia_fim', $rules);
        $this->assertSame('Selecione o perfil a vincular.', $messages['perfil_id.required']);
        $this->assertSame('Perfil informado é inválido.', $messages['perfil_id.exists']);
    }
}
