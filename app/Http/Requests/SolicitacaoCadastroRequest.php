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
            'cpf'                    => Helpers::onlyDigits($this->cpf ?? ''),
            'telefoneInstitucional' => Helpers::onlyDigits($this->telefone_institucional ?? ''),
            'telefonePessoal'       => Helpers::onlyDigits($this->telefone_pessoal ?? ''),
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

            'nome'                  => ['required', 'string', 'max:255'],
            'emailInstitucional'    => ['required', 'email', 'max:255'],
            'telefoneInstitucional' => ['nullable', 'string', 'max:20'],
            'telefonePessoal'       => ['nullable', 'string', 'max:20'],

            'esfera_id' => ['required', 'integer', Rule::in(array_column(EsferaEnum::cases(), 'value'))],

            'uf_id' => [
                $esfera?->requiresUf() ? 'required' : 'nullable',
                'exists:ufs,id',
            ],

            'municipio_id' => [
                $esfera?->requiresMunicipio() ? 'required' : 'nullable',
                'exists:municipios,id',
            ],

            'perfil_id'             => ['required', 'exists:perfis,id'],
            'orgao'                 => ['required', 'string', 'max:255'],
            'cargo'                 => ['nullable', 'string', 'max:255'],
            'status_solicitacao_id' => ['required', 'exists:status_solicitacao,id'],
            'vigenciaInicio'        => ['required', 'date', 'after_or_equal:today'],
            'vigenciaFim'           => ['nullable', 'date', 'after:vigenciaInicio'],
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

            'vigenciaInicio.required'       => 'A vigência inicial é obrigatória.',
            'vigenciaInicio.date'           => 'A vigência inicial deve ser uma data válida.',
            'vigenciaInicio.after_or_equal' => 'A vigência inicial deve ser hoje ou uma data futura.',

            'vigenciaFim.date'  => 'A vigência final deve ser uma data válida.',
            'vigenciaFim.after' => 'A vigência final deve ser posterior à vigência inicial.',
        ];
    }
}
