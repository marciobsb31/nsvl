<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PerfilSeeder extends Seeder
{
    public function run(): void
    {
        $esferaFederal = DB::table('esferas')->where('codigo', 'federal')->first();
        $esferaEstadual = DB::table('esferas')->where('codigo', 'estadual')->first();

        if (! $esferaFederal || ! $esferaEstadual) {
            $this->command->error('Esferas não encontradas! Rode o EsferaSeeder primeiro.');

            return;
        }

        $now = Carbon::now();

        $perfis = [

            [
                'esfera_id' => $esferaFederal->id,
                'codigo'    => 'admin_federal',
                'nome'      => 'Administrador Federal',
                'descricao' => 'Gestão total do sistema em nível nacional.',
                'ativo'     => true,
            ],
            [
                'esfera_id' => $esferaFederal->id,
                'codigo'    => 'gestor_federal',
                'nome'      => 'Gestor Federal',
                'descricao' => 'Acompanhamento e gestão de dados nacionais.',
                'ativo'     => true,
            ],
            [
                'esfera_id' => $esferaFederal->id,
                'codigo'    => 'visualizador_federal',
                'nome'      => 'Consultor Federal',
                'descricao' => 'Acesso apenas para leitura de relatórios nacionais.',
                'ativo'     => true,
            ],

            [
                'esfera_id' => $esferaEstadual->id,
                'codigo'    => 'admin_estadual',
                'nome'      => 'Administrador Estadual',
                'descricao' => 'Gestão total dentro da sua unidade federativa.',
                'ativo'     => true,
            ],
            [
                'esfera_id' => $esferaEstadual->id,
                'codigo'    => 'gestor_estadual',
                'nome'      => 'Gestor Estadual',
                'descricao' => 'Gestão operacional de solicitações estaduais.',
                'ativo'     => true,
            ],
            [
                'esfera_id' => $esferaEstadual->id,
                'codigo'    => 'tecnico_estadual',
                'nome'      => 'Técnico Estadual',
                'descricao' => 'Análise técnica de processos locais.',
                'ativo'     => true,
            ],
            [
                'esfera_id' => $esferaEstadual->id,
                'codigo'    => 'operador_estadual',
                'nome'      => 'Operador Estadual',
                'descricao' => 'Entrada de dados e registros regionais.',
                'ativo'     => true,
            ],
            [
                'esfera_id' => $esferaEstadual->id,
                'codigo'    => 'visitante_estadual',
                'nome'      => 'Visitante Estadual',
                'descricao' => 'Acesso restrito para visualização regional.',
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
    }
}
