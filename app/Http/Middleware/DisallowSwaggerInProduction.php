<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bloqueia a UI e o JSON do Swagger em ambientes de produção.
 */
class DisallowSwaggerInProduction
{
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->environment(['production', 'prod'])) {
            abort(404);
        }

        return $next($request);
    }
}
