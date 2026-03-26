<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'govbr_sub' => $this->faker->unique()->numerify('###########'),
            'cpf_hash'  => hash_hmac('sha256', $this->faker->numerify('###########'), 'testing'),
            'name'      => $this->faker->name(),
            'email'     => $this->faker->unique()->safeEmail(),
            'role'      => 'user',
            'esfera_atuacao' => 'federal',
        ];
    }

    public function admin(): static
    {
        return $this->state(fn () => ['role' => 'admin']);
    }

    public function federal(): static
    {
        return $this->state(fn () => [
            'esfera_atuacao' => 'federal',
            'uf_lotacao' => null,
            'municipio_lotacao' => null,
        ]);
    }

    public function estadual(string $uf = 'GO'): static
    {
        return $this->state(fn () => [
            'esfera_atuacao' => 'estadual',
            'uf_lotacao' => $uf,
            'municipio_lotacao' => null,
        ]);
    }

    public function municipal(string $uf = 'GO', string $municipio = 'Alexânia'): static
    {
        return $this->state(fn () => [
            'esfera_atuacao' => 'municipal',
            'uf_lotacao' => $uf,
            'municipio_lotacao' => $municipio,
        ]);
    }
}
