<?php

namespace Database\Seeders;

use App\Models\Usuario;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UsuarioEstadualMunicialSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $usuario = Usuario::firstOrCreate(
            ['email' => 'gestor.goias@nvsl.gov.br'],
            [
                'nome'       => 'Gestor Multiesferas GO',
                'cpf'        => '89444432033',
                'ativo'      => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        $esferaEstadual = DB::table('esferas')->where('codigo', 'estadual')->first();
        $esferaMunicipal = DB::table('esferas')->where('codigo', 'municipal')->first();
        $ufGO = DB::table('ufs')->where('sigla', 'GO')->first();

        $perfilGestorEstadual = DB::table('perfis')->where('codigo', 'gestor_estadual')->first();
        $perfilGestorMunicipal = DB::table('perfis')->where('codigo', 'gestor_municipal')->first();

        DB::table('usuario_abrangencia')->updateOrInsert(
            [
                'usuario_id'   => $usuario->id,
                'esfera_id'    => $esferaEstadual->id,
                'uf_id'        => $ufGO->id,
                'municipio_id' => null,
            ],
            [
                'nome'        => 'Estado de '.$ufGO->nome,
                'origem_tipo' => 'seeder',
                'ativo'       => true,
                'created_at'  => $now,
                'updated_at'  => $now,
            ]
        );

        $abrangenciaEstadualId = DB::table('usuario_abrangencia')
            ->where('usuario_id', $usuario->id)
            ->where('esfera_id', $esferaEstadual->id)
            ->whereNull('municipio_id')
            ->value('id');

        DB::table('perfil_usuario')->updateOrInsert(
            [
                'usuario_id' => $usuario->id,
                'perfil_id'  => $perfilGestorEstadual->id,
            ],
            [
                'usuario_abrangencia_id' => $abrangenciaEstadualId,
                'ativo'                  => true,
                'origem_tipo'            => 'seeder',
                'data_inicio_vigencia'   => $now,
                'created_at'             => $now,
                'updated_at'             => $now,
            ]
        );

        $perfilUsuarioEstadualId = DB::table('perfil_usuario')
            ->where('usuario_id', $usuario->id)
            ->where('perfil_id', $perfilGestorEstadual->id)
            ->value('id');

        $municipio = DB::table('municipios')
            ->where('nome', 'ILIKE', 'Goiânia')
            ->where('uf_id', $ufGO->id)
            ->first();

        DB::table('usuario_abrangencia')->updateOrInsert(
            [
                'usuario_id'   => $usuario->id,
                'esfera_id'    => $esferaMunicipal->id,
                'uf_id'        => $ufGO->id,
                'municipio_id' => $municipio->id,
            ],
            [
                'nome'        => 'Município de '.$municipio->nome,
                'origem_tipo' => 'seeder',
                'ativo'       => true,
                'created_at'  => $now,
                'updated_at'  => $now,
            ]
        );

        $abrangenciaMunicipalId = DB::table('usuario_abrangencia')
            ->where('usuario_id', $usuario->id)
            ->where('esfera_id', $esferaMunicipal->id)
            ->where('municipio_id', $municipio->id)
            ->value('id');

        DB::table('perfil_usuario')->updateOrInsert(
            [
                'usuario_id' => $usuario->id,
                'perfil_id'  => $perfilGestorMunicipal->id,
            ],
            [
                'usuario_abrangencia_id' => $abrangenciaMunicipalId,
                'ativo'                  => true,
                'origem_tipo'            => 'seeder',
                'data_inicio_vigencia'   => $now,
                'created_at'             => $now,
                'updated_at'             => $now,
            ]
        );

        DB::table('usuario_contexto')->updateOrInsert(
            ['usuario_id' => $usuario->id],
            [
                'perfil_usuario_id'      => $perfilUsuarioEstadualId,
                'usuario_abrangencia_id' => $abrangenciaEstadualId,
                'created_at'             => $now,
                'updated_at'             => $now,
            ]
        );

        $this->command->info('Usuário multicontexto atualizado: Perfis agora possuem abrangência vinculada.');
    }
}
