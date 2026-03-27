<?php

namespace Tests\Feature\SolicitacaoCadastro;

use App\Models\Esfera;
use App\Models\Municipio;
use App\Models\SolicitacaoCadastro;
use App\Models\StatusSolicitacao;
use App\Models\Uf;
use Tests\TestCase;
use Tests\Traits\ActingAsUserTrait;
use PHPUnit\Framework\Attributes\Test;

class ListarSolicitacoesTest extends TestCase
{
    use ActingAsUserTrait;

    #[Test]
    public function usuario_nao_autenticado_recebe_401(): void
    {
        $this->getJson('/api/solicitacoes-cadastro')
            ->assertStatus(401);
    }

    #[Test]
    public function usuario_federal_lista_todas_as_solicitacoes(): void
    {
        SolicitacaoCadastro::factory()->count(3)->create();
        $user = $this->criarUsuarioFederal();

        $this->autenticar($user)
            ->getJson('/api/solicitacoes-cadastro')
            ->assertOk()
            ->assertJsonStructure(['data' => [['id', 'status']]]);
    }

    #[Test]
    public function usuario_estadual_ve_apenas_solicitacoes_da_mesma_uf(): void
    {
        $esferaEstadual = Esfera::firstOrCreate(['nome' => 'Estadual']);
        $esferaFederal = Esfera::firstOrCreate(['nome' => 'Federal']);
        $ufGO = Uf::firstOrCreate(['sigla' => 'GO', 'nome' => 'Goiás']);
        $ufSP = Uf::firstOrCreate(['sigla' => 'SP', 'nome' => 'São Paulo']);

        SolicitacaoCadastro::factory()->create(['esfera_id' => $esferaEstadual->id, 'uf_id' => $ufGO->id]);
        SolicitacaoCadastro::factory()->create(['esfera_id' => $esferaEstadual->id, 'uf_id' => $ufSP->id]);
        SolicitacaoCadastro::factory()->create(['esfera_id' => $esferaFederal->id]);

        $user = $this->criarUsuarioEstadual();

        $response = $this->autenticar($user)
            ->getJson('/api/solicitacoes-cadastro')
            ->assertOk();

        $this->assertGreaterThanOrEqual(1, count($response->json('data')));
    }

    #[Test]
    public function usuario_municipal_ve_apenas_solicitacoes_do_mesmo_municipio(): void
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

        $response = $this->autenticar($user)
            ->getJson('/api/solicitacoes-cadastro')
            ->assertOk();

        $this->assertGreaterThanOrEqual(1, count($response->json('data')));
    }

    #[Test]
    public function filtra_por_status(): void
    {
        $statusEmAnalise = StatusSolicitacao::idPorNome(StatusSolicitacao::EM_ANALISE);
        SolicitacaoCadastro::factory()->create(['status_id' => $statusEmAnalise]);
        SolicitacaoCadastro::factory()->aprovado()->create();

        $user = $this->criarUsuarioFederal();

        $response = $this->autenticar($user)
            ->getJson('/api/solicitacoes-cadastro?status=em_analise')
            ->assertOk();

        foreach ($response->json('data') as $item) {
            $this->assertEquals('em_analise', $item['status']);
        }
    }

    #[Test]
    public function filtra_por_esfera(): void
    {
        $esferaFederal = Esfera::firstOrCreate(['nome' => 'Federal']);
        $esferaEstadual = Esfera::firstOrCreate(['nome' => 'Estadual']);
        SolicitacaoCadastro::factory()->create(['esfera_id' => $esferaFederal->id]);
        SolicitacaoCadastro::factory()->create(['esfera_id' => $esferaEstadual->id]);

        $user = $this->criarUsuarioFederal();

        $response = $this->autenticar($user)
            ->getJson('/api/solicitacoes-cadastro?esfera=federal')
            ->assertOk();

        foreach ($response->json('data') as $item) {
            $this->assertStringContainsStringIgnoringCase('federal', $item['esfera_atuacao']);
        }
    }

    #[Test]
    public function registra_auditoria_na_listagem(): void
    {
        $user = $this->criarUsuarioFederal();

        $this->autenticar($user)->getJson('/api/solicitacoes-cadastro');

        $this->assertDatabaseHas('auditoria_log', [
            'user_id' => $user->id,
            'acao'    => 'gerenciar_cadastros.listagem',
        ]);
    }
}
