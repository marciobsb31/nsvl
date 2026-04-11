<?php

namespace Tests\Integracao;

use App\Models\Perfil;
use App\Models\SolicitacaoCadastro;
use App\Models\StatusSolicitacao;
use App\Models\Usuario;
use Database\Seeders\TestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected const SUB_FEDERAL = '11144477735';

    protected const SUB_ESTADUAL = '52998224725';

    protected const SUB_MUNICIPAL = '98765432100';

    protected bool $seed = true;

    protected string $seeder = TestSeeder::class;

    protected function autenticarComoFederal(): Usuario
    {
        return $this->autenticarComo(self::SUB_FEDERAL);
    }

    protected function autenticarComoEstadual(): Usuario
    {
        return $this->autenticarComo(self::SUB_ESTADUAL);
    }

    protected function autenticarComoMunicipal(): Usuario
    {
        return $this->autenticarComo(self::SUB_MUNICIPAL);
    }

    protected function autenticarComo(string $govbrSub): Usuario
    {
        $usuario = $this->usuarioPorSub($govbrSub);

        Sanctum::actingAs($usuario);

        return $usuario;
    }

    protected function usuarioPorSub(string $govbrSub): Usuario
    {
        $govbrSub = match ($govbrSub) {
            'teste-federal-001'            => self::SUB_FEDERAL,
            'teste-estadual-go-002'        => self::SUB_ESTADUAL,
            'teste-municipal-alexania-003' => self::SUB_MUNICIPAL,
            default                        => $govbrSub,
        };

        return Usuario::query()
            ->where('govbr_sub', $govbrSub)
            ->firstOrFail();
    }

    protected function perfilPorNome(string $nome): Perfil
    {
        return Perfil::query()
            ->where('nome', $nome)
            ->firstOrFail();
    }

    protected function solicitacaoPorCpfEStatus(string $cpf, string $status): SolicitacaoCadastro
    {
        $statusId = StatusSolicitacao::idPorNome($status);

        return SolicitacaoCadastro::query()
            ->whereHas('usuario', fn ($query) => $query->where('cpf', $cpf))
            ->where('status_id', $statusId)
            ->firstOrFail();
    }
}
