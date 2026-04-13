<?php

namespace App\Enums;

enum StatusSolicitacaoEnum: int
{
    case PENDENTE = 1;
    case EM_ANALISE = 2;
    case APROVADO = 3;
    case REPROVADO = 4;

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
