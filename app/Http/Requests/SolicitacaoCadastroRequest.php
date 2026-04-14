<?php

namespace App\Http\Requests;

use App\Enums\EsferaEnum;
use App\Helpers\Helpers;
use App\Rules\CpfValidoRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SolicitacaoCadastroRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'cpf'                   => Helpers::onlyDigits($this->cpf ?? ''),
            'telefone_institucional' => Helpers::onlyDigits($this->telefone_institucional ?? ''),
            'telefone_pessoal'       => Helpers::onlyDigits($this->telefone_pessoal ?? ''),
        ]);
    }

    public function rules(): array
    {
        $esfera = $this->getEsferaEnum();

        return [
            'cpf' => [
                'required',
                'string',
                'size:11',
                new CpfValidoRule,
            ],

            'nome'                   => ['required', 'string', 'max:255'],
            'email_institucional'     => ['required', 'email', 'max:255'],
            'telefone_institucional' => ['nullable', 'string', 'max:20'],
            'telefone_pessoal'       => ['nullable', 'string', 'max:20'],

            'esfera_id' => ['required', 'integer', Rule::in(array_column(EsferaEnum::cases(), 'value'))],
            'uf_id'     => [$esfera?->requiresUf() ? 'required' : 'nullable', 'exists:ufs,id'],

            'municipio_id' => [$esfera?->requiresMunicipio() ? 'required' : 'nullable', 'exists:municipios,id'],

            'perfil_id'             => ['nullable', 'exists:perfis,id'],
            'orgao'                 => ['required', 'string', 'max:255'],
            'cargo'                 => ['nullable', 'string', 'max:255'],
            'status_solicitacao_id' => ['nullable', 'exists:status_solicitacao,id'],
            'vigencia_inicio'       => ['nullable', 'date', 'after_or_equal:today'],
            'vigencia_fim'          => ['nullable', 'date', 'after:vigenciaInicio'],
        ];
    }

    private function getEsferaEnum(): ?EsferaEnum
    {
        return EsferaEnum::tryFrom((int) $this->input('esfera_id'));
    }

    public function messages(): array
    {
        return [
            'cpf.required'             => 'O CPF é obrigatório.',
            'cpf.size'                 => 'O CPF deve conter 11 dígitos.',
            'uf_id.required'           => 'O estado é obrigatório para esta esfera.',
            'uf_id.exists'             => 'O estado selecionado é inválido.',
            'municipio_id.required'    => 'O município é obrigatório para esta esfera.',
            'municipio_id.exists'      => 'O município selecionado é inválido.',
            'emailInstitucional.email' => 'Informe um e-mail institucional válido.',

            'vigencia_inicio.date'           => 'A vigência inicial deve ser uma data válida.',
            'vigencia_inicio.after_or_equal' => 'A vigência inicial deve ser hoje ou uma data futura.',

            'vigencia_fim.date'  => 'A vigência final deve ser uma data válida.',
            'vigencia_fim.after' => 'A vigência final deve ser posterior à vigência inicial.',
        ];
    }
}
