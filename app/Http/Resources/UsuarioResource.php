<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UsuarioResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'     => $this->id,
            'name'   => $this->nome,
            'email'  => $this->email,
            'sub'    => $this->govbr_sub,
            'perfis' => PerfilResource::collection(
                $this->whenLoaded('perfis')
            ),
            'permissions' => $this->when(
                $this->relationLoaded('perfis'),
                fn () => $this->getAllPermissions()
            ),
        ];
    }
}
