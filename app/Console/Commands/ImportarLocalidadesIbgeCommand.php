<?php

namespace App\Console\Commands;

use App\Models\Municipio;
use App\Models\Uf;
use Illuminate\Console\Command;
use Illuminate\Http\Client\Pool;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/**
 * Importa todas as UFs e municípios brasileiros a partir das APIs públicas do IBGE.
 *
 * @see https://servicodados.ibge.gov.br/api/v1/localidades/estados
 * @see https://servicodados.ibge.gov.br/api/v1/localidades/estados/{id}/municipios
 */
class ImportarLocalidadesIbgeCommand extends Command
{
    private const IBGE_ESTADOS_URL = 'https://servicodados.ibge.gov.br/api/v1/localidades/estados';

    private const CHUNK_MUNICIPIOS = 350;

    protected $signature = 'localidades:importar-ibge';

    protected $description = 'Importa todas as UFs e municípios do IBGE (upsert nas tabelas ufs e municipios)';

    public function handle(): int
    {
        $this->info('Consultando IBGE (estados e municípios)...');

        $estados = $this->buscarEstados();
        if ($estados === []) {
            $this->error('Não foi possível obter os estados do IBGE. Verifique a conexão com a internet.');

            return self::FAILURE;
        }

        DB::transaction(function () use ($estados): void {
            $this->inserirUfs($estados);
            $this->info('UFs sincronizadas: '.count($estados));

            $municipiosPorUf = $this->buscarMunicipios($estados);
            $ufIdsPorSigla = Uf::query()->pluck('id', 'sigla');

            $mostrarBarra = $this->input->isInteractive();
            $bar = $mostrarBarra ? $this->output->createProgressBar(count($municipiosPorUf)) : null;
            $bar?->start();

            $totalMun = 0;
            foreach ($municipiosPorUf as $sigla => $municipios) {
                $ufId = $ufIdsPorSigla[$sigla] ?? null;
                if ($ufId === null || ! is_array($municipios)) {
                    $bar?->advance();

                    continue;
                }

                $rows = [];
                foreach ($municipios as $m) {
                    if (empty($m['nome'])) {
                        continue;
                    }
                    $rows[] = [
                        'uf_id' => $ufId,
                        'nome'  => $m['nome'],
                    ];
                }

                foreach (array_chunk($rows, self::CHUNK_MUNICIPIOS) as $chunk) {
                    Municipio::upsert($chunk, ['uf_id', 'nome'], ['nome']);
                }

                $totalMun += count($rows);
                $bar?->advance();
            }

            $bar?->finish();
            if ($bar !== null) {
                $this->newLine(2);
            }
            $this->info("Municípios sincronizados (linhas processadas): {$totalMun}");
        });

        $this->info('Importação IBGE concluída.');

        return self::SUCCESS;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function buscarEstados(): array
    {
        $response = Http::timeout(60)->get(self::IBGE_ESTADOS_URL);
        if (! $response->successful()) {
            return [];
        }

        $data = $response->json();

        return is_array($data) ? $data : [];
    }

    /**
     * @param  list<array<string, mixed>>  $estados
     */
    private function inserirUfs(array $estados): void
    {
        $rows = [];
        foreach ($estados as $e) {
            if (empty($e['sigla']) || empty($e['nome'])) {
                continue;
            }
            $rows[] = [
                'sigla' => strtoupper((string) $e['sigla']),
                'nome'  => (string) $e['nome'],
            ];
        }

        if ($rows === []) {
            return;
        }

        Uf::upsert($rows, ['sigla'], ['nome']);
    }

    /**
     * @param  list<array<string, mixed>>  $estados
     * @return array<string, list<array<string, mixed>>>
     */
    private function buscarMunicipios(array $estados): array
    {
        $estadosComId = array_values(array_filter(
            $estados,
            static fn ($e) => ! empty($e['id']) && ! empty($e['sigla'])
        ));

        if ($estadosComId === []) {
            return [];
        }

        $responses = Http::pool(fn (Pool $pool) => collect($estadosComId)->map(
            fn ($estado) => $pool->as(strtoupper((string) $estado['sigla']))
                ->timeout(90)
                ->get(self::IBGE_ESTADOS_URL.'/'.$estado['id'].'/municipios')
        )->all());

        $resultado = [];
        foreach ($estadosComId as $estado) {
            $sigla = strtoupper((string) $estado['sigla']);
            $response = $responses[$sigla] ?? null;
            if ($response === null || ! $response->successful()) {
                $this->newLine();
                $this->warn("Falha ao obter municípios da UF {$sigla} no IBGE.");
                $resultado[$sigla] = [];

                continue;
            }

            $municipios = $response->json();
            $resultado[$sigla] = is_array($municipios) ? $municipios : [];
        }

        return $resultado;
    }
}
