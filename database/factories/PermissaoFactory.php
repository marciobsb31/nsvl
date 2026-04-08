<?php

namespace Database\Factories;

use App\Models\Permissao;
use Illuminate\Database\Eloquent\Factories\Factory;

class PermissaoFactory extends Factory
{
    protected $model = Permissao::class;

    public function definition(): array
    {
        return [
            'modulo'    => $this->faker->randomElement(['Gerenciar Perfis', 'Gerenciar Cadastros', 'Relatórios', 'Plano de Ação']),
            'acao'      => $this->faker->randomElement(['Visualizar', 'Criar', 'Editar', 'Aprovar']),
            'descricao' => $this->faker->sentence(),
        ];
    }
}
