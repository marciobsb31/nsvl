<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlanoAcaoDiagnosticoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                       => $this->id,
            'usuario_id'               => $this->usuario_id,
            'caracterizacao_populacao' => $this->caracterizacao_populacao,
            'barreiras_urbanisticas'   => $this->barreiras_urbanisticas ?? [],
            'barreiras_transportes'    => $this->barreiras_transportes ?? [],
            'barreiras_atitudinais'    => $this->barreiras_atitudinais ?? [],
            'barreiras_arquitetonicas' => $this->barreiras_arquitetonicas ?? [],
            'barreiras_comunicacoes'   => $this->barreiras_comunicacoes ?? [],
            'barreiras_tecnologicas'   => $this->barreiras_tecnologicas ?? [],
            'outras_barreiras'         => $this->outras_barreiras,
            'pronto_para_envio'        => $this->isProntoParaEnvio(),
            'created_at'               => $this->created_at?->toIso8601String(),
            'updated_at'               => $this->updated_at?->toIso8601String(),
        ];
    }

    private function isProntoParaEnvio(): bool
    {
        if (!filled($this->caracterizacao_populacao)) {
            return false;
        }

        $grupos = [
            $this->barreiras_urbanisticas   ?? [],
            $this->barreiras_transportes    ?? [],
            $this->barreiras_atitudinais    ?? [],
            $this->barreiras_arquitetonicas ?? [],
            $this->barreiras_comunicacoes   ?? [],
            $this->barreiras_tecnologicas   ?? [],
        ];

        foreach ($grupos as $grupo) {
            if (!empty($grupo)) {
                return true;
            }
        }

        return false;
    }
}
