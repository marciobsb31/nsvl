<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContextoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'perfil'       => $this->perfilUsuario?->perfil?->nome,
            'esfera'       => $this->abrangencia?->esfera?->nome,
            'localidade'   => $this->abrangencia?->nome,
            'uf_id'        => $this->abrangencia?->uf_id ? (int) $this->abrangencia->uf_id : null,
            'municipio_id' => $this->abrangencia?->municipio_id ? (int) $this->abrangencia->municipio_id : null,
        ];
    }
}
