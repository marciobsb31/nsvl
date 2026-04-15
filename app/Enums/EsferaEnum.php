<?php

namespace App\Enums;

enum EsferaEnum: int
{
    case FEDERAL = 1;
    case ESTADUAL = 2;
    case MUNICIPAL = 3;

    public function requiresUf(): bool
    {
        return match ($this) {
            self::FEDERAL => false,
            self::ESTADUAL, self::MUNICIPAL => true,
        };
    }

    public function requiresMunicipio(): bool
    {
        return $this === self::MUNICIPAL;
    }
}
