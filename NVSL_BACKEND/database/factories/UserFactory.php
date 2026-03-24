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
            'picture'   => null,
            'role'      => 'user',
        ];
    }

    public function admin(): static
    {
        return $this->state(fn () => ['role' => 'admin']);
    }
}
