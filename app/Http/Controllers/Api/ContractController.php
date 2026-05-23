<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\Author;
use App\Http\Resources\ContractCollection;
use App\Http\Resources\ContractResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Helpers\ApiResponse;
use Illuminate\Support\Facades\Storage;

class ContractController extends Controller
{
    /**
     * UC-04: Penulis Upload Kontrak
     * Endpoint: POST /api/contracts
     * Access: Penulis Only
     */

    public function upload(Request $request)
    {
        $user = Auth::user();

        if (!$user->author) {
            return ApiResponse::error('Profile Penulis tidak ditemukan. Hubungi admin!', 403);
        }

        $validator = Validator::make($request->all(), [
            'contract_file' => 'required|file|mimes:pdf|max:5120',
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('Validasi file gagal', 422, $validator->errors());
        }

        try {
            $file = $request->file('contract_file');

            $fileName = time() . '_' . $file->getClientOriginalName();

            $originalName = $file->getClientOriginalName();

            $path = $file->storeAs('contracts', $fileName, 'public');

            $contract = Contract::updateOrCreate(
                ['author_id' => $user->author->id],
                [
                    'file_path' => $path,
                    'original_name' => $originalName,
                    'status' => 'contract_uploaded',
                ]
            );

            return ApiResponse::success('Kontrak berhasil diunggah. Menunggu validasi admin', new ContractResource($contract), 200);
        } catch (\Exception $e) {
            return ApiResponse::error('Gagal menggunggah file: ' . $e->getMessage(), 500);
        }
    }

    /**
     * UC-04: Penulis Melihat Kontrak Miliknya
     * Endpoint: GET /api/contracts/me
     * Access: Penulis only
     */

    public function myContract(Request $request)
    {
        $user = Auth::user();

        if (!$user->author) {
            return ApiResponse::error('Profile Penulis tidak ditemukan. Silahkan hubungi admin!', 403);
        }

        $contract = Contract::where('author_id', $user->author->id)->first();

        if (!$contract) {
            return ApiResponse::error('Anda belum menggunggah kontrak.', 404);
        }

        return ApiResponse::success(
            'Detail Kontrak Anda.',
            new ContractResource($contract)
        );
    }

    /**
     * UC-04: Admin Validasi Kontrak
     * Endpoint: PATCH /api/contracts/{contract}/validate
     * Access: Admin only
     */

    public function validateContract(Request $request, Contract $contract)
    {
        $user = Auth::user();
        $contract->update([
            'status' => 'contract_validated',
            'notes' => $request->input('notes', 'Kontrak divalidasi oleh admin'),
            'validated_by' => $user->id,
            'validated_at' => now(),
        ]);

        return ApiResponse::success(
            'Kontrak telah divalidasi. penulis dapat melanjutkan ke upload naskah.',
            new ContractResource($contract)
        );
    }

    public function rejectContract(Request $request, Contract $contract)
    {

        $user = Auth::user();
        $validator = Validator::make($request->all(), [
            'rejection_reason' => 'required|string|min:5',
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('Alasan penolakan wajib diisi (min. 5 karakter).', 422, $validator->errors());
        }

        try {
            $contract->update([
                'status' => 'contract_rejected',
                'notes' => $request->rejection_reason,
                'validated_by' => $user->id,
                'validated_at' => now()
            ]);

            return ApiResponse::success(
                'Kontrak ditolak. Silahkan perbaiki dan unggah ulang.',
                new ContractResource($contract)
            );
        } catch (\Exception $e) {
            return ApiResponse::error('Gagal menolak kontrak: ' . $e->getMessage(), 500);
        }

    }

    // Method index untuk Admin
    public function index(Request $request)
    {
        $query = Contract::with('author.user');

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $contracts = $query->orderBy('created_at', 'desc')->paginate(10);

        return ApiResponse::success(
            'Daftar semua kontrak.',
            ContractCollection::make($contracts)
        );
    }

    /**
     * UC-04: Download File Kontrak
     * Endpoint: GET /api/contracts/{contract}/download
     * Access: Author pemilik kontrak atau Admin
     */
    public function download(Contract $contract)
    {
        $user = Auth::user();

        if ($user->role->name !== 'admin' && $contract->author->user_id !== $user->id) {
            return ApiResponse::error('Anda tidak memiliki akses untuk mengunduh kontrak ini.', 403);
        }

        // Cek apakah file ada di storage
        if (!Storage::disk('public')->exists($contract->file_path)) {
            return ApiResponse::error('File kontrak tidak ditemukan di server.', 404);
        }

        // Return file
        return response()->download(
            Storage::disk('public')->path($contract->file_path),
            $contract->original_name // Nama file asli saat didownload
        );
    }

}
