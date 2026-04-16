<?php

namespace App\Policies;

use App\Models\SolicitacaoCadastro;
use App\Models\Usuario;

class SolicitacaoCadastroPolicy
{
    public function view(Usuario $user, SolicitacaoCadastro $solicitacao): bool
    {
        return $this->verificarVisibilidade($user, $solicitacao);
    }

    public function update(Usuario $user, SolicitacaoCadastro $solicitacao): bool
    {
        return $user->hasPermissao('solicitacoes_cadastro.analisar')
            && $this->verificarVisibilidade($user, $solicitacao);
    }

    private function verificarVisibilidade(Usuario $user, SolicitacaoCadastro $solicitacao): bool
    {
        $user->loadMissing('contextoAtivo.abrangencia.esfera');
        $abrangencia = $user->contextoAtivo?->abrangencia;
        $esfera = strtolower((string) ($abrangencia?->esfera?->codigo ?? ''));

        if ($esfera === 'federal') {
            return true;
        }

        if ($esfera === 'estadual') {
            return (int) $solicitacao->uf_id === (int) $abrangencia?->uf_id;
        }

        if ($esfera === 'municipal') {
            return (int) $solicitacao->uf_id === (int) $abrangencia?->uf_id
                && (int) $solicitacao->municipio_id === (int) $abrangencia?->municipio_id;
        }

        return false;
    }
}
