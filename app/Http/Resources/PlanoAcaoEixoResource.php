<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlanoAcaoEixoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'usuario_id'       => $this->usuario_id,
            'eixo_numero'      => $this->eixo_numero,
            'acoes'            => $this->acoes ?? [],
            'total_acoes'      => count($this->acoes ?? []),
            'pronto_para_envio' => $this->isProntoParaEnvio(),
            'created_at'       => $this->created_at?->toIso8601String(),
            'updated_at'       => $this->updated_at?->toIso8601String(),
        ];
    }

    private function isProntoParaEnvio(): bool
    {
        return !empty($this->acoes);
    }
}
