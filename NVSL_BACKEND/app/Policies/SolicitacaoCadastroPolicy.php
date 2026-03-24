<?php

namespace App\Policies;

use App\Models\SolicitacaoCadastro;
use App\Models\User;

class SolicitacaoCadastroPolicy
{
    public function view(User $user, SolicitacaoCadastro $solicitacao): bool
    {
        return $this->verificarVisibilidade($user, $solicitacao);
    }

    public function update(User $user, SolicitacaoCadastro $solicitacao): bool
    {
        return $this->verificarVisibilidade($user, $solicitacao);
    }

    private function verificarVisibilidade(User $user, SolicitacaoCadastro $solicitacao): bool
    {
        $esfera = $user->esfera_atuacao ?? 'federal';

        if ($esfera === 'federal') {
            return true;
        }

        if ($esfera === 'estadual') {
            return $solicitacao->esfera_atuacao === 'estadual'
                && $solicitacao->uf === ($user->uf_lotacao ?? '');
        }

        if ($esfera === 'municipal') {
            return $solicitacao->esfera_atuacao === 'municipal'
                && $solicitacao->uf === ($user->uf_lotacao ?? '')
                && $solicitacao->municipio === ($user->municipio_lotacao ?? '');
        }

        return false;
    }
}
