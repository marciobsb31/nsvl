<?php

namespace App\Rules;

use App\Helpers\Helpers;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class CpfValidoRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $cpf = Helpers::onlyDigits($value);

        if (strlen($cpf) !== 11) {
            $fail('O CPF informado é inválido.');

            return;
        }

        if (preg_match('/^(\d)\1{10}$/', $cpf)) {
            $fail('O CPF informado é inválido.');

            return;
        }

        $soma = 0;
        for ($i = 0; $i < 9; $i++) {
            $soma += (int) $cpf[$i] * (10 - $i);
        }
        $resto = $soma % 11;
        $digito1 = $resto < 2 ? 0 : 11 - $resto;

        if ((int) $cpf[9] !== $digito1) {
            $fail('O CPF informado é inválido.');

            return;
        }

        $soma = 0;
        for ($i = 0; $i < 10; $i++) {
            $soma += (int) $cpf[$i] * (11 - $i);
        }
        $resto = $soma % 11;
        $digito2 = $resto < 2 ? 0 : 11 - $resto;

        if ((int) $cpf[10] !== $digito2) {
            $fail('O CPF informado é inválido.');
        }
    }
}
