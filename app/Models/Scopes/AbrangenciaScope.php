<?php

namespace App\Models\Scopes;

use App\Enums\EsferaEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class AbrangenciaScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $usuario = Auth::user();

        if (! $usuario) {
            return;
        }

        $contexto = $this->getContexto($usuario);

        if (! $contexto || ! $contexto->abrangencia || $contexto->abrangencia->esfera?->codigo === EsferaEnum::FEDERAL) {
            return;
        }

        $abrangencia = $contexto->abrangencia;
        $esfera = $abrangencia->esfera->codigo;
        $tabela = $model->getTable();

        match ($esfera) {
            'estadual'  => $builder->where("{$tabela}.uf_id", $abrangencia->uf_id),
            'municipal' => $builder->where("{$tabela}.uf_id", $abrangencia->uf_id)
                ->where("{$tabela}.municipio_id", $abrangencia->municipio_id),

            default => $builder->whereRaw('1 = 0'),
        };
    }

    private function getContexto($usuario): ?object
    {
        return once(function () use ($usuario) {
            return $usuario->contextoAtivo()
                ->with(['abrangencia.esfera'])
                ->first();
        });
    }
}
