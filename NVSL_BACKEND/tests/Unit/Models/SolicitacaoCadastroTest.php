<?php

namespace Tests\Unit\Models;

use App\Models\SolicitacaoCadastro;
use App\Models\User;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class SolicitacaoCadastroTest extends TestCase
{
    #[Test]
    public function pode_ser_criada_com_factory(): void
    {
        $s = SolicitacaoCadastro::factory()->create();

        $this->assertDatabaseHas('solicitacoes_cadastro', ['id' => $s->id]);
        $this->assertEquals(SolicitacaoCadastro::STATUS_EM_ANALISE, $s->status);
    }

    #[Test]
    public function constantes_de_status_estao_corretas(): void
    {
        $this->assertEquals('em_analise', SolicitacaoCadastro::STATUS_EM_ANALISE);
        $this->assertEquals('aprovado', SolicitacaoCadastro::STATUS_APROVADO);
        $this->assertEquals('reprovado', SolicitacaoCadastro::STATUS_REPROVADO);
    }

    #[Test]
    public function cpf_mascarado_usa_exibicao_quando_preenchido(): void
    {
        $s = SolicitacaoCadastro::factory()->create(['cpf_exibicao' => '123.456.789-01']);
        $this->assertEquals('123.456.789-01', $s->cpf_mascarado);
    }

    #[Test]
    public function cpf_mascarado_retorna_asteriscos_quando_exibicao_vazia(): void
    {
        $s = SolicitacaoCadastro::factory()->create(['cpf_exibicao' => null]);
        $this->assertEquals('***.***.***-**', $s->cpf_mascarado);
    }

    #[Test]
    public function scope_visivel_para_federal_ve_todas(): void
    {
        SolicitacaoCadastro::factory()->create(['esfera_atuacao' => 'federal']);
        SolicitacaoCadastro::factory()->create(['esfera_atuacao' => 'estadual', 'uf' => 'GO']);
        SolicitacaoCadastro::factory()->create(['esfera_atuacao' => 'municipal', 'uf' => 'SP', 'municipio' => 'São Paulo']);

        $user = User::factory()->federal()->create();

        $resultado = SolicitacaoCadastro::query()->visivelPara($user)->get();

        $this->assertCount(3, $resultado);
    }

    #[Test]
    public function scope_visivel_para_estadual_filtra_por_esfera_e_uf(): void
    {
        SolicitacaoCadastro::factory()->create(['esfera_atuacao' => 'estadual', 'uf' => 'GO']);
        SolicitacaoCadastro::factory()->create(['esfera_atuacao' => 'estadual', 'uf' => 'SP']);
        SolicitacaoCadastro::factory()->create(['esfera_atuacao' => 'federal']);

        $user = User::factory()->estadual('GO')->create();

        $resultado = SolicitacaoCadastro::query()->visivelPara($user)->get();

        $this->assertCount(1, $resultado);
        $this->assertEquals('GO', $resultado->first()->uf);
    }

    #[Test]
    public function scope_visivel_para_municipal_filtra_por_esfera_uf_e_municipio(): void
    {
        SolicitacaoCadastro::factory()->create([
            'esfera_atuacao' => 'municipal', 'uf' => 'GO', 'municipio' => 'Alexânia',
        ]);
        SolicitacaoCadastro::factory()->create([
            'esfera_atuacao' => 'municipal', 'uf' => 'GO', 'municipio' => 'Goiânia',
        ]);

        $user = User::factory()->municipal('GO', 'Alexânia')->create();

        $resultado = SolicitacaoCadastro::query()->visivelPara($user)->get();

        $this->assertCount(1, $resultado);
        $this->assertEquals('Alexânia', $resultado->first()->municipio);
    }

    #[Test]
    public function factory_states_funcionam(): void
    {
        $aprovada = SolicitacaoCadastro::factory()->aprovada()->create();
        $reprovada = SolicitacaoCadastro::factory()->reprovada()->create();
        $estadual = SolicitacaoCadastro::factory()->estadual('SP')->create();

        $this->assertEquals('aprovado', $aprovada->status);
        $this->assertEquals('reprovado', $reprovada->status);
        $this->assertEquals('estadual', $estadual->esfera_atuacao);
        $this->assertEquals('SP', $estadual->uf);
    }

    #[Test]
    public function datas_vigencia_sao_castadas_corretamente(): void
    {
        $s = SolicitacaoCadastro::factory()->create([
            'vigencia_inicio_solicitada' => '2026-01-01',
            'vigencia_fim_solicitada'    => '2026-12-31',
        ]);

        $this->assertInstanceOf(\Carbon\Carbon::class, $s->vigencia_inicio_solicitada);
        $this->assertInstanceOf(\Carbon\Carbon::class, $s->vigencia_fim_solicitada);
    }
}
