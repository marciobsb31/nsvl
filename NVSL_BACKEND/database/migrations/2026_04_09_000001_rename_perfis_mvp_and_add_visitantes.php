<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * MVP: renomeia perfis legados e adiciona os 3 perfis Visitante.
 *
 * Antes: Gestor Nacional, Gestor Estadual, Gestor Municipal, Administrador Nacional,
 *        Administrador Estadual, Administrador Municipal (6 perfis)
 *
 * Depois: Gestor Federal, Gestor Estadual, Gestor Municipal,
 *         Administrador Estadual, Administrador Municipal,
 *         Visitante Federal, Visitante Estadual, Visitante Municipal (8 perfis)
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Renomear "Gestor Nacional" → "Gestor Federal"
        DB::table('perfis')
            ->where('nome', 'Gestor Nacional')
            ->update([
                'nome'      => 'Gestor Federal',
                'descricao' => 'Administrador geral do sistema — acesso integral no âmbito federal',
            ]);

        // 2. Renomear "Administrador Nacional" → "Visitante Federal"
        //    (não existe "Administrador Federal" no MVP)
        DB::table('perfis')
            ->where('nome', 'Administrador Nacional')
            ->update([
                'nome'      => 'Visitante Federal',
                'descricao' => 'Acesso somente leitura no âmbito federal (nacional)',
            ]);

        // 3. Adicionar perfis Visitante Estadual e Municipal (se não existirem)
        $now = now();

        if (!DB::table('perfis')->where('nome', 'Visitante Estadual')->exists()) {
            DB::table('perfis')->insert([
                'nome'       => 'Visitante Estadual',
                'descricao'  => 'Acesso somente leitura no âmbito estadual',
                'ativo'      => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        if (!DB::table('perfis')->where('nome', 'Visitante Municipal')->exists()) {
            DB::table('perfis')->insert([
                'nome'       => 'Visitante Municipal',
                'descricao'  => 'Acesso somente leitura no âmbito municipal',
                'ativo'      => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // 4. Atualizar descrições dos perfis existentes que se mantêm
        DB::table('perfis')->where('nome', 'Gestor Estadual')
            ->update(['descricao' => 'Gestão operacional no âmbito estadual']);
        DB::table('perfis')->where('nome', 'Gestor Municipal')
            ->update(['descricao' => 'Gestão operacional no âmbito municipal']);
        DB::table('perfis')->where('nome', 'Administrador Estadual')
            ->update(['descricao' => 'Equipe multidisciplinar do plano de ação — âmbito estadual']);
        DB::table('perfis')->where('nome', 'Administrador Municipal')
            ->update(['descricao' => 'Equipe multidisciplinar do plano de ação — âmbito municipal']);
    }

    public function down(): void
    {
        // Reverter nomes
        DB::table('perfis')->where('nome', 'Gestor Federal')
            ->update(['nome' => 'Gestor Nacional', 'descricao' => 'Gestão operacional no âmbito federal']);

        DB::table('perfis')->where('nome', 'Visitante Federal')
            ->update(['nome' => 'Administrador Nacional', 'descricao' => 'Administração e configuração no âmbito federal']);

        // Remover perfis adicionados
        DB::table('perfis')->where('nome', 'Visitante Estadual')->delete();
        DB::table('perfis')->where('nome', 'Visitante Municipal')->delete();
    }
};
