<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleOrPermissionMiddleware
{
    public function handle(Request $request, Closure $next, string $roles = '', string $permissions = ''): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['error' => 'Nao autenticado'], 401);
        }

        $roleNames = $this->split($roles);
        $permissionNames = $this->split($permissions);

        if ($roleNames !== [] && $user->hasAnyRole($roleNames)) {
            return $next($request);
        }

        if ($permissionNames !== [] && $user->hasAnyPermission($permissionNames)) {
            return $next($request);
        }

        return response()->json(['error' => 'Sem permissao'], 403);
    }

    private function split(string $value): array
    {
        return array_values(array_filter(array_map('trim', explode('|', $value))));
    }
}
