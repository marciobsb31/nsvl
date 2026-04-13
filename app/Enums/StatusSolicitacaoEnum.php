<?php

namespace App\Enums;

enum StatusSolicitacaoEnum: string
{
    case EM_ANALISE = 'em_analise';
    case APROVADO = 'aprovado';
    case REPROVADO = 'reprovado';
    case PENDENTE = 'pendente';

    public function label(): string
    {
        return match ($this) {
            self::EM_ANALISE => 'Em análise',
            self::APROVADO   => 'Aprovado',
            self::REPROVADO  => 'Reprovado',
            self::PENDENTE   => 'Pendente',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::EM_ANALISE => 'warning',
            self::APROVADO   => 'success',
            self::REPROVADO  => 'danger',
            self::PENDENTE   => 'info',
        };
    }
}
