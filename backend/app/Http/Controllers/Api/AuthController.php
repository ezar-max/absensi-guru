<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Login for mobile secretary user.
     *
     * @throws ValidationException
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|string',
            'password' => 'required|string',
            'device' => 'nullable|string',
        ], [
            'email.required' => 'Username atau NIS wajib diisi.',
            'password.required' => 'Kata sandi wajib diisi.',
        ]);

        $loginInput = trim($request->email);

        $user = User::where(function ($query) use ($loginInput) {
            $query->where('nis', $loginInput)
                ->orWhere('email', $loginInput)
                ->orWhere('name', $loginInput);
        })->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'message' => ['NIS atau kata sandi salah.'],
            ]);
        }

        if (!$user->is_active) {
            throw ValidationException::withMessages([
                'message' => ['Akun Anda telah dinonaktifkan. Silakan hubungi admin.'],
            ]);
        }

        if ($user->role !== 'secretary') {
            throw ValidationException::withMessages([
                'message' => ['Akun ini tidak memiliki akses ke aplikasi mobile.'],
            ]);
        }

        $deviceName = $request->device ?: 'mobile-device';
        $token = $user->createToken($deviceName)->plainTextToken;

        AuditLog::log(
            actor: $user,
            action: 'login',
            tableName: 'users',
            recordId: $user->id,
            detail: ['device' => $deviceName]
        );

        $classes = $user->classes()->get(['classes.id', 'classes.name'])->map(function ($c) {
            return [
                'id' => $c->id,
                'name' => $c->name,
            ];
        });

        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'nis' => $user->nis,
                'email' => $user->email,
                'role' => $user->role,
            ],
            'classes' => $classes,
        ]);
    }

    /**
     * Logout and revoke the current Sanctum token.
     */
    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user && $user->currentAccessToken()) {
            $user->currentAccessToken()->delete();
        }

        return response()->json([
            'message' => 'Berhasil keluar.',
        ]);
    }
}

