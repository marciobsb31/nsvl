<?php

namespace App\Console\Commands;

use App\Models\Municipio;
use App\Models\Uf;
use Illuminate\Console\Command;
use Illuminate\Http\Client\Pool;
use Illuminate\Support\Facades\Http;

/**
 * Importa UFs e municípios do IBGE para o banco de dados.
 * Executar UMA VEZ para popular as tabelas. Volatilidade dos dados é baixa.
 *
 * URLs de referência (estrutura IBGE):
 * - Estados: https://servicodados.ibge.gov.br/api/v1/localidades/estados
 * - Municípios: https://servicodados.ibge.gov.br/api/v1/localidades/estados/{id}/municipios
 */
class ImportarLocalidadesIbgeCommand extends Command
{
    private const IBGE_ESTADOS_URL = 'https://servicodados.ibge.gov.br/api/v1/localidades/estados';

    protected $signature = 'localidades:importar-ibge
                            {--force : Substituir dados existentes}';

    protected $description = 'Importa UFs e municípios do IBGE para o banco (executar uma vez)';

    public function handle(): int
    {
        $this->info('Importando UFs e municípios do IBGE...');

        $estados = $this->buscarEstados();
        if (empty($estados)) {
            $this->error('Não foi possível obter os estados do IBGE.');
            return 1;
        }

        $this->info('Inserindo ' . count($estados) . ' UFs...');
        $ufsInseridas = $this->inserirUfs($estados);
        $this->info("UFs processadas: {$ufsInseridas}");

        $this->info('Buscando municípios (requisições em paralelo)...');
        $municipiosPorUf = $this->buscarMunicipios($estados);

        $total = 0;
        foreach ($municipiosPorUf as $sigla => $municipios) {
            $count = $this->inserirMunicipios($sigla, $municipios);
            $total += $count;
        }

        $this->info("Municípios inseridos: {$total}");
        $this->newLine();
        $this->info('Importação concluída. O sistema agora usa os dados do banco.');

        return 0;
    }

    private function buscarEstados(): array
    {
        $response = Http::timeout(30)->get(self::IBGE_ESTADOS_URL);
        if (!$response->successful()) {
            return [];
        }
        $data = $response->json();
        return is_array($data) ? $data : [];
    }

    private function inserirUfs(array $estados): int
    {
        $count = 0;
        foreach ($estados as $e) {
            $regiao = $e['regiao'] ?? [];
            Uf::updateOrCreate(
                ['id_ibge' => $e['id']],
                [
                    'sigla' => $e['sigla'],
                    'nome' => $e['nome'],
                    'regiao_sigla' => $regiao['sigla'] ?? null,
                    'regiao_nome' => $regiao['nome'] ?? null,
                ]
            );
            $count++;
        }
        return $count;
    }

    private function buscarMunicipios(array $estados): array
    {
        $estadosComId = array_filter($estados, fn ($e) => !empty($e['id']));
        if (empty($estadosComId)) {
            return [];
        }

        $responses = Http::pool(fn (Pool $pool) => collect($estadosComId)->map(
            fn ($estado) => $pool->as($estado['sigla'])
                ->timeout(30)
                ->get(self::IBGE_ESTADOS_URL . '/' . $estado['id'] . '/municipios')
        )->all());

        $resultado = [];
        foreach ($estadosComId as $estado) {
            $sigla = $estado['sigla'];
            $response = $responses[$sigla] ?? null;
            $municipios = ($response && $response->successful()) ? $response->json() : [];
            $resultado[$sigla] = is_array($municipios) ? $municipios : [];
        }
        return $resultado;
    }

    private function inserirMunicipios(string $sigla, array $municipios): int
    {
        $uf = Uf::where('sigla', $sigla)->first();
        if (!$uf) {
            return 0;
        }

        $count = 0;
        foreach ($municipios as $m) {
            Municipio::updateOrCreate(
                ['id_ibge' => $m['id']],
                [
                    'nome' => $m['nome'],
                    'uf_id' => $uf->id,
                ]
            );
            $count++;
        }
        return $count;
    }
}
