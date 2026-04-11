<?php

namespace App\Http\Requests;

use App\Models\Perfil;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CadastrarPerfilRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nome'      => ['required', 'string', 'max:100', Rule::in(Perfil::CATALOGO_OFICIAL), 'unique:perfis,nome'],
            'descricao' => ['nullable', 'string', 'max:255'],
            'ativo'     => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'nome.required' => 'Preencha os campos obrigatórios.',
            'nome.in'       => 'O nome deve ser um dos perfis oficiais do sistema.',
            'nome.unique'   => 'Já existe um perfil com este nome.',
            'nome.max'      => 'O nome do perfil deve ter no máximo 100 caracteres.',
        ];
    }
}
