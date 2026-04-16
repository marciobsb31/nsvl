<?php

namespace App\Http\Requests;

use App\Enums\StatusSolicitacaoEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AvaliarSolicitacaoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status_id' => [
                'required',
                Rule::in([
                    StatusSolicitacaoEnum::APROVADO->value,
                    StatusSolicitacaoEnum::REPROVADO->value,
                ]),
            ],

            'perfil_id' => [
                Rule::requiredIf(fn () => (int) $this->status_id === StatusSolicitacaoEnum::APROVADO->value),
                'integer',
                'exists:perfis,id',
            ],

            'vigencia_inicio' => [
                'nullable',
                'date',
                Rule::requiredIf(fn () => (int) $this->status_id === StatusSolicitacaoEnum::APROVADO->value),
            ],

            'vigencia_fim' => [
                'nullable',
                'date',
                'after_or_equal:vigencia_inicio',
            ],

            'justificativa' => [
                Rule::requiredIf(fn () => (int) $this->status_id === StatusSolicitacaoEnum::REPROVADO->value),
                'string',
                'min:10',
                'max:1000',
            ],
        ];
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
