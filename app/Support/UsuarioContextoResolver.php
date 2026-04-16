<?php

namespace App\Support;

use App\Models\Usuario;

class UsuarioContextoResolver
{
    public static function garantirContextoValido(Usuario $usuario): void
    {
        $hoje = now()->toDateString();

        $perfisVigentes = $usuario->perfisUsuario()
            ->where('ativo', true)
            ->where(function ($q) use ($hoje) {
                $q->whereNull('data_inicio_vigencia')
                    ->orWhereDate('data_inicio_vigencia', '<=', $hoje);
            })
            ->where(function ($q) use ($hoje) {
                $q->whereNull('data_fim_vigencia')
                    ->orWhereDate('data_fim_vigencia', '>=', $hoje);
            })
            ->orderBy('id')
            ->get();

        if ($perfisVigentes->isEmpty()) {
            return;
        }

        $contextoAtual = $usuario->contextoAtivo()->first();
        $contextoValido = $contextoAtual
            && $perfisVigentes->contains(fn ($perfil) => (int) $perfil->id === (int) $contextoAtual->perfil_usuario_id);

        if ($contextoValido) {
            return;
        }

        $perfilPadrao = $perfisVigentes->first();

        $usuario->contextoAtivo()->updateOrCreate(
            ['usuario_id' => $usuario->id],
            [
                'perfil_usuario_id' => $perfilPadrao->id,
                'usuario_abrangencia_id' => $perfilPadrao->usuario_abrangencia_id,
            ]
        );

        $usuario->unsetRelation('contextoAtivo');
    }
}
