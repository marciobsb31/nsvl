<?php

namespace Tests\Unit\Policies;

use App\Models\SolicitacaoCadastro;
use App\Models\User;
use App\Policies\SolicitacaoCadastroPolicy;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class SolicitacaoCadastroPolicyTest extends TestCase
{
    private SolicitacaoCadastroPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new SolicitacaoCadastroPolicy();
    }

    #[Test]
    public function federal_pode_ver_qualquer_solicitacao(): void
    {
        $user = User::factory()->federal()->create();
        $s = SolicitacaoCadastro::factory()->create(['esfera_atuacao' => 'municipal', 'uf' => 'SP', 'municipio' => 'Campinas']);

        $this->assertTrue($this->policy->view($user, $s));
    }

    #[Test]
    public function estadual_pode_ver_solicitacao_da_mesma_uf(): void
    {
        $user = User::factory()->estadual('GO')->create();
        $s = SolicitacaoCadastro::factory()->estadual('GO')->create();

        $this->assertTrue($this->policy->view($user, $s));
    }

    #[Test]
    public function estadual_nao_pode_ver_solicitacao_de_outra_uf(): void
    {
        $user = User::factory()->estadual('GO')->create();
        $s = SolicitacaoCadastro::factory()->estadual('SP')->create();

        $this->assertFalse($this->policy->view($user, $s));
    }

    #[Test]
    public function estadual_nao_pode_ver_solicitacao_federal(): void
    {
        $user = User::factory()->estadual('GO')->create();
        $s = SolicitacaoCadastro::factory()->create(['esfera_atuacao' => 'federal']);

        $this->assertFalse($this->policy->view($user, $s));
    }

    #[Test]
    public function municipal_pode_ver_solicitacao_do_mesmo_municipio(): void
    {
        $user = User::factory()->municipal('GO', 'Alexânia')->create();
        $s = SolicitacaoCadastro::factory()->municipal('GO', 'Alexânia')->create();

        $this->assertTrue($this->policy->view($user, $s));
    }

    #[Test]
    public function municipal_nao_pode_ver_solicitacao_de_outro_municipio(): void
    {
        $user = User::factory()->municipal('GO', 'Alexânia')->create();
        $s = SolicitacaoCadastro::factory()->municipal('GO', 'Goiânia')->create();

        $this->assertFalse($this->policy->view($user, $s));
    }

    #[Test]
    public function federal_pode_atualizar_qualquer_solicitacao(): void
    {
        $user = User::factory()->federal()->create();
        $s = SolicitacaoCadastro::factory()->create();

        $this->assertTrue($this->policy->update($user, $s));
    }

    #[Test]
    public function estadual_pode_atualizar_solicitacao_da_mesma_uf(): void
    {
        $user = User::factory()->estadual('GO')->create();
        $s = SolicitacaoCadastro::factory()->estadual('GO')->create();

        $this->assertTrue($this->policy->update($user, $s));
    }

    #[Test]
    public function estadual_nao_pode_atualizar_solicitacao_de_outra_uf(): void
    {
        $user = User::factory()->estadual('GO')->create();
        $s = SolicitacaoCadastro::factory()->estadual('SP')->create();

        $this->assertFalse($this->policy->update($user, $s));
    }
}
