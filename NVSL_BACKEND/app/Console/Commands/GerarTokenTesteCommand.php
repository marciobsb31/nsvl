<?php

namespace App\Console\Commands;

use App\Models\Usuario;
use Illuminate\Console\Command;

class GerarTokenTesteCommand extends Command
{
    protected $signature = 'token:teste {perfil=federal : federal|estadual|municipal}';

    protected $description = 'Gera token Sanctum para usuário de teste (Federal, Estadual ou Municipal)';

    public function handle(): int
    {
        $perfil = $this->argument('perfil');

        $user = match ($perfil) {
            'federal'  => Usuario::where('govbr_sub', '11144477735')->first(),
            'estadual' => Usuario::where('govbr_sub', '52998224725')->first(),
            'municipal'=> Usuario::where('govbr_sub', '98765432100')->first(),
            default    => null,
        };

        if (!$user) {
            $this->error("Usuário de teste '$perfil' não encontrado. Execute: php artisan db:seed --class=UsuarioExemploSeeder --force");
            return 1;
        }

        $token = $user->createToken('teste-' . $perfil)->plainTextToken;

        $this->info("Token para {$user->nome} ({$perfil}):");
        $this->line($token);
        $this->newLine();
        $this->comment('Use no header: Authorization: Bearer ' . substr($token, 0, 20) . '...');

        return 0;
    }
}
