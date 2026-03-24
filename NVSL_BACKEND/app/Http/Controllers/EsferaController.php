<?php

namespace App\Http\Controllers;

use App\Models\Esfera;
use Illuminate\Http\JsonResponse;

/**
 * Controller para dados de Esfera de Atuação.
 * Retorna lista de esferas para combos/autocomplete.
 */
class EsferaController extends Controller
{
    /**
     * GET /api/esferas
     *
     * Retorna todas as esferas ordenadas.
     */
    public function index(): JsonResponse
    {
        $data = Esfera::query()
            ->orderBy('ordem')
            ->orderBy('nome')
            ->get()
            ->map(fn (Esfera $e) => [
                'value' => $e->codigo,
                'label' => $e->nome,
            ])
            ->all();

        return response()->json(['data' => $data]);
    }
}
