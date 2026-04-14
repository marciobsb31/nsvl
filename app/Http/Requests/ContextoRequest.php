<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ContextoRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'contexto_id' => 'required|integer',
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
