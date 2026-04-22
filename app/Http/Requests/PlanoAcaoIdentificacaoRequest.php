<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class PlanoAcaoIdentificacaoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'orgao_gestor'              => ['required', 'string', 'max:255'],
            'secretarias_envolvidas'    => ['nullable', 'array'],
            'secretarias_envolvidas.*'  => ['required', 'string', 'max:255'],
            'vigencia_inicio'           => ['required', 'date'],
            'vigencia_fim'              => ['required', 'date', 'after_or_equal:vigencia_inicio'],
        ];
    }

    public function messages(): array
    {
        return [
            'orgao_gestor.required'   => 'Campo obrigatório não preenchido.',
            'vigencia_inicio.required' => 'Campo obrigatório não preenchido.',
            'vigencia_fim.required'   => 'Campo obrigatório não preenchido.',
            'vigencia_fim.after_or_equal' => 'A data final deve ser maior ou igual à data inicial.',
        ];
    }
}
