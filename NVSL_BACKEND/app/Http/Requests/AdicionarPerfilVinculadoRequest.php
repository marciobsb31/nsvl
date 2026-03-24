<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdicionarPerfilVinculadoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'perfil_id'       => ['required', 'integer', 'exists:perfis,id'],
            'vigencia_inicio' => ['nullable', 'date'],
            'vigencia_fim'    => ['nullable', 'date', 'after_or_equal:vigencia_inicio'],
        ];
    }

    public function messages(): array
    {
        return [
            'perfil_id.required' => 'Selecione o perfil a vincular.',
            'perfil_id.exists'   => 'Perfil informado é inválido.',
        ];
    }
}
