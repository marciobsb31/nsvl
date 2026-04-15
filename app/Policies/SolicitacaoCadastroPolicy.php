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

    /**
     * Apenas gestores podem avaliar (aprovar/reprovar) solicitações
     */
    public function update(Usuario $user, SolicitacaoCadastro $solicitacao): bool
    {
        // Visitantes e Administradores não podem avaliar
        if ($user->isVisitante()) {
            return false;
        }

        // Apenas Gestores podem avaliar
        if (!$user->isGestor()) {
            return false;
        }

        return $this->verificarVisibilidade($user, $solicitacao);
    }

    private function verificarVisibilidade(Usuario $user, SolicitacaoCadastro $solicitacao): bool
    {
        $esfera = mb_strtolower(trim((string) $user->esfera_atuacao), 'UTF-8');

        if ($user->isPerfilFederalAtivo() || $esfera === 'federal') {
            return true;
        }

        $solEsfera = mb_strtolower(trim((string) ($solicitacao->esfera?->nome ?? '')), 'UTF-8');
        $solUfSigla = strtoupper(trim((string) ($solicitacao->ufRelacao?->sigla ?? '')));
        $userUf = strtoupper(trim((string) $user->uf_lotacao));

        if ($esfera === 'estadual') {
            return $solEsfera === 'estadual' && $solUfSigla === $userUf;
        }

        if ($esfera === 'municipal') {
            $solMunicipio = trim((string) ($solicitacao->municipioRelacao?->nome ?? ''));
            $userMunicipio = trim((string) $user->municipio_lotacao);
            return $solEsfera === 'municipal'
                && $solUfSigla === $userUf
                && $solMunicipio === $userMunicipio;
        }

        return false;
    }
}
