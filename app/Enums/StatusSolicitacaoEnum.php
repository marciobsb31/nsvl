<?php

namespace App\Enums;

enum StatusSolicitacaoEnum: int
{
    case EM_ANALISE = 1;
    case APROVADO = 2;
    case REPROVADO = 3;

    public function label(): string
    {
        return match ($this) {
            self::EM_ANALISE => 'Em análise',
            self::APROVADO   => 'Aprovado',
            self::REPROVADO  => 'Reprovado',
        };
    }
}
