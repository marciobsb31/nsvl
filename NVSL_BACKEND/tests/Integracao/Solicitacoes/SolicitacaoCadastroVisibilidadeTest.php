<?php

namespace Tests\Integracao\Solicitacoes;

use App\Models\SolicitacaoCadastro;
use App\Policies\SolicitacaoCadastroPolicy;
use PHPUnit\Framework\Attributes\Test;
use Tests\Integracao\TestCase;

class SolicitacaoCadastroVisibilidadeTest extends TestCase
{
    #[Test]
    public function scope_visivel_para_nao_restringe_usuario_federal(): void
    {
        $usuario = $this->usuarioPorSub('teste-federal-001');

        $ids = SolicitacaoCadastro::query()
            ->visivelPara($usuario)
            ->pluck('id');

        $this->assertGreaterThan(2, $ids->count());
    }

    #[Test]
    public function scope_visivel_para_restringe_usuario_estadual_para_mesma_uf(): void
    {
        $usuario = $this->usuarioPorSub('teste-estadual-go-002');

        $itens = SolicitacaoCadastro::query()
            ->visivelPara($usuario)
            ->get();

        $this->assertNotEmpty($itens);
        $this->assertTrue($itens->every(fn (SolicitacaoCadastro $solicitacao) => $solicitacao->uf === 'GO'));
        $this->assertTrue($itens->every(fn (SolicitacaoCadastro $solicitacao) => strtolower($solicitacao->esfera_atuacao) === 'Estadual' || strtolower($solicitacao->esfera_atuacao) === 'estadual'));
    }

    #[Test]
    public function scope_visivel_para_restringe_usuario_municipal_para_mesmo_municipio(): void
    {
        $usuario = $this->usuarioPorSub('teste-municipal-alexania-003');

        $itens = SolicitacaoCadastro::query()
            ->visivelPara($usuario)
            ->get();

        $this->assertNotEmpty($itens);
        $this->assertTrue($itens->every(fn (SolicitacaoCadastro $solicitacao) => $solicitacao->uf === 'GO'));
        $this->assertTrue($itens->every(fn (SolicitacaoCadastro $solicitacao) => $solicitacao->municipio === 'Alexânia'));
    }

    #[Test]
    public function policy_permte_estadual_na_mesma_uf_e_nega_outras_esferas(): void
    {
        $policy = new SolicitacaoCadastroPolicy();
        $estadual = $this->usuarioPorSub('teste-estadual-go-002');

        $permitida = SolicitacaoCadastro::query()
            ->whereHas('esfera', fn ($query) => $query->where('nome', 'Estadual'))
            ->whereHas('ufRelacao', fn ($query) => $query->where('sigla', 'GO'))
            ->firstOrFail();

        $negada = SolicitacaoCadastro::query()
            ->whereHas('esfera', fn ($query) => $query->where('nome', 'Federal'))
            ->firstOrFail();

        $this->assertTrue($policy->view($estadual, $permitida));
        $this->assertFalse($policy->update($estadual, $negada));
    }

    #[Test]
    public function policy_permte_municipal_apenas_no_mesmo_municipio(): void
    {
        $policy = new SolicitacaoCadastroPolicy();
        $municipal = $this->usuarioPorSub('teste-municipal-alexania-003');

        $permitida = SolicitacaoCadastro::query()
            ->whereHas('esfera', fn ($query) => $query->where('nome', 'Municipal'))
            ->whereHas('ufRelacao', fn ($query) => $query->where('sigla', 'GO'))
            ->whereHas('municipioRelacao', fn ($query) => $query->where('nome', 'Alexânia'))
            ->firstOrFail();

        $negada = SolicitacaoCadastro::query()
            ->whereHas('esfera', fn ($query) => $query->where('nome', 'Estadual'))
            ->firstOrFail();

        $this->assertTrue($policy->view($municipal, $permitida));
        $this->assertFalse($policy->view($municipal, $negada));
    }
}
