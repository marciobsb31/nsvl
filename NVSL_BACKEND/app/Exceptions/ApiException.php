<?php

namespace App\Exceptions;

use RuntimeException;

class ApiException extends RuntimeException
{
    public function __construct(
        string $message,
        private readonly int $status = 400,
        private readonly string $error = 'bad_request'
    ) {
        parent::__construct($message);
    }

    public function status(): int
    {
        return $this->status;
    }

    public function error(): string
    {
        return $this->error;
    }

    public static function unauthenticated(string $message = 'Não autenticado.'): self
    {
        return new self($message, 401, 'unauthenticated');
    }

    public static function forbidden(string $message = 'Acesso não permitido.'): self
    {
        return new self($message, 403, 'forbidden');
    }

    public static function notFound(string $message = 'Recurso não encontrado.'): self
    {
        return new self($message, 404, 'not_found');
    }

    public static function unprocessable(string $message = 'Dados inválidos.'): self
    {
        return new self($message, 422, 'unprocessable_entity');
    }

    public static function conflict(string $message = 'Conflito de dados.'): self
    {
        return new self($message, 409, 'conflict');
    }
}

