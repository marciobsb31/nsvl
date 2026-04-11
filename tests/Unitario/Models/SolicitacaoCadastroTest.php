<?php

namespace Tests\Unitario\Models;

use App\Models\Esfera;
use App\Models\Municipio;
use App\Models\SolicitacaoCadastro;
use App\Models\StatusSolicitacao;
use App\Models\Uf;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use PHPUnit\Framework\Attributes\Test;
use Tests\Unitario\TestCase;

class SolicitacaoCadastroTest extends TestCase
{
    #[Test]
    public function retorna_acessores_de_conveniencia_a_partir_das_relacoes_ou_defaults(): void
    {
        $solicitacao = new SolicitacaoCadastro;
        $solicitacao->setRelation('statusSolicitacao', new StatusSolicitacao(['nome' => StatusSolicitacao::APROVADO]));
        $solicitacao->setRelation('usuario', new Usuario(['nome' => 'Maria Teste']));
        $solicitacao->setRelation('esfera', new Esfera(['nome' => 'Municipal']));
        $solicitacao->setRelation('ufRelacao', new Uf(['sigla' => 'GO']));
        $solicitacao->setRelation('municipioRelacao', new Municipio(['nome' => 'Alexânia']));

        $this->assertSame('aprovado', $solicitacao->status);
        $this->assertSame('Maria Teste', $solicitacao->nome);
        $this->assertSame('Municipal', $solicitacao->esfera_atuacao);
        $this->assertSame('GO', $solicitacao->uf);
        $this->assertSame('GO', $solicitacao->uf_sigla);
        $this->assertSame('Alexânia', $solicitacao->municipio);
        $this->assertSame('Alexânia', $solicitacao->municipio_nome);
    }

    #[Test]
    public function usa_status_padrao_quando_a_relacao_nao_esta_carregada(): void
    {
        $solicitacao = new SolicitacaoCadastro;

        $this->assertSame(StatusSolicitacao::EM_ANALISE, $solicitacao->status);
        $this->assertSame('', $solicitacao->nome);
        $this->assertSame('', $solicitacao->esfera_atuacao);
        $this->assertSame('', $solicitacao->uf);
        $this->assertSame('', $solicitacao->municipio);
    }

    #[Test]
    public function expoe_relacoes_belongs_to_esperadas(): void
    {
        $solicitacao = new SolicitacaoCadastro;

        $this->assertInstanceOf(BelongsTo::class, $solicitacao->usuario());
        $this->assertInstanceOf(BelongsTo::class, $solicitacao->esfera());
        $this->assertInstanceOf(BelongsTo::class, $solicitacao->ufRelacao());
        $this->assertInstanceOf(BelongsTo::class, $solicitacao->municipioRelacao());
        $this->assertInstanceOf(BelongsTo::class, $solicitacao->perfilSolicitado());
        $this->assertInstanceOf(BelongsTo::class, $solicitacao->statusSolicitacao());
    }
}
