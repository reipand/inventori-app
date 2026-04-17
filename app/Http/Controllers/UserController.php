<?php

namespace App\Http\Controllers;

use App\Mail\TempPasswordMail;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Facades\JWTAuth;

class UserController extends Controller
{
    /**
     * GET /api/users
     * Return paginated list of users (Pengelola only).
     */
    public function index(): JsonResponse
    {
        $users = User::select('id', 'email', 'role', 'is_active', 'created_at')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $users,
        ]);
    }

    /**
     * POST /api/users
     * Create a new user account with a temporary password (Pengelola only).
     */
    public function store(Request $request): JsonResponse
    {
        $email = $request->input('email');
        $role  = $request->input('role');

        // Validate required fields
        $missing = [];
        if (empty($email)) {
            $missing[] = 'email';
        }
        if (empty($role)) {
            $missing[] = 'role';
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

        // Validate role value
        if (! in_array($role, ['pengelola', 'kasir'], true)) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'Peran tidak valid. Nilai yang diizinkan: pengelola, kasir.',
                    'fields' => ['role'],
                ],
            ], 400);
        }

        // Validate email format
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'Format email tidak valid.',
                    'fields' => ['email'],
                ],
            ], 400);
        }

        // Check for duplicate email
        if (User::where('email', $email)->exists()) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'CONFLICT',
                    'message' => 'Email sudah terdaftar',
                ],
            ], 409);
        }

        // Generate temporary password
        $tempPassword = Str::random(10);

        $user = User::create([
            'email'               => $email,
            'password'            => $tempPassword,
            'role'                => $role,
            'is_active'           => true,
            'must_change_password' => true,
        ]);

        // Send temporary password via email (uses log driver in local dev)
        Mail::to($user->email)->send(new TempPasswordMail($tempPassword, $role));

        return response()->json([
            'success' => true,
            'data' => [
                'id'                  => $user->id,
                'email'               => $user->email,
                'role'                => $user->role,
                'is_active'           => $user->is_active,
                'must_change_password' => $user->must_change_password,
                'created_at'          => $user->created_at,
                // Include temp password in response so frontend can display it
                // (useful when email is not configured)
                'temp_password'       => $tempPassword,
            ],
        ], 201);
    }

    /**
     * PUT /api/users/:id/deactivate
     * Deactivate a user account and revoke their active tokens (Pengelola only).
     */
    public function deactivate(string $id): JsonResponse
    {
        $user = User::find($id);

        if (! $user) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Pengguna tidak ditemukan.',
                ],
            ], 404);
        }

        // Prevent deactivating yourself
        $currentUser = auth('api')->user();
        if ($currentUser && $currentUser->id === $user->id) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'BUSINESS_RULE_VIOLATION',
                    'message' => 'Anda tidak dapat menonaktifkan akun Anda sendiri.',
                ],
            ], 422);
        }

        $user->is_active = false;
        $user->save();

        // Attempt to invalidate the user's current token if they have one.
        // Since JWT tokens are stateless, the is_active=false check in the
        // CheckActiveUser middleware will reject all subsequent requests.
        // For immediate revocation, we try to blacklist via JWTAuth if possible.
        try {
            $token = JWTAuth::fromUser($user);
            JWTAuth::setToken($token)->invalidate();
        } catch (\Throwable) {
            // Blacklisting a freshly-generated token is best-effort.
            // The is_active check handles the actual enforcement.
        }

        return response()->json([
            'success' => true,
            'data' => [
                'message' => 'Akun pengguna berhasil dinonaktifkan.',
                'id'       => $user->id,
                'email'    => $user->email,
                'is_active' => false,
            ],
        ]);
    }
}
