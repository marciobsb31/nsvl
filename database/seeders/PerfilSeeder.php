<?php

namespace Database\Seeders;

use App\Support\MvpPerfilRules;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PerfilSeeder extends Seeder
{
    public function run(): void
    {
        $esferaFederal = DB::table('esferas')->where('codigo', 'federal')->first();
        $esferaEstadual = DB::table('esferas')->where('codigo', 'estadual')->first();
        $esferaMunicipal = DB::table('esferas')->where('codigo', 'municipal')->first();

        if (! $esferaFederal || ! $esferaEstadual) {
            $this->command->error('Esferas não encontradas! Rode o EsferaSeeder primeiro.');

            return;
        }

        $now = Carbon::now();

        $perfis = [

            [
                'esfera_id' => $esferaFederal->id,
                'codigo'    => 'gestor_federal',
                'nome'      => 'Gestor Federal',
                'descricao' => 'Gestão total do sistema em nível nacional.',
                'ativo'     => true,
            ],
            [
                'esfera_id' => $esferaEstadual->id,
                'codigo'    => 'gestor_estadual',
                'nome'      => 'Gestor Estadual',
                'descricao' => 'Acompanhamento e gestão de dados do estado de vínculo.',
                'ativo'     => true,
            ],
            [
                'esfera_id' => $esferaMunicipal->id,
                'codigo'    => 'gestor_municipal',
                'nome'      => 'Gestor Municipal',
                'descricao' => 'Acompanhamento e gestão de dados do município de vínculo.',
                'ativo'     => true,
            ],

            [
                'esfera_id' => $esferaEstadual->id,
                'codigo'    => 'admin_estadual',
                'nome'      => 'Administrador Estadual',
                'descricao' => 'Acompanhamento e gestão de dados do estado de vínculo',
                'ativo'     => true,
            ],
            [
                'esfera_id' => $esferaMunicipal->id,
                'codigo'    => 'admin_municipal',
                'nome'      => 'Administrador Municipal',
                'descricao' => 'Acompanhamento e gestão de dados do município de vínculo.',
                'ativo'     => true,
            ],
            [
                'esfera_id' => $esferaFederal->id,
                'codigo'    => 'visitante_federal',
                'nome'      => 'Visitante Federal',
                'descricao' => 'Acesso restrito para visualização federais.',
                'ativo'     => true,
            ],
            [
                'esfera_id' => $esferaEstadual->id,
                'codigo'    => 'visitante_estadual',
                'nome'      => 'Visitante Estadual',
                'descricao' => 'Acesso restrito para visualização regional.',
                'ativo'     => true,
            ],
            [
                'esfera_id' => $esferaMunicipal->id,
                'codigo'    => 'visitante_municipal',
                'nome'      => 'Visitante Municipal',
                'descricao' => 'Acesso restrito para visualização municipal.',
                'ativo'     => true,
            ],
        ];

        foreach ($perfis as $perfil) {
            DB::table('perfis')->updateOrInsert(
                ['codigo' => $perfil['codigo'], 'esfera_id' => $perfil['esfera_id']],
                array_merge($perfil, [
                    'created_at' => $now,
                    'updated_at' => $now,
                ])
            );
        }

        DB::table('perfis')
            ->whereNotIn('codigo', MvpPerfilRules::mvpProfileCodes())
            ->update([
                'ativo'      => false,
                'updated_at' => $now,
            ]);
    }
}
