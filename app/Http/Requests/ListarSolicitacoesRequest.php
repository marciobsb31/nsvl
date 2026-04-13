<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListarSolicitacoesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cpf'       => ['nullable', 'string', 'max:14'],
            'nome'      => ['nullable', 'string', 'max:255'],
            'uf'        => ['nullable', 'string', 'size:2'],
            'municipio' => ['nullable', 'string', 'max:100'],
            'orgao'     => ['nullable', 'string', 'max:255'],
            'esfera'    => ['nullable', 'in:federal,estadual,municipal'],
            'status'    => ['nullable', 'in:em_analise,aprovado,reprovado'],
            'per_page'  => ['nullable', 'integer', 'min:1', 'max:100'],
            'page'      => ['nullable', 'integer', 'min:1'],
            'sort'      => ['nullable', 'string', 'in:id,nome,orgao,created_at'],
            'direction' => ['nullable', 'string', 'in:asc,desc'],
        ];
    }
}
