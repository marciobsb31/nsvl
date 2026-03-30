<?php

namespace App\Http\Requests;

use App\Helpers\CpfHelper;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class SolicitacaoCadastroRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function messages(): array
    {
        return [
            'perfilId.required'       => 'Selecione o perfil.',
            'perfilId.exists'         => 'Perfil informado é inválido.',
            'vigenciaInicio.required' => 'Informe a vigência inicial.',
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
        $autenticado = Auth::guard('sanctum')->check();
        $cpfRules = ['required', 'string', 'regex:/^\d{11}$/', function ($attr, $value, $fail) {
            if (!CpfHelper::validar($value)) {
                $fail('O CPF informado é inválido.');
            }
        }];

        $perfilRules = $autenticado
            ? ['required', 'integer', 'exists:perfis,id']
            : ['nullable', 'integer', 'exists:perfis,id'];

        $vigenciaInicioRules = $autenticado
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
