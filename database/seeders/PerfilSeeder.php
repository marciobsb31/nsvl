<?php

namespace Database\Seeders;

use App\Models\Perfil;
use Illuminate\Database\Seeder;

/**
 * Catálogo oficial de perfis ({@see \App\Models\Perfil::CATALOGO_OFICIAL}),
 * alinhado a GET /api/perfis e GET /api/gerenciar-perfis.
 */
class PerfilSeeder extends Seeder
{
    public function run(): void
    {
        $perfis = [
            ['nome' => 'Gestor Nacional', 'descricao' => 'Gestão operacional no âmbito federal', 'ativo' => true],
            ['nome' => 'Gestor Estadual', 'descricao' => 'Gestão operacional no âmbito estadual', 'ativo' => true],
            ['nome' => 'Gestor Municipal', 'descricao' => 'Gestão operacional no âmbito municipal', 'ativo' => true],
            ['nome' => 'Administrador Nacional', 'descricao' => 'Administração e configuração no âmbito federal', 'ativo' => true],
            ['nome' => 'Administrador Estadual', 'descricao' => 'Administração e configuração no âmbito estadual', 'ativo' => true],
            ['nome' => 'Administrador Municipal', 'descricao' => 'Administração e configuração no âmbito municipal', 'ativo' => true],
        ];

        foreach ($perfis as $p) {
            Perfil::updateOrCreate(
                ['nome' => $p['nome']],
                ['descricao' => $p['descricao'], 'ativo' => $p['ativo']]
            );
        }
    }
}
