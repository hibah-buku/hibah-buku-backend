<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Helpers\ApiResponse;
use Illuminate\Validation\Rule;
use App\Http\Resources\WillingnessFormResource;
use App\Models\WillingnessForm;
use App\Models\User;
use App\Models\Role;
use App\Models\Author;
use App\Http\Resources\WillingnessFormCollection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class WillingnessFormController extends Controller
{
    /**
     * UC-02: Submit Form Kesediaan Penulis
     * Endpoint: POST /api/auth/register-willingness
     */
    public function store(Request $request)
    {
         // Validasi Input Sesuai ERD & Requirement Min 2 Penulis
        $validator = Validator::make($request->all(), [
            // Main Author
            'main_author_name' => 'required|string|max:255',
            'main_author_email' => [
                'required',
                'email',
                Rule::unique('willingness_forms')->where(function ($query) use ($request) {
                    return $query->where('main_author_email', $request->main_author_email)
                                ->where('status', 'pending');
                }),
            ],
            'main_author_institution' => 'required|string|max:255',
            'main_author_phone' => 'required|string|max:20',

            // Co-Author 1 (Wajib)
            'co_author_1_name' => 'required|string|max:255',
            'co_author_1_email' => 'required|email',
            'co_author_1_institution' => 'required|string|max:255',

            // Co-Author 2-4 (Opsional)
            'co_author_2_name' => 'nullable|string|max:255',
            'co_author_2_email' => 'nullable|email',
            'co_author_2_institution' => 'nullable|string|max:255',

            'co_author_3_name' => 'nullable|string|max:255',
            'co_author_3_email' => 'nullable|email',
            'co_author_3_institution' => 'nullable|string|max:255',

            'co_author_4_name' => 'nullable|string|max:255',
            'co_author_4_email' => 'nullable|email',
            'co_author_4_institution' => 'nullable|string|max:255',

            // Data Buku
            'book_title' => 'required|string|max:255',
            'book_type' => 'required|in:bukuajar,bukureferensi',
            'field_of_study' => 'required|string|max:255',
            'book_abstract' => 'nullable|string',
            'target_audience' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('Validasi gagal.', 422, $validator->errors());
        }

        $form = WillingnessForm::create($validator->validated());

        // Return Response Standar
        return ApiResponse::success(
            'Form kesediaan berhasil dikirim. Menunggu verifikasi admin.',
            new WillingnessFormResource($form),
            201
        );
    }

    public function index(Request $request)
    {
        $query = WillingnessForm::query();

        if ($request->has('status')) {
                $query->where('status', $request->status);
        } else {
            $query->where('status', 'pending'); // Default tetap pending
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('main_author_name', 'like', "%{$search}%")
                ->orWhere('main_author_email', 'like', "%{$search}%")
                ->orWhere('book_title', 'like', "%{$search}%");
            });
        }

        $forms = $query->orderBy('created_at', 'desc')
                   ->paginate(10);

        $collection = new WillingnessFormCollection($forms);

        return ApiResponse::success(
            'Daftar form kesediaan.',
            [
                'items' => $collection->toArray($request)['data'],
                'meta' => $collection->toArray($request)['meta'],
                '_links' => $collection->toArray($request)['_links'],
            ]
        );
    }

    public function approve($id)
    {
        $form = WillingnessForm::find($id);

        if (!$form) {
            return ApiResponse::error('Data form tidak ditemukan.', 404);
        }

        if ($form->status !== 'pending') {
            return ApiResponse::error("Form sudah berstatus '{$form->status}', tidak bisa diproses ulang.", 422);
        }

        if (User::where('email' , $form->main_author_email)->exists()) {
            return ApiResponse::error('Akun penulis dengan email tersebut sudah terdaftar di sistem', 409);
        }

        try {
            DB::beginTransaction();

            $randomPassword = Str::random(8);

            $authorRole = Role::where('name', 'penulis')->first();
            if (!$authorRole) {
                throw new \Exception("Role 'penulis' tidak ditemukan di database");
            }

            // Membuat akun role penulis
            $user = User::create([
                'name' => $form->main_author_name,
                'email' => $form->main_author_email,
                'password' => Hash::make($randomPassword),
                'role_id' => $authorRole->id,
                'status' => 'active'
            ]);

            // Menulis author profile
            Author::create([
                'user_id' => $user->id,
                'institution' => $form->main_author_institution,
                'field_of_study' => $form->field_of_study,
            ]);

            $form->update([
                'status' => 'approved',
                'admin_notes' => "Akun penulis otomatis dibuat pada " . now()->toISOString(),
            ]);

            DB::commit();

            $form->setAttribute('linked_user_id', $user->id);
            $form->setAttribute('temporary_password', $randomPassword); // Hanya untuk response admin

            return ApiResponse::success(
                'Form disetujui. Akun penulis berhasil dibuat.',
                new WillingnessFormResource($form)
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponse::error('Gagal memproses pembuatan akun: ' . $e->getMessage(), 500);
        }
    }

    public function reject(Request $request, $id)
    {
        $form = WillingnessForm::find($id);

        if (!$form) {
            return ApiResponse::error('Data form tidak ditemukan.', 404);
        }

        if ($form->status !== 'pending') {
            return ApiResponse::error("Form sudah berstatus '{$form->status}', tidak bisa diproses ulang.", 422);
        }

        $validator = Validator::make($request->all(), [
            'rejection_reason' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('Validasi gagal.', 422, $validator->errors());
        }

        $form->update([
            'status'           => 'rejected',
            'rejection_reason' => $request->rejection_reason,
            'rejected_at'      => now(),
        ]);

        return ApiResponse::success(
            'Form ditolak.',
            new WillingnessFormResource($form->fresh())
        );
    }
}
