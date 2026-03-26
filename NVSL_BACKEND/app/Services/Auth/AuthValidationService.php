<?php

namespace App\Services\Auth;

use App\DTOs\Auth\GovBrUserDTO;
use App\Models\SolicitacaoCadastro;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class AuthValidationService
{
    public function validarOuFalhar(GovBrUserDTO $govBrUser): User
    {
        $cpf = preg_replace('/\D/', '', (string) ($govBrUser->cpf ?? $govBrUser->sub));
        if (strlen($cpf) !== 11) {
            throw ValidationException::withMessages([
                'auth' => 'Não foi possível identificar o CPF retornado pelo GOV.BR.',
            ]);
        }

        $cpfHash = User::hashCpf($cpf);
        $user = User::where('govbr_sub', $govBrUser->sub)->first();

        if ($user && $user->cpf_hash && $user->cpf_hash !== $cpfHash) {
            throw ValidationException::withMessages([
                'auth' => 'Os dados do GOV.BR não correspondem ao cadastro existente no sistema.',
            ]);
        }

        if (!$user) {
            $user = User::where('cpf_hash', $cpfHash)->first();
        }

        if ($user) {
            $this->sincronizarIdentidade($user, $govBrUser, $cpfHash);

            if (!$user->possuiPerfilVigente()) {
                throw ValidationException::withMessages([
                    'auth' => 'Seu usuário não possui perfil ativo no sistema.',
                ]);
            }

            return $user->fresh() ?? $user;
        }

        $solicitacao = SolicitacaoCadastro::where('cpf_hash', $cpfHash)
            ->latest('id')
            ->first();

        if ($solicitacao) {
            $mensagem = match ($solicitacao->status) {
                SolicitacaoCadastro::STATUS_EM_ANALISE => 'Solicitação de acesso em análise.',
                SolicitacaoCadastro::STATUS_REPROVADO => 'Sua solicitação de cadastro foi reprovada.',
                SolicitacaoCadastro::STATUS_APROVADO => 'Seu cadastro foi aprovado, mas nenhum perfil de acesso foi configurado. Procure o administrador do sistema.',
                default => 'Seu acesso não pôde ser validado no momento.',
            };

            throw ValidationException::withMessages(['auth' => $mensagem]);
        }

        throw ValidationException::withMessages([
            'auth' => 'Solicitar acesso e aguardar avaliação',
        ]);
    }

    private function sincronizarIdentidade(User $user, GovBrUserDTO $govBrUser, string $cpfHash): void
    {
        $conflitoSub = User::where('govbr_sub', $govBrUser->sub)
            ->where('id', '<>', $user->id)
            ->exists();

        if ($conflitoSub) {
            throw ValidationException::withMessages([
                'auth' => 'Já existe outro usuário vinculado a esta conta GOV.BR.',
            ]);
        }

        $user->fill([
            'govbr_sub' => $govBrUser->sub,
            'cpf_hash' => $cpfHash,
            'name' => $govBrUser->name ?: $user->name,
            'email' => $govBrUser->email ?: $user->email,
        ]);

        if ($user->isDirty()) {
            $user->save();
        }
    }
}
