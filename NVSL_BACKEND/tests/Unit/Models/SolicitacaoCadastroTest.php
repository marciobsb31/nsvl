<?php

namespace Tests\Unit\Models;

use App\Models\Esfera;
use App\Models\Municipio;
use App\Models\SolicitacaoCadastro;
use App\Models\StatusSolicitacao;
use App\Models\Uf;
use Tests\TestCase;
use Tests\Traits\ActingAsUserTrait;
use PHPUnit\Framework\Attributes\Test;

class SolicitacaoCadastroTest extends TestCase
{
    use ActingAsUserTrait;

    #[Test]
    public function pode_ser_criada_com_factory(): void
    {
        $s = SolicitacaoCadastro::factory()->create();

        $this->assertDatabaseHas('solicitacoes_cadastro', ['id' => $s->id]);
        $this->assertEquals(StatusSolicitacao::EM_ANALISE, $s->status);
    }

    #[Test]
    public function constantes_de_status_estao_corretas(): void
    {
        $this->assertEquals('em_analise', StatusSolicitacao::EM_ANALISE);
        $this->assertEquals('aprovado', StatusSolicitacao::APROVADO);
        $this->assertEquals('reprovado', StatusSolicitacao::REPROVADO);
    }

    #[Test]
    public function scope_visivel_para_federal_ve_todas(): void
    {
        $esferaFederal = Esfera::firstOrCreate(['nome' => 'Federal']);
        $esferaEstadual = Esfera::firstOrCreate(['nome' => 'Estadual']);
        $esferaMunicipal = Esfera::firstOrCreate(['nome' => 'Municipal']);
        $ufGO = Uf::firstOrCreate(['sigla' => 'GO', 'nome' => 'Goiás']);
        $ufSP = Uf::firstOrCreate(['sigla' => 'SP', 'nome' => 'São Paulo']);
        $municipioSP = Municipio::firstOrCreate(['nome' => 'São Paulo', 'uf_id' => $ufSP->id]);

        SolicitacaoCadastro::factory()->create(['esfera_id' => $esferaFederal->id]);
        SolicitacaoCadastro::factory()->create(['esfera_id' => $esferaEstadual->id, 'uf_id' => $ufGO->id]);
        SolicitacaoCadastro::factory()->create(['esfera_id' => $esferaMunicipal->id, 'uf_id' => $ufSP->id, 'municipio_id' => $municipioSP->id]);

        $user = $this->criarUsuarioFederal();

        $resultado = SolicitacaoCadastro::query()->visivelPara($user)->get();

        $this->assertGreaterThanOrEqual(3, $resultado->count());
    }

    #[Test]
    public function scope_visivel_para_estadual_filtra_por_esfera_e_uf(): void
    {
        $esferaEstadual = Esfera::firstOrCreate(['nome' => 'Estadual']);
        $esferaFederal = Esfera::firstOrCreate(['nome' => 'Federal']);
        $ufGO = Uf::firstOrCreate(['sigla' => 'GO', 'nome' => 'Goiás']);
        $ufSP = Uf::firstOrCreate(['sigla' => 'SP', 'nome' => 'São Paulo']);

        SolicitacaoCadastro::factory()->create(['esfera_id' => $esferaEstadual->id, 'uf_id' => $ufGO->id]);
        SolicitacaoCadastro::factory()->create(['esfera_id' => $esferaEstadual->id, 'uf_id' => $ufSP->id]);
        SolicitacaoCadastro::factory()->create(['esfera_id' => $esferaFederal->id]);

        $user = $this->criarUsuarioEstadual();

        $resultado = SolicitacaoCadastro::query()->visivelPara($user)->get();

        $resultado->each(fn ($s) => $this->assertEquals('GO', $s->uf));
    }

    #[Test]
    public function scope_visivel_para_municipal_filtra_por_esfera_uf_e_municipio(): void
    {
        $esferaMunicipal = Esfera::firstOrCreate(['nome' => 'Municipal']);
        $ufGO = Uf::firstOrCreate(['sigla' => 'GO', 'nome' => 'Goiás']);
        $municipioAlexania = Municipio::firstOrCreate(['nome' => 'Alexânia', 'uf_id' => $ufGO->id]);
        $municipioGoiania = Municipio::firstOrCreate(['nome' => 'Goiânia', 'uf_id' => $ufGO->id]);

        SolicitacaoCadastro::factory()->create([
            'esfera_id' => $esferaMunicipal->id, 'uf_id' => $ufGO->id, 'municipio_id' => $municipioAlexania->id,
        ]);
        SolicitacaoCadastro::factory()->create([
            'esfera_id' => $esferaMunicipal->id, 'uf_id' => $ufGO->id, 'municipio_id' => $municipioGoiania->id,
        ]);

        $user = $this->criarUsuarioMunicipal();

        $resultado = SolicitacaoCadastro::query()->visivelPara($user)->get();

        $resultado->each(fn ($s) => $this->assertEquals('Alexânia', $s->municipio));
    }

    #[Test]
    public function factory_states_funcionam(): void
    {
        $aprovada = SolicitacaoCadastro::factory()->aprovado()->create();
        $reprovada = SolicitacaoCadastro::factory()->reprovado()->create();

        $this->assertEquals('aprovado', $aprovada->status);
        $this->assertEquals('reprovado', $reprovada->status);
    }
}
