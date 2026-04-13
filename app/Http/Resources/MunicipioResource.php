<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MunicipioResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'     => $this->id,
            'nome'   => $this->nome,
            'estado' => new EstadoResource($this->whenLoaded('estadoRelacao')),
        ];
    }
}
