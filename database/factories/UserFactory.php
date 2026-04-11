<?php

namespace Database\Factories;

use App\Models\Usuario;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Usuario>
 */
class UserFactory extends Factory
{
    protected $model = Usuario::class;

    public function definition(): array
    {
        $cpf = $this->gerarCpfValido();

        return [
            'cpf'       => $cpf,
            'nome'      => fake()->name(),
            'email'     => fake()->unique()->safeEmail(),
            'govbr_sub' => 'teste-'.fake()->unique()->uuid(),
            'telefone'  => fake()->optional()->numerify('###########'),
        ];
    }

    public function federal(): static
    {
        return $this->state(fn () => []);
    }

    public function estadual(): static
    {
        return $this->state(fn () => []);
    }

    public function municipal(): static
    {
        return $this->state(fn () => []);
    }

    private function gerarCpfValido(): string
    {
        $n = [];
        for ($i = 0; $i < 9; $i++) {
            $n[] = random_int(0, 9);
        }
        $d1 = 0;
        for ($i = 0; $i < 9; $i++) {
            $d1 += $n[$i] * (10 - $i);
        }
        $d1 = ((10 * $d1) % 11) % 10;
        $n[] = $d1;

        $d2 = 0;
        for ($i = 0; $i < 10; $i++) {
            $d2 += $n[$i] * (11 - $i);
        }
        $d2 = ((10 * $d2) % 11) % 10;
        $n[] = $d2;

        return implode('', $n);
    }
}
