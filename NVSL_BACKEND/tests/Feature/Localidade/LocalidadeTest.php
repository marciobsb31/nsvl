<?php

namespace Tests\Feature\Localidade;

use App\Models\Municipio;
use App\Models\Uf;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class LocalidadeTest extends TestCase
{
    private function seedLocalidades(): void
    {
        $go = Uf::create(['sigla' => 'GO', 'nome' => 'Goiás']);
        $sp = Uf::create(['sigla' => 'SP', 'nome' => 'São Paulo']);

        Municipio::create(['nome' => 'Alexânia', 'uf_id' => $go->id]);
        Municipio::create(['nome' => 'Goiânia', 'uf_id' => $go->id]);
        Municipio::create(['nome' => 'São Paulo', 'uf_id' => $sp->id]);
    }

    #[Test]
    public function lista_ufs(): void
    {
        $this->seedLocalidades();

        $this->getJson('/api/localidades/ufs')
            ->assertOk()
            ->assertJsonStructure(['data' => [['value', 'label']]]);
    }

    #[Test]
    public function lista_municipios_por_uf(): void
    {
        $this->seedLocalidades();

        $response = $this->getJson('/api/localidades/municipios?uf=GO')
            ->assertOk()
            ->assertJsonStructure(['data' => [['value', 'label']]]);

        $this->assertCount(2, $response->json('data'));
    }

    #[Test]
    public function uf_obrigatoria_para_municipios(): void
    {
        $this->getJson('/api/localidades/municipios')
            ->assertStatus(422);
    }

    #[Test]
    public function uf_com_tamanho_invalido_retorna_422(): void
    {
        $this->getJson('/api/localidades/municipios?uf=GOI')
            ->assertStatus(422);
    }

    #[Test]
    public function endpoint_completo_retorna_ufs_e_municipios(): void
    {
        $this->seedLocalidades();

        $this->getJson('/api/localidades/completo')
            ->assertOk()
            ->assertJsonStructure(['data' => ['ufs', 'municipios_por_uf']]);
    }

    #[Test]
    public function municipios_por_uf_agrupados_corretamente(): void
    {
        $this->seedLocalidades();

        $response = $this->getJson('/api/localidades/completo')->assertOk();

        $this->assertArrayHasKey('GO', $response->json('data.municipios_por_uf'));
        $this->assertCount(2, $response->json('data.municipios_por_uf.GO'));
    }
}
