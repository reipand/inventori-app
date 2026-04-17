<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class AuthController extends Controller
{
    /**
     * POST /api/auth/login
     * Authenticate user and return JWT token.
     */
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->only('email', 'password');

        // Validate input presence
        if (empty($credentials['email']) || empty($credentials['password'])) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'Email dan kata sandi wajib diisi.',
                ],
            ], 400);
        }

        try {
            if (! $token = auth('api')->attempt($credentials)) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'UNAUTHORIZED',
                        'message' => 'Email atau kata sandi salah',
                    ],
                ], 401);
            }
        } catch (JWTException $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'INTERNAL_ERROR',
                    'message' => 'Tidak dapat membuat token.',
                ],
            ], 500);
        }

        $user = auth('api')->user();

        // Reject inactive accounts
        if (! $user->is_active) {
            auth('api')->logout();
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'Email atau kata sandi salah',
                ],
            ], 401);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'token' => $token,
                'token_type' => 'bearer',
                'expires_in' => config('jwt.ttl') * 60,
                'user' => [
                    'id' => $user->id,
                    'email' => $user->email,
                    'role' => $user->role,
                    'must_change_password' => (bool) $user->must_change_password,
                ],
            ],
        ]);
    }

    /**
     * POST /api/auth/logout
     * Invalidate (blacklist) the current JWT token.
     */
    public function logout(): JsonResponse
    {
        try {
            JWTAuth::invalidate(JWTAuth::getToken());
        } catch (JWTException $e) {
            // Token already invalid or missing — treat as logged out
        }

        return response()->json([
            'success' => true,
            'data' => ['message' => 'Berhasil keluar.'],
        ]);
    }

    /**
     * GET /api/auth/me
     * Return the currently authenticated user's info.
     */
    public function me(): JsonResponse
    {
        $user = auth('api')->user();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'email' => $user->email,
                'role' => $user->role,
                'must_change_password' => (bool) $user->must_change_password,
            ],
        ]);
    }

    /**
     * POST /api/auth/change-password
     * Change the authenticated user's password.
     */
    public function changePassword(Request $request): JsonResponse
    {
        $user = auth('api')->user();

        $oldPassword = $request->input('old_password');
        $newPassword = $request->input('new_password');
        $newPasswordConfirmation = $request->input('new_password_confirmation');

        // Validate required fields
        $missing = [];
        if (empty($oldPassword)) {
            $missing[] = 'old_password';
        }
        if (empty($newPassword)) {
            $missing[] = 'new_password';
        }
        if (empty($newPasswordConfirmation)) {
            $missing[] = 'new_password_confirmation';
        }

        if (! empty($missing)) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'Field berikut wajib diisi: ' . implode(', ', $missing),
                    'fields' => $missing,
                ],
            ], 400);
        }

        // Verify old password
        if (! Hash::check($oldPassword, $user->password)) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'Kata sandi lama tidak sesuai.',
                    'fields' => ['old_password'],
                ],
            ], 400);
        }

        // Confirm new password matches
        if ($newPassword !== $newPasswordConfirmation) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'Konfirmasi kata sandi baru tidak cocok.',
                    'fields' => ['new_password_confirmation'],
                ],
            ], 400);
        }

        // Minimum length check
        if (strlen($newPassword) < 8) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'Kata sandi baru minimal 8 karakter.',
                    'fields' => ['new_password'],
                ],
            ], 400);
        }

        $user->password = $newPassword;
        $user->must_change_password = false;
        $user->save();

        return response()->json([
            'success' => true,
            'data' => ['message' => 'Kata sandi berhasil diubah.'],
        ]);
    }
}
