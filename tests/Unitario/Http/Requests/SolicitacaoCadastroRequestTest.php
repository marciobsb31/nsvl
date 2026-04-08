<?php

namespace Tests\Unitario\Http\Requests;

use App\Http\Requests\SolicitacaoCadastroRequest;
use App\Models\Usuario;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\Test;
use Tests\Unitario\TestCase;

class SolicitacaoCadastroRequestTest extends TestCase
{
    #[Test]
    public function define_regras_e_atributos_conforme_autenticacao(): void
    {
        Auth::shouldReceive('guard->check')
            ->once()
            ->andReturn(false);

        $requestPublico = new SolicitacaoCadastroRequest();
        $rulesPublico = $requestPublico->rules();

        $this->assertSame('perfil', $requestPublico->attributes()['perfilId']);
        $this->assertSame('Selecione o perfil.', $requestPublico->messages()['perfilId.required']);
        $this->assertContains('nullable', $rulesPublico['perfilId']);
        $this->assertContains('nullable', $rulesPublico['vigenciaInicio']);
    }

    #[Test]
    public function define_regras_obrigatorias_para_fluxo_autenticado(): void
    {
        Auth::shouldReceive('guard->check')
            ->once()
            ->andReturn(true);

        $requestAutenticado = new SolicitacaoCadastroRequest();
        $rulesAutenticado = $requestAutenticado->rules();

        $this->assertContains('required', $rulesAutenticado['perfilId']);
        $this->assertContains('required', $rulesAutenticado['vigenciaInicio']);
        $this->assertTrue($requestAutenticado->authorize());
    }

    #[Test]
    public function prepara_payload_normalizando_campos_e_sobrescrevendo_dados_do_usuario_autenticado(): void
    {
        $usuario = new Usuario([
            'cpf' => '11144477735',
            'nome' => 'Maria Federal',
        ]);

        Auth::shouldReceive('guard->user')
            ->once()
            ->andReturn($usuario);

        $request = new SolicitacaoCadastroRequest();
        $request->merge([
            'CPF' => '111.444.777-35',
            'nome' => 'Nome Ignorado',
            'telefoneInstitucional' => '(61) 99988-7766',
            'telefonePessoal' => '',
            'uf' => ' go ',
            'municipio' => ' Alexânia ',
        ]);

        $closure = \Closure::bind(
            fn () => $this->prepareForValidation(),
            $request,
            $request
        );

        $closure();

        $this->assertSame('11144477735', $request->input('CPF'));
        $this->assertSame('Maria Federal', $request->input('nome'));
        $this->assertSame('61999887766', $request->input('telefoneInstitucional'));
        $this->assertNull($request->input('telefonePessoal'));
        $this->assertSame('GO', $request->input('uf'));
        $this->assertSame('Alexânia', $request->input('municipio'));
    }

    #[Test]
    public function normaliza_telefone_pessoal_para_nulo_quando_fica_sem_digitos(): void
    {
        Auth::shouldReceive('guard->user')
            ->once()
            ->andReturn(null);

        $request = new SolicitacaoCadastroRequest();
        $request->merge([
            'CPF' => '111.444.777-35',
            'telefoneInstitucional' => '(61) 99988-7766',
            'telefonePessoal' => '() - ',
            'uf' => ' df ',
            'municipio' => ' Brasília ',
        ]);

        $closure = \Closure::bind(
            fn () => $this->prepareForValidation(),
            $request,
            $request
        );

        $closure();

        $this->assertSame('11144477735', $request->input('CPF'));
        $this->assertNull($request->input('telefonePessoal'));
        $this->assertSame('DF', $request->input('uf'));
        $this->assertSame('Brasília', $request->input('municipio'));
    }
}
