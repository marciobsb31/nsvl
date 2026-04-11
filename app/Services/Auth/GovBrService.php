<?php

namespace App\Services\Auth;

use App\DTOs\Auth\GovBrUserDTO;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class GovBrService
{
    public function gerarState(): string
    {
        return Str::random(64);
    }

    public function gerarNonce(): string
    {
        return Str::random(64);
    }

    public function gerarCodeVerifier(): string
    {
        return Str::random(96);
    }

    public function gerarCodeChallenge(string $codeVerifier): string
    {
        $hash = hash('sha256', $codeVerifier, true);

        return $this->base64UrlEncode($hash);
    }

    public function montarUrlAutorizacao(string $state, string $nonce, string $codeChallenge): string
    {
        $query = http_build_query([
            'response_type'         => 'code',
            'client_id'             => config('govbr.client_id'),
            'scope'                 => implode(' ', config('govbr.scopes', [])),
            'redirect_uri'          => config('govbr.redirect_uri'),
            'nonce'                 => $nonce,
            'state'                 => $state,
            'code_challenge'        => $codeChallenge,
            'code_challenge_method' => 'S256',
        ]);

        return rtrim((string) config('govbr.authorize_url'), '?').'?'.$query;
    }

    /**
     * @return array{access_token:string,id_token:?string,token_type:?string,expires_in:int|null}
     */
    public function trocarCodePorToken(string $code, string $codeVerifier): array
    {
        $response = Http::asForm()
            ->acceptJson()
            ->withBasicAuth((string) config('govbr.client_id'), (string) config('govbr.client_secret'))
            ->post((string) config('govbr.token_url'), [
                'grant_type'    => 'authorization_code',
                'code'          => $code,
                'redirect_uri'  => config('govbr.redirect_uri'),
                'code_verifier' => $codeVerifier,
            ]);

        if ($response->failed()) {
            throw new RequestException($response);
        }

        $data = $response->json();
        if (! is_array($data) || empty($data['access_token'])) {
            throw new RuntimeException('Resposta inválida ao obter token do GOV.BR.');
        }

        return [
            'access_token' => (string) $data['access_token'],
            'id_token'     => isset($data['id_token']) ? (string) $data['id_token'] : null,
            'token_type'   => isset($data['token_type']) ? (string) $data['token_type'] : null,
            'expires_in'   => isset($data['expires_in']) ? (int) $data['expires_in'] : null,
        ];
    }

    public function obterUsuario(string $accessToken): GovBrUserDTO
    {
        $response = Http::acceptJson()
            ->withToken($accessToken)
            ->get((string) config('govbr.userinfo_url'));

        if ($response->failed()) {
            throw new RequestException($response);
        }

        $data = $response->json();
        if (! is_array($data) || empty($data['sub']) || empty($data['name'])) {
            throw new RuntimeException('Resposta inválida do endpoint userinfo do GOV.BR.');
        }

        return GovBrUserDTO::fromArray($data);
    }

    public function validarNonce(?string $idToken, string $nonceEsperado): bool
    {
        if (! $idToken) {
            return true;
        }

        $payload = $this->decodificarJwtPayload($idToken);
        if (! is_array($payload)) {
            return false;
        }

        return ($payload['nonce'] ?? null) === $nonceEsperado;
    }

    private function decodificarJwtPayload(string $jwt): ?array
    {
        $partes = explode('.', $jwt);
        if (count($partes) < 2) {
            return null;
        }

        $payload = $partes[1];
        $padding = strlen($payload) % 4;
        if ($padding > 0) {
            $payload .= str_repeat('=', 4 - $padding);
        }

        $json = base64_decode(strtr($payload, '-_', '+/'), true);
        if ($json === false) {
            return null;
        }

        $data = json_decode($json, true);

        return is_array($data) ? $data : null;
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
