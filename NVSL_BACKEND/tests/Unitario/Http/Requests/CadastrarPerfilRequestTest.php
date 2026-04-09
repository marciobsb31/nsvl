<?php

namespace Tests\Unitario\Http\Requests;

use App\Http\Requests\CadastrarPerfilRequest;
use PHPUnit\Framework\Attributes\Test;
use Tests\Unitario\TestCase;

class CadastrarPerfilRequestTest extends TestCase
{
    #[Test]
    public function define_regras_e_mensagens_do_request(): void
    {
        $request = new CadastrarPerfilRequest();
        $rules = $request->rules();
        $messages = $request->messages();

        $this->assertTrue($request->authorize());
        $this->assertArrayHasKey('nome', $rules);
        $this->assertArrayHasKey('descricao', $rules);
        $this->assertArrayHasKey('ativo', $rules);
        $this->assertSame('Preencha os campos obrigatórios.', $messages['nome.required']);
        $this->assertSame('Já existe um perfil com este nome.', $messages['nome.unique']);
    }
}
