<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SolicitacaoCadastroResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                     => $this->id,
            'nome'                   => $this->nome,
            'cpf'                    => $this->usuario->cpf,
            'telefone_institucional' => $this->telefone_institucional,
            'telefone_pessoal'       => $this->telefone_pessoal,
            'email'                  => $this->email_institucional,
            'esfera'                 => EsferaResource::make($this->esfera),
            'estado'                 => EstadoResource::make($this->ufRelacao),
            'municipio'              => MunicipioResource::make($this->municipioRelacao),
            'orgao'                  => $this->orgao,
            'cargo'                  => $this->cargo,
            'status'                 => StatusSolicitacaoResource::make($this->statusSolicitacao),
            'motivo_reprovacao'      => $this->justificativa,
        ];
    }
}
