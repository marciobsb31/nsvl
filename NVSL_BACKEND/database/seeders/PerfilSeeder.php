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
            ['nome' => 'Gestor Federal', 'descricao' => 'Administrador geral do sistema — acesso integral no âmbito federal', 'ativo' => true],
            ['nome' => 'Gestor Estadual', 'descricao' => 'Gestão operacional no âmbito estadual', 'ativo' => true],
            ['nome' => 'Gestor Municipal', 'descricao' => 'Gestão operacional no âmbito municipal', 'ativo' => true],
            ['nome' => 'Administrador Estadual', 'descricao' => 'Equipe multidisciplinar do plano de ação — âmbito estadual', 'ativo' => true],
            ['nome' => 'Administrador Municipal', 'descricao' => 'Equipe multidisciplinar do plano de ação — âmbito municipal', 'ativo' => true],
            ['nome' => 'Visitante Federal', 'descricao' => 'Acesso somente leitura no âmbito federal (nacional)', 'ativo' => true],
            ['nome' => 'Visitante Estadual', 'descricao' => 'Acesso somente leitura no âmbito estadual', 'ativo' => true],
            ['nome' => 'Visitante Municipal', 'descricao' => 'Acesso somente leitura no âmbito municipal', 'ativo' => true],
        ];

        // Renomear perfis legados que mudaram de nome
        Perfil::where('nome', 'Gestor Nacional')->update(['nome' => 'Gestor Federal']);
        Perfil::where('nome', 'Administrador Nacional')->update([
            'nome'      => 'Visitante Federal',
            'descricao' => 'Acesso somente leitura no âmbito federal (nacional)',
        ]);

        foreach ($perfis as $p) {
            Perfil::updateOrCreate(
                ['nome' => $p['nome']],
                ['descricao' => $p['descricao'], 'ativo' => $p['ativo']]
            );
        }
    }
}
