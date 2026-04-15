<?php

namespace App\Services\Auth;

use App\DTOs\Auth\GovBrUserDTO;
use App\Enums\StatusSolicitacaoEnum;
use App\Models\SolicitacaoCadastro;
use App\Models\Usuario;
use Illuminate\Validation\ValidationException;

class AuthValidationService
{
    public function validarOuFalhar(GovBrUserDTO $govBrUser): Usuario
    {

        $cpf = preg_replace('/\D/', '', (string) ($govBrUser->cpf ?? $govBrUser->sub));
        if (strlen($cpf) !== 11) {
            throw ValidationException::withMessages([
                'auth' => 'Não foi possível identificar o CPF retornado pelo GOV.BR.',
            ]);
        }

        $user = Usuario::where('govbr_sub', $govBrUser->sub)->first();

        if ($user && $user->cpf && $user->cpf !== $cpf) {
            throw ValidationException::withMessages([
                'auth' => 'Os dados do GOV.BR não correspondem ao cadastro existente no sistema.',
            ]);
        }

        if (! $user) {
            $user = Usuario::where('cpf', $cpf)->first();
        }

        if ($user) {
            $this->sincronizarIdentidade($user, $govBrUser, $cpf);

            if ($user->perfisVigentes()->isEmpty()) {

                $ultimaSolicitacao = SolicitacaoCadastro::where('usuario_id', $user->id)
                    ->latest('id')
                    ->first();

                if ($ultimaSolicitacao && $ultimaSolicitacao->status_id === StatusSolicitacaoEnum::EM_ANALISE->value) {
                    throw ValidationException::withMessages([
                        'auth' => 'Solicitação de acesso em análise.',
                    ]);
                }

                if (! $ultimaSolicitacao || $ultimaSolicitacao->status_id === StatusSolicitacaoEnum::REPROVADO->value) {
                    // Reprovado ou sem solicitação → permitir nova solicitação
                    throw ValidationException::withMessages([
                        'auth' => 'Solicitar acesso e aguardar avaliação',
                    ]);
                }

                throw ValidationException::withMessages([
                    'auth' => 'Seu cadastro foi aprovado, mas nenhum perfil de acesso foi configurado. Procure o administrador do sistema.',
                ]);
            }

            return $user->fresh() ?? $user;
        }

        $solicitacao = SolicitacaoCadastro::whereHas('usuario', fn ($q) => $q->where('cpf', $cpf))
            ->latest('id')
            ->first();

        if ($solicitacao) {
            $mensagem = match ($solicitacao->status_id) {
                StatusSolicitacaoEnum::EM_ANALISE->value => 'Solicitação de acesso em análise.',
                StatusSolicitacaoEnum::REPROVADO->value  => 'Sua solicitação de cadastro foi reprovada.',
                StatusSolicitacaoEnum::APROVADO->value   => 'Seu cadastro foi aprovado, mas nenhum perfil de acesso foi configurado. Procure o administrador do sistema.',
                default                                  => 'Seu acesso não pôde ser validado no momento.',
            };

            throw ValidationException::withMessages(['auth' => $mensagem]);
        }

        throw ValidationException::withMessages([
            'auth' => 'Solicitar acesso e aguardar avaliação',
        ]);
    }

    private function sincronizarIdentidade(Usuario $user, GovBrUserDTO $govBrUser, string $cpf): void
    {
        $conflitoSub = Usuario::where('govbr_sub', $govBrUser->sub)
            ->where('id', '<>', $user->id)
            ->exists();

        if ($conflitoSub) {
            throw ValidationException::withMessages([
                'auth' => 'Já existe outro usuário vinculado a esta conta GOV.BR.',
            ]);
        }

        $user->fill([
            'govbr_sub' => $govBrUser->sub,
            'cpf'       => $cpf,
            'nome'      => $govBrUser->name ?: $user->nome,
            'email'     => $govBrUser->email ?: $user->email,
        ]);

        if ($user->isDirty()) {
            $user->save();
        }
    }
}
