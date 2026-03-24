<?php

namespace App\Http\Requests;

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
            'nome'          => ['required', 'string', 'max:100', 'unique:perfis,nome'],
            'descricao'     => ['nullable', 'string', 'max:255'],
            'esfera'        => ['required', Rule::in(['federal', 'estadual', 'municipal'])],
            'status'        => ['required', Rule::in(['ativo', 'inativo'])],
            'permissoes'    => ['nullable', 'array'],
            'permissoes.*'  => ['integer', 'exists:permissoes,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'nome.required'    => 'Preencha os campos obrigatórios.',
            'nome.unique'      => 'Já existe um perfil com este nome.',
            'nome.max'         => 'O nome do perfil deve ter no máximo 100 caracteres.',
            'esfera.required'  => 'Selecione a esfera de atuação.',
            'esfera.in'        => 'Esfera inválida.',
            'status.required'  => 'Selecione o status.',
            'status.in'        => 'Status inválido.',
            'permissoes.array' => 'Formato de permissões inválido.',
            'permissoes.*.exists' => 'Permissão selecionada não existe.',
        ];
    }
}
