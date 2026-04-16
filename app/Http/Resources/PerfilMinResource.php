<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PerfilMinResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'        => $this->id,
            'nome'      => $this->nome,
            'codigo'    => $this->codigo,
            'descricao' => $this->descricao,
            'ativo'     => $this->ativo,
            'esfera_id' => $this->esfera_id,
        ];
    }
}
