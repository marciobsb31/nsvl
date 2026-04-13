<?php

namespace Database\Seeders;

use App\Models\Usuario;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UsuarioEstadualSeeder extends Seeder
{
    public function run(): void
    {
        $usuario = Usuario::firstOrCreate(
            ['email' => 'gestor.goias@nvsl.gov.br'],
            [
                'nome'  => 'Gestor Estadual GO',
                'cpf'   => '89444432033',
                'ativo' => true,
            ]
        );

        $esfera = DB::table('esferas')->where('codigo', 'estadual')->first();
        $uf = DB::table('ufs')->where('sigla', 'GO')->first();

        if (!$esfera || !$uf) {
            $this->command->error("Esfera estadual ou UF GO não encontrada. Verifique seus seeders base.");
            return;
        }

        $abrangenciaId = DB::table('usuario_abrangencia')->insertGetId([
            'usuario_id'  => $usuario->id,
            'esfera_id'   => $esfera->id,
            'uf_id'       => $uf->id,
            'nome'        => "Estado de " . $uf->nome,
            'origem_tipo' => 'seeder',
            'ativo'       => true,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        $perfil = DB::table('perfis')->where('codigo', 'gestor_estadual')->first();

        $perfilUsuarioId = DB::table('perfil_usuario')->insertGetId([
            'usuario_id'           => $usuario->id,
            'perfil_id'            => $perfil->id,
            'ativo'                => true,
            'origem_tipo'          => 'seeder',
            'data_inicio_vigencia' => now(),
            'created_at'           => now(),
            'updated_at'           => now(),
        ]);

        DB::table('usuario_contexto')->insert([
            'usuario_id'             => $usuario->id,
            'perfil_usuario_id'      => $perfilUsuarioId,
            'usuario_abrangencia_id' => $abrangenciaId,
            'created_at'             => now(),
            'updated_at'             => now(),
        ]);

        $this->command->info("Usuário Gestor Estadual (GO) criado com sucesso!");
    }
}
