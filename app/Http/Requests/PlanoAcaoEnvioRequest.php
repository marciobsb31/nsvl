<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PlanoAcaoEnvioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'responsavel_nome'     => ['nullable', 'string', 'max:255'],
            'responsavel_cargo'    => ['nullable', 'string', 'max:255'],
            'responsavel_orgao'    => ['nullable', 'string', 'max:255'],
            'responsavel_contato'  => ['nullable', 'string', 'max:255'],
            'justificativa_eixo_1' => ['nullable', 'string', 'max:8000'],
            'justificativa_eixo_2' => ['nullable', 'string', 'max:8000'],
            'justificativa_eixo_3' => ['nullable', 'string', 'max:8000'],
            'justificativa_eixo_4' => ['nullable', 'string', 'max:8000'],
        ];
    }
}
