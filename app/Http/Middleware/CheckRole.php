<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Check that the authenticated user has the required role.
     * Usage: middleware('role:pengelola')
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $user = auth('api')->user();

        if (! $user || $user->role !== $role) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'FORBIDDEN',
                    'message' => 'Anda tidak memiliki izin untuk mengakses halaman ini',
                ],
            ], 403);
        }

        return $next($request);
    }
}
