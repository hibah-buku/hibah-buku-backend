<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use App\Helpers\ApiResponse; // Import helper jika ada
use App\Http\Resources\UserCollection;
use App\Http\Resources\UserResource;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (!$token = JWTAuth::attempt($credentials)) {
            return ApiResponse::error('Email atau password salah.', 401);
        }

         $user = JWTAuth::user();

        if ($user->status !== 'active') {
            JWTAuth::invalidate($token);

           return ApiResponse::error('Akun Anda tidak aktif. Silakan hubungi admin.', 403);
        }

        return ApiResponse::success('Login berhasil.', [
            'token' => $token,
            'user' => new UserResource($user->load('role', 'author')),
            'token_type' => 'bearer',
            'expires_in' => JWTAuth::factory()->getTTL() * 60,
        ], 200);
    }

    public function logout()
    {
        try {
            JWTAuth::invalidate(JWTAuth::getToken());

            return ApiResponse::success('Logout berhasil.', null, 200);
        } catch (\Exception $e){
            return ApiResponse::error('Gagal logout.', 500);
        }
    }

    /**
     * Get Authenticated User Profile
     */
    public function me()
    {
        $user = JWTAuth::user();
        return ApiResponse::success('Profil pengguna.',
        new UserResource($user),
        200);
    }
}
