<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class LocalidadesSeeder extends Seeder
{
    private const API_IBGE = 'https://servicodados.ibge.gov.br/api/v1/localidades';

    public function run(): void
    {
        $this->command->info('Buscando estados na API do IBGE...');
        $estados = $this->buscarEstados();

        $this->command->info('Inserindo '.count($estados).' estados...');
        $this->inserirEstados($estados);

        $this->command->info('Buscando e inserindo municípios...');
        $this->inserirMunicipios($estados);

        $this->command->info('✅ Localidades importadas com sucesso!');
    }

    private function buscarEstados(): array
    {
        $response = Http::get(self::API_IBGE.'/estados', [
            'orderBy' => 'nome',
        ]);

        if ($response->failed()) {
            $this->command->error('Falha ao buscar estados do IBGE.');

            return [];
        }

        return $response->json();
    }

    private function inserirEstados(array $estados): void
    {
        $agora = now();

        $dados = collect($estados)->map(fn ($estado) => [
            'id'          => $estado['id'],
            'sigla'       => $estado['sigla'],
            'nome'        => $estado['nome'],
            'codigo_ibge' => $estado['id'],
            'created_at'  => $agora,
            'updated_at'  => $agora,
        ])->toArray();

        DB::table('ufs')->upsert(
            $dados,
            ['id'],
            ['sigla', 'nome', 'updated_at']
        );
    }

    private function inserirMunicipios(array $estados): void
    {
        $agora = now();
        $bar = $this->command->getOutput()->createProgressBar(count($estados));
        $bar->start();

        foreach ($estados as $estado) {
            $response = Http::get(self::API_IBGE."/estados/{$estado['id']}/municipios");

            if ($response->failed()) {
                $this->command->warn("Falha ao buscar municípios de {$estado['sigla']}");
                $bar->advance();

                continue;
            }

            $municipios = collect($response->json())->map(fn ($municipio) => [
                'id'          => $municipio['id'],
                'nome'        => $municipio['nome'],
                'uf_id'       => $estado['id'],
                'codigo_ibge' => $municipio['id'],
                'created_at'  => $agora,
                'updated_at'  => $agora,
            ])->toArray();

            DB::table('municipios')->upsert(
                $municipios,
                ['id'],
                ['nome', 'uf_id', 'updated_at']
            );

            $bar->advance();
        }

        $bar->finish();
        $this->command->newLine();
    }
}
