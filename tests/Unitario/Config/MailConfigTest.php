<?php

namespace Tests\Unitario\Config;

use PHPUnit\Framework\Attributes\Test;
use Tests\Unitario\TestCase;

class MailConfigTest extends TestCase
{
    private array $originalEnv = [];

    protected function tearDown(): void
    {
        foreach ($this->originalEnv as $name => $value) {
            $this->restoreEnvValue($name, $value);
        }

        $this->originalEnv = [];

        parent::tearDown();
    }

    #[Test]
    public function usa_mail_driver_como_mailer_padrao(): void
    {
        $this->setEnvValue('MAIL_DRIVER', 'smtp');

        $config = require base_path('config/mail.php');

        $this->assertSame('smtp', $config['default']);
    }

    #[Test]
    public function normaliza_credenciais_e_opcoes_vazias_para_nulo(): void
    {
        $this->setEnvValue('APP_URL', 'https://api.nvsl.gov.br');
        $this->setEnvValue('MAIL_ENCRYPTION', 'null');
        $this->setEnvValue('MAIL_USERNAME', '');
        $this->setEnvValue('MAIL_PASSWORD', '');

        $config = require base_path('config/mail.php');

        $this->assertNull($config['mailers']['smtp']['scheme']);
        $this->assertNull($config['mailers']['smtp']['username']);
        $this->assertNull($config['mailers']['smtp']['password']);
        $this->assertSame('api.nvsl.gov.br', $config['mailers']['smtp']['local_domain']);
        $this->assertSame(30, $config['mailers']['smtp']['timeout']);
    }

    private function setEnvValue(string $name, ?string $value): void
    {
        if (!array_key_exists($name, $this->originalEnv)) {
            $current = getenv($name);
            $this->originalEnv[$name] = $current === false ? null : $current;
        }

        $this->restoreEnvValue($name, $value);
    }

    private function restoreEnvValue(string $name, ?string $value): void
    {
        if ($value === null) {
            putenv($name);
            unset($_ENV[$name], $_SERVER[$name]);

            return;
        }

        putenv($name . '=' . $value);
        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;
    }
}
