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
            'email_institucional'    => ['required', 'email', 'max:255'],
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

    public function attributes(): array
    {
        return [
            'perfilId'               => 'perfil',
            'vigenciaInicio'         => 'vigência inicial',
            'vigenciaFim'            => 'vigência fim',
            'emailInstitucional'     => 'e-mail institucional',
            'telefoneInstitucional'  => 'telefone institucional',
            'telefonePessoal'        => 'telefone pessoal',
            'esferaAtuacao'          => 'esfera de atuação',
        ];
    }

    public function rules(): array
    {
        $user = Auth::guard('sanctum')->user();

        // Auto-cadastro: usuário autenticado via GOV.BR submetendo para si mesmo.
        // Nesse fluxo perfil e vigência são opcionais (atribuídos pelo gestor na avaliação).
        $cpfInput = preg_replace('/\D/', '', $this->input('CPF', ''));
        $ehAutoCadastro = $user !== null && $user->cpf === $cpfInput;

        // Cadastro interno via painel (Gestor criando para outro) requer perfil e vigência.
        $ehCadastroInterno = $user !== null && !$ehAutoCadastro;

        $cpfRules = ['required', 'string', 'regex:/^\d{11}$/', function ($attr, $value, $fail) {
            if (!CpfHelper::validar($value)) {
                $fail('O CPF informado é inválido.');
            }
        }];

        $perfilRules = $ehCadastroInterno
            ? ['required', 'integer', 'exists:perfis,id']
            : ['nullable', 'integer', 'exists:perfis,id'];

        $vigenciaInicioRules = $ehCadastroInterno
            ? ['required', 'date']
            : ['nullable', 'date'];

        return [
            'nome'                  => ['required', 'string', 'max:255'],
            'CPF'                   => $cpfRules,
            'emailInstitucional'    => ['required', 'email', 'max:255'],
            'telefoneInstitucional' => ['required', 'string', 'regex:/^\d{10,11}$/', 'max:20'],
            'telefonePessoal'       => ['nullable', 'string', 'regex:/^\d{10,11}$/', 'max:20'],
            'esferaAtuacao'         => ['required', Rule::in(['federal', 'estadual', 'municipal'])],
            'uf'                    => ['required', 'string', 'size:2'],
            'municipio'             => ['required', 'string', 'max:100'],
            'orgao'                 => ['required', 'string', 'max:255'],
            'cargo'                 => ['required', 'string', 'max:255'],
            'perfilId'              => $perfilRules,
            'vigenciaInicio'        => $vigenciaInicioRules,
            'vigenciaFim'           => ['nullable', 'date', 'after_or_equal:vigenciaInicio'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $cpf = $this->input('CPF');
        if (is_string($cpf)) {
            $this->merge(['CPF' => preg_replace('/\D/', '', $cpf)]);
        }
        $tel = $this->input('telefoneInstitucional');
        if (is_string($tel)) {
            $this->merge(['telefoneInstitucional' => preg_replace('/\D/', '', $tel)]);
        }
        $telP = $this->input('telefonePessoal');
        if ($telP === null || $telP === '') {
            $this->merge(['telefonePessoal' => null]);
        } elseif (is_string($telP)) {
            $digits = preg_replace('/\D/', '', $telP);
            $this->merge(['telefonePessoal' => $digits === '' ? null : $digits]);
        }

        $uf = $this->input('uf');
        if (is_string($uf)) {
            $this->merge(['uf' => strtoupper(trim($uf))]);
        }

        $municipio = $this->input('municipio');
        if (is_string($municipio)) {
            $this->merge(['municipio' => trim($municipio)]);
        }
    }
}
