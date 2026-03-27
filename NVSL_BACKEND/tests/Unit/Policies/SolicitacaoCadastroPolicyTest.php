<?php

namespace Tests\Unit\Policies;

use App\Models\Esfera;
use App\Models\Municipio;
use App\Models\SolicitacaoCadastro;
use App\Models\Uf;
use App\Policies\SolicitacaoCadastroPolicy;
use Tests\TestCase;
use Tests\Traits\ActingAsUserTrait;
use PHPUnit\Framework\Attributes\Test;

class SolicitacaoCadastroPolicyTest extends TestCase
{
    use ActingAsUserTrait;

    private SolicitacaoCadastroPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new SolicitacaoCadastroPolicy();
    }

    #[Test]
    public function federal_pode_ver_qualquer_solicitacao(): void
    {
        $user = $this->criarUsuarioFederal();
        $esferaMunicipal = Esfera::firstOrCreate(['nome' => 'Municipal']);
        $ufSP = Uf::firstOrCreate(['sigla' => 'SP', 'nome' => 'São Paulo']);
        $municipioCampinas = Municipio::firstOrCreate(['nome' => 'Campinas', 'uf_id' => $ufSP->id]);

        $s = SolicitacaoCadastro::factory()->create([
            'esfera_id'    => $esferaMunicipal->id,
            'uf_id'        => $ufSP->id,
            'municipio_id' => $municipioCampinas->id,
        ]);

        $this->assertTrue($this->policy->view($user, $s));
    }

    #[Test]
    public function estadual_pode_ver_solicitacao_da_mesma_uf(): void
    {
        $user = $this->criarUsuarioEstadual();
        $esferaEstadual = Esfera::firstOrCreate(['nome' => 'Estadual']);
        $ufGO = Uf::firstOrCreate(['sigla' => 'GO', 'nome' => 'Goiás']);

        $s = SolicitacaoCadastro::factory()->create([
            'esfera_id' => $esferaEstadual->id,
            'uf_id'     => $ufGO->id,
        ]);

        $this->assertTrue($this->policy->view($user, $s));
    }

    #[Test]
    public function estadual_nao_pode_ver_solicitacao_de_outra_uf(): void
    {
        $user = $this->criarUsuarioEstadual();
        $esferaEstadual = Esfera::firstOrCreate(['nome' => 'Estadual']);
        $ufSP = Uf::firstOrCreate(['sigla' => 'SP', 'nome' => 'São Paulo']);

        $s = SolicitacaoCadastro::factory()->create([
            'esfera_id' => $esferaEstadual->id,
            'uf_id'     => $ufSP->id,
        ]);

        $this->assertFalse($this->policy->view($user, $s));
    }

    #[Test]
    public function estadual_nao_pode_ver_solicitacao_federal(): void
    {
        $user = $this->criarUsuarioEstadual();
        $esferaFederal = Esfera::firstOrCreate(['nome' => 'Federal']);

        $s = SolicitacaoCadastro::factory()->create([
            'esfera_id' => $esferaFederal->id,
        ]);

        $this->assertFalse($this->policy->view($user, $s));
    }

    #[Test]
    public function municipal_pode_ver_solicitacao_do_mesmo_municipio(): void
    {
        $user = $this->criarUsuarioMunicipal();
        $esferaMunicipal = Esfera::firstOrCreate(['nome' => 'Municipal']);
        $ufGO = Uf::firstOrCreate(['sigla' => 'GO', 'nome' => 'Goiás']);
        $municipioAlexania = Municipio::firstOrCreate(['nome' => 'Alexânia', 'uf_id' => $ufGO->id]);

        $s = SolicitacaoCadastro::factory()->create([
            'esfera_id'    => $esferaMunicipal->id,
            'uf_id'        => $ufGO->id,
            'municipio_id' => $municipioAlexania->id,
        ]);

        $this->assertTrue($this->policy->view($user, $s));
    }

    #[Test]
    public function municipal_nao_pode_ver_solicitacao_de_outro_municipio(): void
    {
        $user = $this->criarUsuarioMunicipal();
        $esferaMunicipal = Esfera::firstOrCreate(['nome' => 'Municipal']);
        $ufGO = Uf::firstOrCreate(['sigla' => 'GO', 'nome' => 'Goiás']);
        $municipioGoiania = Municipio::firstOrCreate(['nome' => 'Goiânia', 'uf_id' => $ufGO->id]);

        $s = SolicitacaoCadastro::factory()->create([
            'esfera_id'    => $esferaMunicipal->id,
            'uf_id'        => $ufGO->id,
            'municipio_id' => $municipioGoiania->id,
        ]);

        $this->assertFalse($this->policy->view($user, $s));
    }

    #[Test]
    public function federal_pode_atualizar_qualquer_solicitacao(): void
    {
        $user = $this->criarUsuarioFederal();
        $s = SolicitacaoCadastro::factory()->create();

        $this->assertTrue($this->policy->update($user, $s));
    }

    #[Test]
    public function estadual_pode_atualizar_solicitacao_da_mesma_uf(): void
    {
        $user = $this->criarUsuarioEstadual();
        $esferaEstadual = Esfera::firstOrCreate(['nome' => 'Estadual']);
        $ufGO = Uf::firstOrCreate(['sigla' => 'GO', 'nome' => 'Goiás']);

        $s = SolicitacaoCadastro::factory()->create([
            'esfera_id' => $esferaEstadual->id,
            'uf_id'     => $ufGO->id,
        ]);

        $this->assertTrue($this->policy->update($user, $s));
    }

    #[Test]
    public function estadual_nao_pode_atualizar_solicitacao_de_outra_uf(): void
    {
        $user = $this->criarUsuarioEstadual();
        $esferaEstadual = Esfera::firstOrCreate(['nome' => 'Estadual']);
        $ufSP = Uf::firstOrCreate(['sigla' => 'SP', 'nome' => 'São Paulo']);

        $s = SolicitacaoCadastro::factory()->create([
            'esfera_id' => $esferaEstadual->id,
            'uf_id'     => $ufSP->id,
        ]);

        $this->assertFalse($this->policy->update($user, $s));
    }
}
