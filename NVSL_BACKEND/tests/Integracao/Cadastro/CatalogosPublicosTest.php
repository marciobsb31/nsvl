<?php

namespace Tests\Integracao\Cadastro;

use PHPUnit\Framework\Attributes\Test;
use Tests\Integracao\TestCase;

class CatalogosPublicosTest extends TestCase
{
    #[Test]
    public function lista_esferas_disponiveis_para_o_formulario(): void
    {
        $response = $this->getJson('/api/esferas');

        $response
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonFragment(['value' => 'federal'])
            ->assertJsonFragment(['value' => 'estadual'])
            ->assertJsonFragment(['value' => 'municipal']);
    }

    #[Test]
    public function lista_ufs_disponiveis_no_cache_local_de_localidades(): void
    {
        $response = $this->getJson('/api/localidades/ufs');

        $response
            ->assertOk()
            ->assertJsonFragment(['value' => 'DF'])
            ->assertJsonFragment(['value' => 'GO'])
            ->assertJsonFragment(['value' => 'SP']);
    }

    #[Test]
    public function lista_municipios_por_sigla_de_uf(): void
    {
        $response = $this->getJson('/api/localidades/municipios?uf=GO');

        $response
            ->assertOk()
            ->assertJsonFragment(['value' => 'Alexânia'])
            ->assertJsonFragment(['value' => 'Goiânia']);
    }

    #[Test]
    public function retorna_erro_ao_consultar_municipios_com_uf_invalida(): void
    {
        $response = $this->getJson('/api/localidades/municipios?uf=G');

        $response
            ->assertStatus(422)
            ->assertJsonPath('message', 'Parâmetro uf é obrigatório e deve ter 2 caracteres (sigla).');
    }

    #[Test]
    public function retorna_payload_completo_de_localidades(): void
    {
        $response = $this->getJson('/api/localidades/completo');

        $response
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'ufs',
                    'municipios_por_uf' => ['DF', 'GO', 'SP'],
                ],
            ]);
    }
}
