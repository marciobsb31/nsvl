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
        return $this->verificarVisibilidade($user, $solicitacao);
    }

    private function verificarVisibilidade(Usuario $user, SolicitacaoCadastro $solicitacao): bool
    {
        $esfera = strtolower($user->esfera_atuacao ?? 'federal');

        if ($esfera === 'federal') {
            return true;
        }

        $solEsfera = strtolower($solicitacao->esfera?->nome ?? '');
        $solUfSigla = $solicitacao->ufRelacao?->sigla ?? '';
        $userUf = $user->uf_lotacao ?? '';

        if ($esfera === 'estadual') {
            return $solEsfera === 'estadual' && $solUfSigla === $userUf;
        }

        if ($esfera === 'municipal') {
            $solMunicipio = $solicitacao->municipioRelacao?->nome ?? '';
            $userMunicipio = $user->municipio_lotacao ?? '';

            return $solEsfera === 'municipal'
                && $solUfSigla === $userUf
                && $solMunicipio === $userMunicipio;
        }

        return false;
    }
}
