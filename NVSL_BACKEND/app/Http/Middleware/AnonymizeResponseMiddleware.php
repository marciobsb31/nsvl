<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * AnonymizeResponseMiddleware — Remove campos sensíveis das respostas JSON
 *
 * Garante que dados internos (cpf_hash, tokens, etc.) nunca sejam
 * acidentalmente expostos em respostas da API, mesmo que um Model
 * seja retornado diretamente.
 */
class AnonymizeResponseMiddleware
{
    /**
     * Campos que NUNCA podem aparecer em respostas JSON da API.
     */
    private const SENSITIVE_FIELDS = [
        'cpf_hash',
        'cpf',
        'password',
        'remember_token',
        'govbr_access_token',
        'govbr_refresh_token',
        'id_token',
        'client_secret',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Aplica apenas em respostas JSON
        if (!$response->headers->contains('Content-Type', 'application/json')) {
            return $response;
        }

        $content = json_decode($response->getContent(), true);

        if (is_array($content)) {
            $content = $this->removeSensitiveFields($content);
            $response->setContent(json_encode($content));
        }

        return $response;
    }

    private function removeSensitiveFields(array $data): array
    {
        foreach ($data as $key => &$value) {
            if (in_array($key, self::SENSITIVE_FIELDS, true)) {
                unset($data[$key]);
                continue;
            }

            if (is_array($value)) {
                $value = $this->removeSensitiveFields($value);
            }
        }

        return $data;
    }
}
