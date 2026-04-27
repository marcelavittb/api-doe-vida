<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();

        // ❌ não autenticado
        if (!$user) {
            return response()->json([
                'error' => 'Não autenticado'
            ], 401);
        }

        // ❌ não tem permissão
        if (!$user->hasAnyRole($roles)) {
            return response()->json([
                'error' => 'Sem permissão'
            ], 403);
        }

        return $next($request);
    }
}