<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Token tidak valid atau kadaluwarsa',
                'data' => null
            ], 401);
        }
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'User tidak ditemukan.',
                'data' => null
            ], 404);
        }

        // Cek Role
        $currentRole = $user->role->name;

        if (!in_array($currentRole, $roles)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Akses ditolak. Anda tidak memiliki izin',
                'data' => null
            ], 403);
        }

        return $next($request);
    }
}
