<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PerfilUsuarioResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                   => $this->id,
            'nome'                 => $this->perfil?->nome,
            'descricao'            => $this->perfil?->descricao,
            'localidade'           => $this->abrangencia?->nome ?? 'Âmbito Nacional',
            'esfera'               => $this->abrangencia?->esfera?->nome,
            'ativo'                => $this->ativo,
            'data_inicio_vigencia' => $this->data_inicio_vigencia,
            'data_fim_vigencia'    => $this->data_fim_vigencia,
        ];
    }
}
