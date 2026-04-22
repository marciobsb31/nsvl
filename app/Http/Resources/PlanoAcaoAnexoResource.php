<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlanoAcaoAnexoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'usuario_id'    => $this->usuario_id,
            'nome_original' => $this->nome_original,
            'tipo_mime'     => $this->tipo_mime,
            'tamanho_bytes' => $this->tamanho_bytes,
            'created_at'    => $this->created_at?->toIso8601String(),
        ];
    }
}
