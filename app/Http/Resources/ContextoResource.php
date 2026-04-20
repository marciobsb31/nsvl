<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContextoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'perfil_usuario_id' => $this->perfil_usuario_id ? (int) $this->perfil_usuario_id : null,
            'perfil'            => $this->perfilUsuario?->perfil?->nome,
            'esfera'            => $this->when($this->abrangencia?->esfera, function () {
                return new EsferaResource($this->abrangencia->esfera);
            }),
            'localidade'   => $this->abrangencia?->nome,
            'uf_id'        => $this->abrangencia?->uf_id ? (int) $this->abrangencia->uf_id : null,
            'municipio_id' => $this->abrangencia?->municipio_id ? (int) $this->abrangencia->municipio_id : null,
        ];
    }
}
