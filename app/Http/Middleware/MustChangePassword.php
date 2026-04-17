<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MustChangePassword
{
    /**
     * Block access to protected routes when the user has a temporary password
     * that must be changed. The change-password route itself is always allowed.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth('api')->user();

        if ($user && $user->must_change_password) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'FORBIDDEN',
                    'message' => 'Anda harus mengganti kata sandi sebelum dapat mengakses halaman ini.',
                ],
            ], 403);
        }

        return $next($request);
    }
}
