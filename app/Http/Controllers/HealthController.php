<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Sistema')]
class HealthController extends Controller
{
    // -------------------------------------------------------
    // GET /api/health
    // -------------------------------------------------------

    #[OA\Get(
        path: '/api/health',
        summary: 'Verifica a saúde da aplicação',
        description: 'Retorna status do banco de dados e cache. Útil para healthchecks de orquestração (Docker, Kubernetes).',
        tags: ['Sistema'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Aplicação saudável',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'ok'),
                        new OA\Property(property: 'database', type: 'string', example: 'ok'),
                        new OA\Property(property: 'cache', type: 'string', example: 'ok'),
                        new OA\Property(property: 'version', type: 'string', example: '1.0.0'),
                    ]
                )
            ),
            new OA\Response(response: 503, description: 'Serviço indisponível'),
        ]
    )]
    public function check(): JsonResponse
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'cache'    => $this->checkCache(),
        ];

        $allOk = ! in_array('error', $checks, true);
        $status = $allOk ? 200 : 503;

        return response()->json(array_merge(
            ['status' => $allOk ? 'ok' : 'degraded'],
            $checks,
            ['version' => config('app.version', '1.0.0')],
        ), $status);
    }

    private function checkDatabase(): string
    {
        try {
            DB::connection()->getPdo();

            return 'ok';
        } catch (\Exception) {
            return 'error';
        }
    }

    private function checkCache(): string
    {
        try {
            Cache::put('health_check', true, 5);
            Cache::forget('health_check');

            return 'ok';
        } catch (\Exception) {
            return 'error';
        }
    }
}
