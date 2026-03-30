<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // API middleware stack — sem EnsureFrontendRequestsAreStateful para evitar CSRF;
        // acesso via Bearer token (sessionStorage) apenas.

        // Aliases de middleware
        $middleware->alias([
            'anonymize' => \App\Http\Middleware\AnonymizeResponseMiddleware::class,
        ]);

        // Privacy by design: remove campos sensíveis de toda resposta JSON da API
        $middleware->prependToGroup('api', \App\Http\Middleware\AnonymizeResponseMiddleware::class);

        // CORS via configuração
        $middleware->append(\Illuminate\Http\Middleware\HandleCors::class);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e) {
            return response()->json([
                'message' => 'Não autenticado.',
                'error'   => 'unauthenticated',
            ], 401);
        });

        $exceptions->render(function (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Dados inválidos.',
                'errors'  => $e->errors(),
            ], 422);
        });

        $exceptions->render(function (\App\Exceptions\ApiException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'error' => $e->error(),
            ], $e->status());
        });

        $exceptions->render(function (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'message' => 'Acesso não permitido.',
                'error' => 'forbidden',
            ], 403);
        });

        $exceptions->render(function (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Recurso não encontrado.',
                'error' => 'not_found',
            ], 404);
        });

        $exceptions->render(function (\Illuminate\Http\Exceptions\ThrottleRequestsException $e) {
            return response()->json([
                'message' => 'Limite de requisições excedido. Tente novamente em instantes.',
                'error' => 'too_many_requests',
            ], 429);
        });

        $exceptions->render(function (Throwable $e, Request $request) {
            if (!$request->is('api/*')) {
                return null;
            }

            $requestId = (string) Str::uuid();
            report($e);

            $debug = (bool) config('app.debug', false);

            return response()->json([
                'message' => $debug ? ($e->getMessage() ?: 'Erro interno.') : 'Erro interno do servidor.',
                'error' => 'internal_error',
                'request_id' => $requestId,
            ], 500);
        });
    })
    ->create();
