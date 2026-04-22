<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PlanoAcaoEixoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'acoes'                                    => ['nullable', 'array'],
            'acoes.*.id'                               => ['required', 'string', 'max:64'],
            'acoes.*.nome'                             => ['nullable', 'string', 'max:500'],
            'acoes.*.acao_nvsl'                        => ['nullable', 'string', 'max:500'],
            'acoes.*.acao_nvsl_outra'                  => ['nullable', 'string'],
            'acoes.*.meta'                             => ['nullable', 'string'],
            'acoes.*.metas_por_ano'                    => ['nullable', 'array'],
            'acoes.*.metas_por_ano.*.ano'              => ['nullable', 'string', 'max:10'],
            'acoes.*.metas_por_ano.*.descricao'        => ['nullable', 'string'],
            'acoes.*.orgaos_locais'                    => ['nullable', 'array'],
            'acoes.*.orgaos_locais.*'                  => ['string', 'max:255'],
            'acoes.*.orgaos_federais'                  => ['nullable', 'array'],
            'acoes.*.orgaos_federais.*'                => ['string', 'max:255'],
            'acoes.*.indicador_produto'                => ['nullable', 'string'],
            'acoes.*.indicador_resultado'              => ['nullable', 'string'],
            'acoes.*.vigencia'                         => ['nullable', 'string', 'max:100'],
            'acoes.*.orcamentos'                       => ['nullable', 'array'],
            'acoes.*.orcamentos.*.orcamento_estimado'  => ['nullable', 'string', 'max:100'],
            'acoes.*.orcamentos.*.fonte_recurso'       => ['nullable', 'string', 'max:500'],
        ];
    }
}
