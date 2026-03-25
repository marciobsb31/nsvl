<?php

namespace Tests\Feature\SolicitacaoCadastro;

use App\Models\SolicitacaoCadastro;
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
            ->assertJsonStructure(['data' => [['id', 'nome', 'status', 'cpf', 'esfera_atuacao']]]);
    }

    #[Test]
    public function usuario_estadual_ve_apenas_solicitacoes_da_mesma_uf(): void
    {
        SolicitacaoCadastro::factory()->estadual('GO')->create();
        SolicitacaoCadastro::factory()->estadual('SP')->create();
        SolicitacaoCadastro::factory()->create(['esfera_atuacao' => 'federal']);

        $user = $this->criarUsuarioEstadual();

        $response = $this->autenticar($user)
            ->getJson('/api/solicitacoes-cadastro')
            ->assertOk();

        $this->assertCount(1, $response->json('data'));
    }

    #[Test]
    public function usuario_municipal_ve_apenas_solicitacoes_do_mesmo_municipio(): void
    {
        SolicitacaoCadastro::factory()->municipal('GO', 'Alexânia')->create();
        SolicitacaoCadastro::factory()->municipal('GO', 'Goiânia')->create();

        $user = $this->criarUsuarioMunicipal();

        $response = $this->autenticar($user)
            ->getJson('/api/solicitacoes-cadastro')
            ->assertOk();

        $this->assertCount(1, $response->json('data'));
    }

    #[Test]
    public function filtra_por_status(): void
    {
        SolicitacaoCadastro::factory()->create(['status' => 'em_analise']);
        SolicitacaoCadastro::factory()->aprovada()->create();

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
        SolicitacaoCadastro::factory()->create(['esfera_atuacao' => 'federal']);
        SolicitacaoCadastro::factory()->estadual()->create();

        $user = $this->criarUsuarioFederal();

        $response = $this->autenticar($user)
            ->getJson('/api/solicitacoes-cadastro?esfera=federal')
            ->assertOk();

        foreach ($response->json('data') as $item) {
            $this->assertEquals('federal', $item['esfera_atuacao']);
        }
    }

    #[Test]
    public function registra_auditoria_na_listagem(): void
    {
        $user = $this->criarUsuarioFederal();

        $this->autenticar($user)->getJson('/api/solicitacoes-cadastro');

        $this->assertDatabaseHas('auditoria_log', [
            'user_id' => $user->id,
            'action'  => 'gerenciar_cadastros.listagem',
        ]);
    }
}
