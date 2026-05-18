<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    /**
     * UC-03: Index Users (List Reviewer/Penerbit/Admin)
     * GET /api/users
     */
    public function index(Request $request)
    {
        $query = User::with('role');

        if ($request->has('role')) {
            $query->whereHas('role', function($q) use ($request) {
                $q->where('name', $request->role);
            });
        }

        $users = $query->orderBy('created_at', 'desc')->paginate(10);

        $data = $users->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role->name,
                'status' => $user->status,

                '_links' => [
                    'self' => [
                        'href' => url("/api/users/{$user->id}"),
                        'method' => 'GET'
                    ],
                    'update' => [
                        'href' => url("/api/users/{$user->id}"),
                        'method' => 'PATCH'
                    ],
                    'deactivate' => [
                        'href' => url("/api/users/{$user->id}"),
                        'method' => 'DELETE'
                    ]
                ]
            ];
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Data pengguna berhasil diambil.',
            'data' => $data,

            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'total' => $users->total(),
            ],

            '_links' => [
                'self' => [
                    'href' => url('/api/users'),
                    'method' => 'GET'
                ],
                'create' => [
                    'href' => url('/api/users'),
                    'method' => 'POST'
                ]
            ]
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {    
        // Validasi input
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'role' => 'required|string|in:reviewer,penerbit',
        ]);

        // Jika validasi gagal
        if ($validator->fails()) {

            // Email sudah digunakan
            if ($validator->errors()->has('email')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Email sudah digunakan. Silahkan login atau gunakan email lain.',
                    'data' => $validator->errors()
                ], 409);
            }

            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal.',
                'data' => $validator->errors()
            ], 422);
        }

        // Data valid
        $validated = $validator->validated();

        // Cari role
        $role = Role::where('name', $validated['role'])->first();

        // Role tidak valid
        if (!$role) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal. Role tidak valid.',
                'data' => null
            ], 400);
        }

        // Generate password random
        $plainPassword = Str::random(10);

        // Simpan user
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($plainPassword),
            'role_id' => $role->id,
            'status' => 'active',
        ]);

        // Response sukses
        return response()->json([
        'status' => 'success',
        'message' => 'Akun berhasil dibuat. Kredensial telah dikirim ke email.',
        'data' => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $role->name,
            'status' => $user->status,
            'created_at' => $user->created_at,

            '_links' => [
                'self' => [
                    'href' => url("/api/users/{$user->id}"),
                    'method' => 'GET'
                ],
                'update' => [
                    'href' => url("/api/users/{$user->id}"),
                    'method' => 'PATCH'
                ],
                'deactivate' => [
                    'href' => url("/api/users/{$user->id}"),
                    'method' => 'DELETE'
                ],
                'all_users' => [
                    'href' => url("/api/users"),
                    'method' => 'GET'
                ]
            ]
        ]
    ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $user = User::with('role')->find($id);

        // Jika user tidak ditemukan
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Pengguna tidak ditemukan.',
                'data' => null
            ], 404);
        }

        return response()->json([
        'status' => 'success',
        'message' => 'Detail pengguna berhasil diambil.',
        'data' => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role->name,
            'status' => $user->status,
            'created_at' => $user->created_at,

            '_links' => [
                'all_users' => [
                    'href' => url('/api/users'),
                    'method' => 'GET'
                ],
                'update' => [
                    'href' => url("/api/users/{$user->id}"),
                    'method' => 'PATCH'
                ],
                'deactivate' => [
                    'href' => url("/api/users/{$user->id}"),
                    'method' => 'DELETE'
                ]
            ]
        ]
    ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        // Cari user
        $user = User::with('role')->find($id);

        // Jika user tidak ditemukan
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Pengguna tidak ditemukan.',
                'data' => null
            ], 404);
        }

        // Validasi input
        $validator = Validator::make($request->all(), [
            'status' => 'nullable|in:active,inactive',
            'role' => 'nullable|in:reviewer,penerbit',
        ]);

        // Jika validasi gagal
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal. Role tidak valid.',
                'data' => null
            ], 400);
        }

        $validated = $validator->validated();

        /**
         * Update status
         */
        if (isset($validated['status'])) {
            $user->status = $validated['status'];
        }

        /**
         * Update role
         */
        if (isset($validated['role'])) {

            // Cari role berdasarkan nama
            $role = Role::where('name', $validated['role'])->first();

            // Jika role tidak ditemukan
            if (!$role) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validasi gagal. Role tidak valid.',
                    'data' => null
                ], 400);
            }

            // Update foreign key role_id
            $user->role_id = $role->id;
        }

        // Simpan perubahan
        $user->save();

        // Refresh relasi role
        $user->load('role');

        // Response sukses
        return response()->json([
            'status' => 'success',
            'message' => 'Data pengguna berhasil diperbarui.',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'status' => $user->status,
                'role' => $user->role->name,

                '_links' => [
                    'self' => [
                        'href' => url("/api/users/{$user->id}"),
                        'method' => 'GET'
                    ],
                    'deactivate' => [
                        'href' => url("/api/users/{$user->id}"),
                        'method' => 'DELETE'
                    ],
                    'all_users' => [
                        'href' => url('/api/users'),
                        'method' => 'GET'
                    ]
                ]
            ]
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        // Cari user
        $user = User::find($id);

        // Jika user tidak ditemukan
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Pengguna tidak ditemukan.',
                'data' => null
            ], 404);
        }

        // Cegah admin menonaktifkan akun sendiri
        if (auth()->id() == $user->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Akun sudah nonaktif atau Anda tidak dapat menonaktifkan akun sendiri.',
                'data' => null
            ], 400);
        }

        // Jika akun sudah inactive
        if ($user->status === 'inactive') {
            return response()->json([
                'status' => 'error',
                'message' => 'Akun sudah nonaktif atau Anda tidak dapat menonaktifkan akun sendiri.',
                'data' => null
            ], 400);
        }

        // Ubah status jadi inactive
        $user->status = 'inactive';

        // Simpan perubahan
        $user->save();

        // Soft delete
        $user->delete();

        // Response sukses
        return response()->json([
            'status' => 'success',
            'message' => 'Akun berhasil dinonaktifkan.',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'status' => 'inactive',
                'deleted_at' => $user->deleted_at,

                '_links' => [
                    'all_users' => [
                        'href' => url('/api/users'),
                        'method' => 'GET'
                    ]
                ]
            ]
        ], 200);
    }
}
