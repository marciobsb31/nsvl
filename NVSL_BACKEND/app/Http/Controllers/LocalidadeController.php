<?php

namespace App\Http\Controllers;

use App\Models\Municipio;
use App\Models\Uf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controller para dados de localidade (UF e Municípios).
 * Lê do banco de dados (tabelas ufs e municipios).
 * Dados replicados do IBGE; não consumimos o serviço em tempo de execução.
 */
class LocalidadeController extends Controller
{
    /**
     * GET /api/localidades/ufs
     *
     * Retorna todas as UFs ordenadas por nome.
     */
    public function ufs(): JsonResponse
    {
        $data = Uf::orderBy('nome')
            ->get()
            ->map(fn (Uf $e) => [
                'value' => $e->sigla,
                'label' => $e->sigla . ' - ' . $e->nome,
            ])
            ->values()
            ->all();

        return response()->json(['data' => $data]);
    }

    /**
     * GET /api/localidades/completo
     *
     * Retorna todas as UFs e seus municípios em uma única requisição.
     */
    public function completo(): JsonResponse
    {
        $ufs = Uf::orderBy('nome')
            ->get()
            ->map(fn (Uf $e) => [
                'value' => $e->sigla,
                'label' => $e->sigla . ' - ' . $e->nome,
                'id' => $e->id,
            ])
            ->values()
            ->all();

        $municipiosPorUf = [];
        foreach (Uf::with(['municipios' => fn ($q) => $q->orderBy('nome')])->orderBy('nome')->get() as $uf) {
            $municipiosPorUf[$uf->sigla] = $uf->municipios->map(fn (Municipio $m) => [
                'value' => $m->nome,
                'label' => $m->nome,
            ])->values()->all();
        }

        return response()->json([
            'data' => [
                'ufs' => $ufs,
                'municipios_por_uf' => $municipiosPorUf,
            ],
        ]);
    }

    /**
     * GET /api/localidades/municipios?uf=XX
     *
     * Retorna municípios da UF informada.
     */
    public function municipios(Request $request): JsonResponse
    {
        $uf = strtoupper($request->query('uf', ''));
        if (strlen($uf) !== 2) {
            return response()->json([
                'message' => 'Parâmetro uf é obrigatório e deve ter 2 caracteres (sigla).',
            ], 422);
        }

        $data = Municipio::whereHas('uf', fn ($q) => $q->where('sigla', $uf))
            ->orderBy('nome')
            ->get()
            ->map(fn (Municipio $m) => [
                'value' => $m->nome,
                'label' => $m->nome,
            ])
            ->values()
            ->all();

        return response()->json(['data' => $data]);
    }
}
