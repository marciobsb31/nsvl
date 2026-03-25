<?php

namespace Database\Factories;

use App\Models\SolicitacaoCadastro;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SolicitacaoCadastroFactory extends Factory
{
    protected $model = SolicitacaoCadastro::class;

    public function definition(): array
    {
        $cpf = $this->gerarCpfValido();

        return [
            'cpf_hash'               => User::hashCpf($cpf),
            'cpf_exibicao'           => substr($cpf, 0, 3) . '.' . substr($cpf, 3, 3) . '.' . substr($cpf, 6, 3) . '-' . substr($cpf, 9, 2),
            'nome'                   => $this->faker->name(),
            'email_institucional'    => $this->faker->unique()->safeEmail(),
            'telefone_institucional' => $this->faker->numerify('61#########'),
            'telefone_pessoal'       => $this->faker->optional()->numerify('61#########'),
            'esfera_atuacao'         => 'federal',
            'uf'                     => 'DF',
            'municipio'              => 'Brasília',
            'orgao'                  => $this->faker->company(),
            'cargo'                  => $this->faker->jobTitle(),
            'status'                 => SolicitacaoCadastro::STATUS_EM_ANALISE,
            'aceite_termo_at'        => now(),
        ];
    }

    public function aprovada(): static
    {
        return $this->state(fn () => ['status' => SolicitacaoCadastro::STATUS_APROVADO]);
    }

    public function reprovada(): static
    {
        return $this->state(fn () => [
            'status' => SolicitacaoCadastro::STATUS_REPROVADO,
            'justificativa_reprovacao' => 'Documentação insuficiente para aprovação.',
        ]);
    }

    public function estadual(string $uf = 'GO'): static
    {
        return $this->state(fn () => [
            'esfera_atuacao' => 'estadual',
            'uf' => $uf,
        ]);
    }

    public function municipal(string $uf = 'GO', string $municipio = 'Alexânia'): static
    {
        return $this->state(fn () => [
            'esfera_atuacao' => 'municipal',
            'uf' => $uf,
            'municipio' => $municipio,
        ]);
    }

    private function gerarCpfValido(): string
    {
        $n = [];
        for ($i = 0; $i < 9; $i++) {
            $n[] = random_int(0, 9);
        }

        for ($t = 9; $t < 11; $t++) {
            $d = 0;
            for ($c = 0; $c < $t; $c++) {
                $d += $n[$c] * (($t + 1) - $c);
            }
            $n[$t] = ((10 * $d) % 11) % 10;
        }

        return implode('', $n);
    }
}
