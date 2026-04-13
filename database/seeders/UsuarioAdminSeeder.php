<?php

namespace Database\Seeders;

use App\Models\Usuario;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UsuarioAdminSeeder extends Seeder
{
    public function run(): void
    {
        $usuario = Usuario::firstOrCreate(
            ['email' => 'admin@nvsl.gov.br'],
            [
                'nome'  => 'Administrador Sistema',
                'cpf'   => '71217485090',
                'ativo' => true,
            ]
        );

        $esfera = DB::table('esferas')->where('codigo', 'federal')->first();

        $abrangenciaId = DB::table('usuario_abrangencia')->insertGetId([
            'usuario_id'  => $usuario->id,
            'esfera_id'   => $esfera->id,
            'nome'        => $esfera->nome,
            'origem_tipo' => 'seeder',
            'ativo'       => true,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        $perfil = DB::table('perfis')->where('codigo', 'gestor_federal')->first();

        $perfilUsuarioId = DB::table('perfil_usuario')->insert([
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
    }
}
