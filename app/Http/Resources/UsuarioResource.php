<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UsuarioResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'          => $this->id,
            'name'        => $this->nome,
            'email'       => $this->email,
            'sub'         => $this->govbr_sub,
            'perfis'      => PerfilUsuarioResource::collection($this->whenLoaded('perfisUsuario')),
            'contexto'    => ContextoResource::make($this->contextoAtivo),
            'permissions' => $this->when(
                $this->contextoAtivo,
                fn () => $this->contextoAtivo->perfilUsuario
                    ->perfil
                    ->permissoes
                    ->pluck('codigo')
            ),
        ];
    }
}
