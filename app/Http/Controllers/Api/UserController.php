<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Helpers\ApiResponse;
use App\Http\Resources\UserResource;
use App\Http\Resources\UserCollection;
use App\Services\NotificationService;

class UserController extends Controller
{
    public function __construct(protected NotificationService $notificationService) {}

    /**
     * Index Users (List Reviewer/Penerbit/Admin)
     * GET /api/users
     */
    public function index(Request $request)
    {
        $query = User::with('role');

        // Jika frontend mengirim include_deleted=1,
        // maka user yang sudah soft delete tetap ikut ditampilkan
        if ($request->boolean('include_deleted')) {
            $query->withTrashed();
        }

        if ($request->has('role') && $request->role != '') {
            $query->whereHas('role', function ($q) use ($request) {
                $q->where('name', $request->role);
            });
        }

        if ($request->has('search') && $request->search != '') {
            $searchTerm = $request->search;

            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%")
                    ->orWhere('email', 'like', "%{$searchTerm}%");
            });
        }

        $users = $query->orderBy('created_at', 'desc')->paginate(10);

        return ApiResponse::success(
            'Data pengguna berhasil diambil.',
            new UserCollection($users),
            200
        );
    }

    /**
     * Create Users (List Reviewer/Penerbit/Admin)
     * POST /api/users
     */
    public function store(Request $request)
    {
        // Validasi input
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'role_name' => 'required|in:reviewer,penerbit,admin',
            'password' => 'nullable|string|min:6',
        ]);

        // Jika validasi gagal
        if ($validator->fails()) {

            // Email sudah digunakan
            if ($validator->errors()->has('email')) {
                return ApiResponse::error('Email sudah digunakan. Silahkan login atau gunakan email lain', 409, $validator->errors());
            }

            return ApiResponse::error('Validasi gagal.', 422, $validator->errors());
        }

        try {
            DB::beginTransaction();

            // Mencari role berdasarkan nama
            $role = Role::where('name', $request->role_name)->first();

            if (!$role) {
                throw new \Exception("Role '{$request->role_name}' tidak ditemukan.");
            }

            // Meng-generate password random jika tidak diisi
            $plainPassword = $request->password ?? Str::random(10);

            // Menyimpan user
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($plainPassword),
                'role_id' => $role->id,
                'status' => 'active',
            ]);

            DB::commit();

            return ApiResponse::success(
                "Akun {$request->role_name} berhasil dibuat. Kredensial telah dikirim ke email.",
                new UserResource($user),
                201
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponse::error('Gagal membuat akun: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Display the specified resource.
     * GET /api/users/{id}
     */
    public function show(string $id)
    {
        $user = User::withTrashed()
            ->with('role')
            ->find($id);

        if (!$user) {
            return ApiResponse::error('Pengguna tidak ditemukan.', 404);
        }

        return ApiResponse::success(
            'Detail pengguna berhasil diambil.',
            new UserResource($user),
            200
        );
    }

    /**
     * Update the specified resource in storage.
     * PATCH /api/users/{id}
     */
    public function update(Request $request, string $id)
    {
        // Mencari user
        $user = User::with('role')->find($id);

        // Jika user tidak ditemukan
        if (!$user) {
            return ApiResponse::error('Pengguna tidak ditemukan.', 404);
        }

        // Validasi input
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $user->id,
            'status' => 'sometimes|in:active,inactive',
            'role_name' => 'sometimes|in:reviewer,penerbit,admin',
            'password' => 'sometimes|string|min:6',
        ]);

        // Jika validasi gagal
        if ($validator->fails()) {
            return ApiResponse::error('Validasi gagal.', 422, $validator->errors());
        }

        try {
            DB::beginTransaction();

            $validated = $validator->validated();

            // Update name
            if (isset($validated['name'])) {
                $user->name = $validated['name'];
            }

            // Update email
            if (isset($validated['email'])) {
                $user->email = $validated['email'];
            }

            // Update password (hash jika diisi)
            if (isset($validated['password'])) {
                $user->password = Hash::make($validated['password']);
            }

            // Update status
            if (isset($validated['status'])) {
                $user->status = $validated['status'];
            }

            // Update role
            if (isset($validated['role_name'])) {
                $role = Role::where('name', $validated['role_name'])->first();
                if (!$role) {
                    throw new \Exception("Role tidak valid.");
                }
                $user->role_id = $role->id;
            }

            $user->save();

            // Refresh relasi
            $user->refresh();

            DB::commit();

            return ApiResponse::success(
                'Data pengguna berhasil diperbarui.',
                new UserResource($user)
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponse::error('Gagal memperbarui data: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Hapus akun berdasarkan akun.
     * DELETE /api/users/{id}
     */
    public function destroy(string $id)
    {
        $user = User::find($id);

        if (!$user) {
            return ApiResponse::error('Pengguna tidak ditemukan.', 404);
        }

        // Mencegah admin menonaktifkan akun sendiri
        if (Auth::id() === $user->id) {
            return ApiResponse::error('Anda tidak dapat menonaktifkan akun sendiri.', 400);
        }

        try {
            DB::beginTransaction();

            $user->status = 'inactive';
            $user->save();

            $user->delete(); // Soft delete

            DB::commit();

            return ApiResponse::success(
                'Akun berhasil dinonaktifkan/dihapus.',
                null,
                200
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponse::error('Gagal menonaktifkan akun: ' . $e->getMessage(), 500);
        }
    }
}
