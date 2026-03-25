<?php

namespace Database\Factories;

use App\Models\Perfil;
use Illuminate\Database\Eloquent\Factories\Factory;

class PerfilFactory extends Factory
{
    protected $model = Perfil::class;

    public function definition(): array
    {
        return [
            'nome'      => $this->faker->unique()->words(3, true),
            'descricao' => $this->faker->sentence(),
            'esfera'    => $this->faker->randomElement(['federal', 'estadual', 'municipal']),
            'status'    => 'ativo',
        ];
    }

    public function federal(): static
    {
        return $this->state(fn () => ['esfera' => 'federal']);
    }

    public function estadual(): static
    {
        return $this->state(fn () => ['esfera' => 'estadual']);
    }

    public function municipal(): static
    {
        return $this->state(fn () => ['esfera' => 'municipal']);
    }

    public function inativo(): static
    {
        return $this->state(fn () => ['status' => 'inativo']);
    }
}
