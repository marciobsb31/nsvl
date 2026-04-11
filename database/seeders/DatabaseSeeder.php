<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public const PERFIL_BOOTSTRAP = 'bootstrap';

    public const PERFIL_DEMO = 'demo';

    public const PERFIL_TEST = 'test';

    /**
     * Perfis de seed:
     * - bootstrap: somente dados controlados mínimos do ambiente
     * - demo: dados controlados de demonstração
     * - test: dados mínimos para execução automatizada
     */
    public function run(): void
    {
        $profile = self::resolverPerfilEfetivo(
            (string) config('app.env', 'production'),
            env('DB_SEED_PROFILE', self::PERFIL_BOOTSTRAP),
        );

        match ($profile) {
            self::PERFIL_BOOTSTRAP => $this->call(BootstrapSeeder::class),
            self::PERFIL_DEMO      => $this->call(DemoSeeder::class),
            self::PERFIL_TEST      => $this->call(TestSeeder::class),
            default                => $this->call(BootstrapSeeder::class),
        };
    }

    public static function resolverPerfilEfetivo(string $appEnv, mixed $seedProfile): string
    {
        $perfilSolicitado = is_string($seedProfile) && $seedProfile !== ''
            ? $seedProfile
            : self::PERFIL_BOOTSTRAP;

        if (! in_array($appEnv, ['local', 'hlog'], true)) {
            return self::PERFIL_BOOTSTRAP;
        }

        return match ($perfilSolicitado) {
            self::PERFIL_BOOTSTRAP,
            self::PERFIL_DEMO,
            self::PERFIL_TEST => $perfilSolicitado,
            default           => self::PERFIL_BOOTSTRAP,
        };
    }
}
