<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PerfilResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                   => $this->id,
            'nome'                 => $this->nome,
            'descricao'            => $this->descricao,
            'ativo'                => $this->pivot->ativo ?? null,
            'data_inicio_vigencia' => $this->pivot->data_inicio_vigencia ?? null,
            'data_fim_vigencia'    => $this->pivot->data_fim_vigencia ?? null,
        ];
    }
}
