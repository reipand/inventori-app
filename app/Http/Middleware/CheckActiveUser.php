<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Facades\JWTAuth;

class CheckActiveUser
{
    /**
     * Reject requests from deactivated users even if their JWT token is still valid.
     * Apply this middleware after auth:api.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get the authenticated user ID from the JWT token directly
        // to avoid stale in-memory guard state between requests.
        try {
            $payload = JWTAuth::parseToken()->getPayload();
            $userId  = $payload->get('sub');
            $user    = $userId ? User::find($userId) : null;
        } catch (\Throwable) {
            // If token parsing fails, auth:api already handled it
            return $next($request);
        }

        if ($user && ! $user->is_active) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'Akun Anda telah dinonaktifkan.',
                ],
            ], 401);
        }

        return $next($request);
    }
}
