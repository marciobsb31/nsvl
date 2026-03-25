<?php

namespace Tests\Feature\Esfera;

use App\Models\Esfera;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class EsferaTest extends TestCase
{
    #[Test]
    public function lista_esferas_ordenadas(): void
    {
        Esfera::create(['codigo' => 'federal', 'nome' => 'Federal', 'ordem' => 1]);
        Esfera::create(['codigo' => 'estadual', 'nome' => 'Estadual', 'ordem' => 2]);
        Esfera::create(['codigo' => 'municipal', 'nome' => 'Municipal', 'ordem' => 3]);

        $response = $this->getJson('/api/esferas')
            ->assertOk()
            ->assertJsonStructure(['data' => [['value', 'label']]]);

        $this->assertCount(3, $response->json('data'));
        $this->assertEquals('federal', $response->json('data.0.value'));
    }

    #[Test]
    public function endpoint_e_publico(): void
    {
        $this->getJson('/api/esferas')->assertOk();
    }
}
