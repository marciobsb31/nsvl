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
        $this->assertGreaterThanOrEqual(3, Esfera::count());

        $response = $this->getJson('/api/esferas')
            ->assertOk()
            ->assertJsonStructure(['data' => [['value', 'label']]]);

        $values = collect($response->json('data'))->pluck('value')->all();
        $this->assertContains('federal', $values);
        $this->assertContains('estadual', $values);
        $this->assertContains('municipal', $values);
        $this->assertEquals('federal', $response->json('data.0.value'));
    }

    #[Test]
    public function endpoint_e_publico(): void
    {
        $this->getJson('/api/esferas')->assertOk();
    }
}
