<?php

namespace App\Support;

use App\Models\Usuario;

class MvpPerfilRules
{
    /**
     * Ordem canônica dos perfis MVP.
     *
     * @return array<int, string>
     */
    public static function mvpProfileCodes(): array
    {
        return [
            'gestor_federal',
            'gestor_estadual',
            'gestor_municipal',
            'admin_estadual',
            'admin_municipal',
            'visitante_federal',
            'visitante_estadual',
            'visitante_municipal',
        ];
    }

    /**
     * RN01: perfis que cada avaliador pode atribuir.
     *
     * @return array<int, string>
     */
    public static function allowedTargetCodesForEvaluator(?string $evaluatorProfileCode): array
    {
        return match ($evaluatorProfileCode) {
            'gestor_federal'  => self::mvpProfileCodes(),
            'gestor_estadual' => [
                'gestor_estadual',
                'admin_estadual',
                'visitante_estadual',
            ],
            'gestor_municipal' => [
                'gestor_municipal',
                'admin_municipal',
                'visitante_municipal',
            ],
            default => [],
        };
    }

    public static function canEvaluatorAssign(?string $evaluatorProfileCode, string $targetProfileCode): bool
    {
        return in_array($targetProfileCode, self::allowedTargetCodesForEvaluator($evaluatorProfileCode), true);
    }

    public static function resolveActiveProfileCode(?Usuario $user): ?string
    {
        if (! $user) {
            return null;
        }

        $user->loadMissing('contextoAtivo.perfilUsuario.perfil');

        return $user->contextoAtivo?->perfilUsuario?->perfil?->codigo;
    }
}
