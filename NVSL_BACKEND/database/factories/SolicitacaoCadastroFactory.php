<?php

namespace Database\Factories;

use App\Models\Esfera;
use App\Models\SolicitacaoCadastro;
use App\Models\StatusSolicitacao;
use App\Models\Uf;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SolicitacaoCadastro>
 */
class SolicitacaoCadastroFactory extends Factory
{
    protected $model = SolicitacaoCadastro::class;

    public function definition(): array
    {
        $statusEmAnalise = StatusSolicitacao::where('nome', 'em_analise')->first();

        return [
            'user_id'                => Usuario::factory(),
            'email_institucional'    => fake()->companyEmail(),
            'telefone_institucional' => fake()->numerify('###########'),
            'esfera_id'              => Esfera::inRandomOrder()->first()?->id ?? 1,
            'uf_id'                  => Uf::inRandomOrder()->first()?->id ?? 1,
            'municipio_id'           => null,
            'orgao'                  => fake()->company(),
            'cargo'                  => fake()->jobTitle(),
            'status_id'              => $statusEmAnalise?->id ?? 1,
            'aceite_termo_at'        => now(),
        ];
    }

    public function aprovado(): static
    {
        return $this->state(function () {
            $statusAprovado = StatusSolicitacao::where('nome', 'aprovado')->first();
            return ['status_id' => $statusAprovado?->id ?? 2];
        });
    }

    public function reprovado(): static
    {
        return $this->state(function () {
            $statusReprovado = StatusSolicitacao::where('nome', 'reprovado')->first();
            return [
                'status_id'               => $statusReprovado?->id ?? 3,
                'justificativa_reprovacao' => fake()->sentence(),
            ];
        });
    }
}
