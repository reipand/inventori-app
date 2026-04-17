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
    public function index(): JsonResponse
    {
        $users = User::select('id', 'email', 'role', 'is_active', 'created_at')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json(['success' => true, 'data' => $users]);
    }

    public function store(Request $request): JsonResponse
    {
        $email    = trim((string) $request->input('email', ''));
        $role     = $request->input('role');
        $password = $request->input('password');

        $missing = [];
        if (empty($email))  $missing[] = 'email';
        if (empty($role))   $missing[] = 'role';

        if (! empty($missing)) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'VALIDATION_ERROR', 'message' => 'Field berikut wajib diisi: ' . implode(', ', $missing), 'fields' => $missing],
            ], 400);
        }

        if (! in_array($role, ['pengelola', 'kasir'], true)) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'VALIDATION_ERROR', 'message' => 'Peran tidak valid.', 'fields' => ['role']],
            ], 400);
        }

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'VALIDATION_ERROR', 'message' => 'Format email tidak valid.', 'fields' => ['email']],
            ], 400);
        }

        if (User::where('email', $email)->exists()) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'CONFLICT', 'message' => 'Email sudah terdaftar.'],
            ], 409);
        }

        $usingTemp = empty($password);
        $finalPassword = $usingTemp ? Str::random(10) : $password;

        $user = User::create([
            'email'                => $email,
            'password'             => $finalPassword,
            'role'                 => $role,
            'is_active'            => true,
            'must_change_password' => $usingTemp,
        ]);

        if ($usingTemp) {
            Mail::to($user->email)->send(new TempPasswordMail($finalPassword, $role));
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id'                   => $user->id,
                'email'                => $user->email,
                'role'                 => $user->role,
                'is_active'            => $user->is_active,
                'must_change_password' => $user->must_change_password,
                'created_at'           => $user->created_at,
                'temp_password'        => $usingTemp ? $finalPassword : null,
            ],
        ], 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $user = User::find($id);

        if (! $user) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'NOT_FOUND', 'message' => 'Pengguna tidak ditemukan.'],
            ], 404);
        }

        $currentUser = auth('api')->user();

        if ($request->has('email')) {
            $email = trim((string) $request->input('email', ''));
            if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return response()->json([
                    'success' => false,
                    'error' => ['code' => 'VALIDATION_ERROR', 'message' => 'Format email tidak valid.', 'fields' => ['email']],
                ], 400);
            }
            if (User::where('email', $email)->where('id', '!=', $id)->exists()) {
                return response()->json([
                    'success' => false,
                    'error' => ['code' => 'CONFLICT', 'message' => 'Email sudah digunakan akun lain.'],
                ], 409);
            }
            $user->email = $email;
        }

        if ($request->has('role')) {
            $role = $request->input('role');
            if (! in_array($role, ['pengelola', 'kasir'], true)) {
                return response()->json([
                    'success' => false,
                    'error' => ['code' => 'VALIDATION_ERROR', 'message' => 'Peran tidak valid.', 'fields' => ['role']],
                ], 400);
            }
            $user->role = $role;
        }

        if ($request->has('password') && ! empty($request->input('password'))) {
            $user->password = $request->input('password');
            $user->must_change_password = false;
        }

        if ($request->has('is_active')) {
            if ($currentUser && $currentUser->id === $user->id && ! $request->boolean('is_active')) {
                return response()->json([
                    'success' => false,
                    'error' => ['code' => 'BUSINESS_RULE_VIOLATION', 'message' => 'Anda tidak dapat menonaktifkan akun Anda sendiri.'],
                ], 422);
            }
            $user->is_active = $request->boolean('is_active');
        }

        $user->save();

        return response()->json([
            'success' => true,
            'data' => [
                'id'                   => $user->id,
                'email'                => $user->email,
                'role'                 => $user->role,
                'is_active'            => $user->is_active,
                'must_change_password' => $user->must_change_password,
            ],
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        $user = User::find($id);

        if (! $user) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'NOT_FOUND', 'message' => 'Pengguna tidak ditemukan.'],
            ], 404);
        }

        $currentUser = auth('api')->user();
        if ($currentUser && $currentUser->id === $user->id) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'BUSINESS_RULE_VIOLATION', 'message' => 'Anda tidak dapat menghapus akun Anda sendiri.'],
            ], 422);
        }

        $user->delete();

        return response()->json(['success' => true, 'data' => ['message' => 'Akun pengguna berhasil dihapus.']]);
    }

    public function deactivate(string $id): JsonResponse
    {
        $user = User::find($id);

        if (! $user) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'NOT_FOUND', 'message' => 'Pengguna tidak ditemukan.'],
            ], 404);
        }

        $currentUser = auth('api')->user();
        if ($currentUser && $currentUser->id === $user->id) {
            return response()->json([
                'success' => false,
                'error' => ['code' => 'BUSINESS_RULE_VIOLATION', 'message' => 'Anda tidak dapat menonaktifkan akun Anda sendiri.'],
            ], 422);
        }

        $user->is_active = false;
        $user->save();

        try {
            $token = JWTAuth::fromUser($user);
            JWTAuth::setToken($token)->invalidate();
        } catch (\Throwable) {}

        return response()->json([
            'success' => true,
            'data' => ['message' => 'Akun pengguna berhasil dinonaktifkan.', 'id' => $user->id, 'email' => $user->email, 'is_active' => false],
        ]);
    }
}
