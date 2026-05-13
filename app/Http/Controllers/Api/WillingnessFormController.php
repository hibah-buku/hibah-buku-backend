<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Helpers\ApiResponse;
use App\Http\Resources\WillingnessFormResource;
use App\Models\WillingnessForm;
use App\Http\Resources\WillingnessFormCollection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

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

        // Update status menjadi 'approved'
        // langkah berikutnya: buat logic untuk otomatis membuat User & Author dari data ini
        $form->update(['status' => 'approved']);

        return ApiResponse::success(
            'Form disetujui. Sistem akan memproses pembuatan akun penulis.',
            new WillingnessFormResource($form)
        );
    }
}
