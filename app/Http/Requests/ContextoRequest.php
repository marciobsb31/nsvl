<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ContextoRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->filled('contexto_id') && ! $this->filled('perfil_usuario_id')) {
            $this->merge([
                'perfil_usuario_id' => $this->input('contexto_id'),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'perfil_usuario_id' => 'required|integer|exists:perfil_usuario,id',
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
