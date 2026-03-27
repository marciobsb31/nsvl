<?php

namespace App\Http\Controllers;

use App\Models\Esfera;
use Illuminate\Http\JsonResponse;

class EsferaController extends Controller
{
    public function index(): JsonResponse
    {
        $data = Esfera::query()
            ->orderBy('nome')
            ->get()
            ->map(fn (Esfera $e) => [
                'value' => strtolower($e->nome),
                'label' => $e->nome,
            ])
            ->all();

        return response()->json(['data' => $data]);
    }
}
