<?php

namespace App\DTOs\Auth;

/**
 * GovBrUserDTO — Objeto de Transferência de Dados do SSO GOV.BR
 *
 * Encapsula as informações do usuário retornadas pelo endpoint /userinfo
 * do OIDC do GOV.BR, sem lógica de negócio.
 */
readonly class GovBrUserDTO
{
    public function __construct(
        /** Identificador único do usuário no SSO (equivale ao CPF como sub) */
        public readonly string $sub,
        /** Nome completo */
        public readonly string $name,
        /** E-mail (pode não estar disponível) */
        public readonly ?string $email = null,
        /** Nível de autenticação */
        public readonly ?array $amr = null,
        /** CPF em texto claro — usado APENAS para gerar o hash, nunca persistido */
        public readonly ?string $cpf = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            sub: $data['sub'] ?? '',
            name: $data['name'] ?? '',
            email: $data['email'] ?? null,
            amr: $data['amr'] ?? null,
            cpf: $data['sub'] ?? null, // GOV.BR retorna o CPF como 'sub'
        );
    }
}
