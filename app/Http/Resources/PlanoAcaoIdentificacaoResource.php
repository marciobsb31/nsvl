<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlanoAcaoIdentificacaoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                     => $this->id,
            'usuario_id'             => $this->usuario_id,
            'orgao_gestor'           => $this->orgao_gestor,
            'secretarias_envolvidas' => $this->secretarias_envolvidas ?? [],
            'vigencia_inicio'        => $this->vigencia_inicio?->format('Y-m-d'),
            'vigencia_fim'           => $this->vigencia_fim?->format('Y-m-d'),
            'pronto_para_envio'      => $this->isProntoParaEnvio(),
            'created_at'             => $this->created_at?->toIso8601String(),
            'updated_at'             => $this->updated_at?->toIso8601String(),
        ];
    }

    private function isProntoParaEnvio(): bool
    {
        return filled($this->orgao_gestor)
            && $this->vigencia_inicio !== null
            && $this->vigencia_fim !== null
            && $this->vigencia_fim->greaterThanOrEqualTo($this->vigencia_inicio);
    }
}
