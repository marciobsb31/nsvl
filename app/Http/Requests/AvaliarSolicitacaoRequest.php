<?php

namespace App\Http\Requests;

use App\Models\StatusSolicitacao;
use Illuminate\Foundation\Http\FormRequest;

class AvaliarSolicitacaoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'status' => ['required', 'in:aprovado,reprovado'],
        ];

        if ($this->input('status') === StatusSolicitacao::APROVADO) {
            $rules['perfil_id'] = ['required', 'integer', 'exists:perfis,id'];
            $rules['vigencia_inicio'] = ['nullable', 'date'];
            $rules['vigencia_fim'] = ['nullable', 'date', 'after_or_equal:vigencia_inicio'];
        }

        if ($this->input('status') === StatusSolicitacao::REPROVADO) {
            $rules['justificativa'] = ['required', 'string', 'min:10', 'max:1000'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'status.required'        => 'Status inválido. Use: aprovado ou reprovado.',
            'status.in'              => 'Status inválido. Use: aprovado ou reprovado.',
            'perfil_id.required'     => 'Selecione o perfil para aprovação.',
            'perfil_id.exists'       => 'Perfil informado é inválido.',
            'justificativa.required' => 'A justificativa é obrigatória para reprovação.',
            'justificativa.min'      => 'A justificativa deve ter pelo menos 10 caracteres.',
        ];
    }
}
