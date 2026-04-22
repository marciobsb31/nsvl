<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlanoAcaoEnvioResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                   => $this->id,
            'usuario_id'           => $this->usuario_id,
            'responsavel_nome'     => $this->responsavel_nome,
            'responsavel_cargo'    => $this->responsavel_cargo,
            'responsavel_orgao'    => $this->responsavel_orgao,
            'responsavel_contato'  => $this->responsavel_contato,
            'justificativa_eixo_1' => $this->justificativa_eixo_1,
            'justificativa_eixo_2' => $this->justificativa_eixo_2,
            'justificativa_eixo_3' => $this->justificativa_eixo_3,
            'justificativa_eixo_4' => $this->justificativa_eixo_4,
            'status'               => $this->status,
            'enviado_em'           => $this->enviado_em?->toIso8601String(),
            'created_at'           => $this->created_at?->toIso8601String(),
            'updated_at'           => $this->updated_at?->toIso8601String(),
        ];
    }
}
