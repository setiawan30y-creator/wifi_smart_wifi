<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\LoginRequest;
use App\Http\Resources\V1\UserResource;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(LoginRequest $request)
    {
        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return ApiResponse::error('Email atau password salah.', 401, 'INVALID_CREDENTIALS');
        }

        if ($user->tenant && $user->tenant->status !== 'active' && !$user->is_saas_admin) {
            return ApiResponse::error('Perusahaan sedang dinonaktifkan.', 403, 'TENANT_SUSPENDED');
        }

        $token = $user->createToken($request->input('device_name', 'mobile-app'))->plainTextToken;

        return ApiResponse::success([
            'token_type' => 'Bearer',
            'access_token' => $token,
            'user' => new UserResource($user),
        ], 'Login berhasil.');
    }

    public function me(Request $request)
    {
        return ApiResponse::success(new UserResource($request->user()), 'Profil pengguna.');
    }

    public function logout(Request $request)
    {
        $request->user()?->currentAccessToken()?->delete();

        return ApiResponse::success(null, 'Logout berhasil.');
    }
}
