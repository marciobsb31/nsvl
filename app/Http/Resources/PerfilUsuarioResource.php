<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PerfilUsuarioResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $hoje = now()->toDateString();
        $inicio = $this->data_inicio_vigencia ? (string) $this->data_inicio_vigencia : null;
        $fim = $this->data_fim_vigencia ? (string) $this->data_fim_vigencia : null;
        $vigente =
            ($inicio === null || $inicio <= $hoje)
            && ($fim === null || $fim >= $hoje);

        return [
            'id'                   => $this->id,
            'nome'                 => $this->perfil?->nome,
            'descricao'            => $this->perfil?->descricao,
            'localidade'           => $this->abrangencia?->nome ?? 'Âmbito Nacional',
            'esfera'               => $this->abrangencia?->esfera?->nome,
            'uf'                   => $this->abrangencia?->uf?->sigla,
            'municipio'            => $this->abrangencia?->municipio?->nome,
            'orgao'                => $this->solicitacaoCadastroOrigem?->orgao,
            'ativo'                => $this->ativo,
            'vigente'              => $vigente,
            'data_inicio_vigencia' => $this->data_inicio_vigencia,
            'data_fim_vigencia'    => $this->data_fim_vigencia,
        ];
    }
}
