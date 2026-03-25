<?php

namespace Tests\Feature\Auth;

use Illuminate\Support\Facades\Cache;
use Tests\TestCase;
use Tests\Traits\ActingAsUserTrait;
use PHPUnit\Framework\Attributes\Test;

class ExchangeTest extends TestCase
{
    use ActingAsUserTrait;

    #[Test]
    public function code_obrigatorio(): void
    {
        $this->postJson('/api/auth/exchange', [])
            ->assertStatus(422);
    }

    #[Test]
    public function code_invalido_retorna_422(): void
    {
        $this->postJson('/api/auth/exchange', ['code' => 'codigo-inexistente'])
            ->assertStatus(422);
    }

    #[Test]
    public function code_valido_retorna_token_e_user(): void
    {
        $user = $this->criarUsuarioFederal();
        $code = 'test-login-code-' . uniqid();

        Cache::put("govbr:login-code:{$code}", [
            'token' => 'test-token-123',
            'user'  => $user->toSafeArray(),
        ], 120);

        $this->postJson('/api/auth/exchange', ['code' => $code])
            ->assertOk()
            ->assertJsonStructure(['token', 'user']);
    }

    #[Test]
    public function code_consumido_nao_pode_ser_reutilizado(): void
    {
        $user = $this->criarUsuarioFederal();
        $code = 'test-code-' . uniqid();

        Cache::put("govbr:login-code:{$code}", [
            'token' => 'token', 'user' => $user->toSafeArray(),
        ], 120);

        $this->postJson('/api/auth/exchange', ['code' => $code])->assertOk();
        $this->postJson('/api/auth/exchange', ['code' => $code])->assertStatus(422);
    }
}
