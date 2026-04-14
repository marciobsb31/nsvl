<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UsuarioResource extends JsonResource
{
    public function toArray($request)
    {
        $contexto = $this->contextoAtivo;

        return [
            'id'     => $this->id,
            'name'   => $this->nome,
            'email'  => $this->email,
            'sub'    => $this->govbr_sub,
            'perfis' => PerfilUsuarioResource::collection(
                $this->whenLoaded('perfisUsuario')
            ),

            'contexto' => $this->when(
                $contexto,
                function () use ($contexto) {
                    return [
                        'perfil'     => $contexto->perfilUsuario?->perfil?->nome,
                        'esfera'     => $contexto->abrangencia?->esfera?->nome,
                        'localidade' => $contexto->abrangencia?->nome,
                    ];
                }
            ),

            'permissions' => $this->when(
                $contexto,
                fn () => $contexto->perfilUsuario
                    ->perfil
                    ->permissoes
                    ->pluck('codigo')
            ),
        ];
    }
}
