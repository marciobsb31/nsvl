<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CheckPermissionMiddleware
{
    public function handle(Request $request, Closure $next, string $permission)
    {
        $usuario = auth()->user();

        if (!$usuario || !$usuario->hasPermissao($permission)) {
            abort(Response::HTTP_FORBIDDEN, 'Sem permissão');
        }

        return $next($request);
    }
}
