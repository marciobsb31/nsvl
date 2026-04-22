<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PlanoAcaoDiagnosticoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'caracterizacao_populacao'  => ['nullable', 'string'],
            'barreiras_urbanisticas'    => ['nullable', 'array'],
            'barreiras_urbanisticas.*'  => ['string', 'max:255'],
            'barreiras_transportes'     => ['nullable', 'array'],
            'barreiras_transportes.*'   => ['string', 'max:255'],
            'barreiras_atitudinais'     => ['nullable', 'array'],
            'barreiras_atitudinais.*'   => ['string', 'max:255'],
            'barreiras_arquitetonicas'  => ['nullable', 'array'],
            'barreiras_arquitetonicas.*' => ['string', 'max:255'],
            'barreiras_comunicacoes'    => ['nullable', 'array'],
            'barreiras_comunicacoes.*'  => ['string', 'max:255'],
            'barreiras_tecnologicas'    => ['nullable', 'array'],
            'barreiras_tecnologicas.*'  => ['string', 'max:255'],
            'outras_barreiras'          => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'barreiras_urbanisticas.array'   => 'Barreiras urbanísticas deve ser uma lista.',
            'barreiras_transportes.array'    => 'Barreiras nos transportes deve ser uma lista.',
            'barreiras_atitudinais.array'    => 'Barreiras atitudinais deve ser uma lista.',
            'barreiras_arquitetonicas.array' => 'Barreiras arquitetônicas deve ser uma lista.',
            'barreiras_comunicacoes.array'   => 'Barreiras nas comunicações deve ser uma lista.',
            'barreiras_tecnologicas.array'   => 'Barreiras tecnológicas deve ser uma lista.',
        ];
    }
}
