<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class AbrangenciaScope implements Scope
{
    public function apply(Builder $builder, Model $model)
    {
        $usuario = auth()->user();

        if (! $usuario) {
            return;
        }

        $contexto = $this->getContexto();

        if (! $contexto?->abrangencia) {
            return;
        }

        $abrangencia = $contexto->abrangencia;
        $esfera = $abrangencia->esfera?->codigo;

        $tabela = $model->getTable();
        if ($esfera === 'estadual') {
            $builder->where("{$tabela}.uf_id", $abrangencia->uf_id);

            return;
        }

        match ($esfera) {
            'federal'  => null,
            'estadual' => $builder->where(
                $model->getTable().'.uf_id',
                $abrangencia->uf_id
            ),
            'municipal' => $builder->where(
                $model->getTable().'.uf_id',
                $abrangencia->uf_id
            )->where(
                $model->getTable().'.municipio_id',
                $abrangencia->municipio_id
            ),
            default => $builder->whereRaw('1 = 0'),
        };
    }

    private function getContexto(): ?object
    {
        return once(function () {
            return auth()->user()?->contexto()
                ->with(['abrangencia.esfera'])
                ->first();
        });
    }
}
