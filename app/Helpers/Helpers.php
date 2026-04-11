<?php

namespace App\Helpers;

class Helpers
{
    public static function onlyDigits(string $value): string
    {
        return preg_replace('/\D+/', '', $value);
    }

    public static function hashCpf(string $cpf): string
    {
        $cpf = self::onlyDigits($cpf);

        return hash_hmac('sha256', $cpf, config('app.key'));
    }
}
