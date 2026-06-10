<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\WillingnessForm;
use App\Models\Author;
use App\Http\Resources\ContractCollection;
use App\Http\Resources\ContractResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Helpers\ApiResponse;
use Illuminate\Support\Facades\Storage;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;

class ContractController extends Controller
{
    public function __construct(protected NotificationService $notificationService)
    {
    }

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

            try {
                $reviewUrl = URL::to('api/admin/contracts');

                $willingnessForm = WillingnessForm::where('main_author_email', $contract->author->user->email)
                    ->where('status', 'approved')
                    ->orderBy('created_at', 'desc')
                    ->first();

                $bookTitle = $willingnessForm ? $willingnessForm->book_title : 'N/A';
                $this->notificationService->sendNewContractUploadToAdmins(
                    contractId: $contract->id,
                    authorName: $contract->author->user->name,
                    bookTitle: $bookTitle,
                    fileName: $contract->original_name,
                    uploadedAt: $contract->created_at->format('Y-m-d H:i:s'),
                    reviewUrl: $reviewUrl
                );
            } catch (\Throwable $e) {
                Log::error('Failed to send admin notification for new contract', [
                    'contract_id' => $contract->id,
                    'error' => $e->getMessage()
                ]);
            }

            return ApiResponse::success('Kontrak berhasil diunggah dan email terkirim. Menunggu untuk validasi', new ContractResource($contract), 201);
        } catch (\Exception $e) {
            return ApiResponse::error('Gagal menggunggah file: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Penulis Melihat Kontrak Miliknya
     * Endpoint: GET /api/contracts/me
     * Access: Penulis only
     */

    public function myContract(Request $request, Contract $contract)
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
     * Admin Validasi Kontrak
     * Endpoint: PATCH /api/contracts/{contract}/validate
     * Access: Admin only
     */

    public function validateContract(Request $request, Contract $contract)
    {
        $user = Auth::user();

        // Set deadline draft upload 1 minggu dari sekarang
        $draftDeadline = now()->addWeek();

        $contract->update([
            'status' => 'contract_validated',
            'notes' => $request->input('notes', 'Kontrak divalidasi oleh admin'),
            'validated_by' => $user->id,
            'validated_at' => now(),
            'draft_deadline' => $draftDeadline,
        ]);

        // Kirim email notifikasi ke penulis
        try {
            if ($contract->author && $contract->author->user) {
                $authorEmail = $contract->author->user->email;
                $authorName = $contract->author->user->name;
                $uploadUrl = url('/api/manuscripts/upload-draft');

                $this->notificationService->sendContractValidated(
                    $authorEmail,
                    $authorName,
                    $draftDeadline->format('d F Y H:i'),
                    $uploadUrl
                );
            }
        } catch (\Throwable $e) {
            Log::error('Failed to send contract rejection email to author', [
                'contract_id' => $contract->id,
                'error' => $e->getMessage()
            ]);
        }

        return ApiResponse::success(
            'Kontrak telah divalidasi dan email dikirim. penulis dapat melanjutkan ke upload naskah.',
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

            try {
                $resubmitUrl = URL::to('api/author/upload-kontrak');
                $willingnessForm = WillingnessForm::where('main_author_email', $contract->author->user->email)
                    ->where('status', 'approved')
                    ->orderBy('created_at', 'desc')
                    ->first();

                $bookTitle = $willingnessForm ? $willingnessForm->book_title : 'N/A';

                $this->notificationService->sendContractRejected(
                    email: $contract->author->user->email,
                    authorName: $contract->author->user->name,
                    bookTitle: $bookTitle,
                    rejectionReason: $request->rejection_reason,
                    resubmitUrl: $resubmitUrl
                );
            } catch (\Throwable $e) {
                Log::error('Failed to send contract rejection email to author', [
                    'contract_id' => $contract->id,
                    'error' => $e->getMessage()
                ]);
            }

            return ApiResponse::success(
                'Kontrak ditolak dan email notifikasi terkirim..',
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

        $validator = Validator::make($request->all(), [
            'status' => 'nullable|in:contract_uploaded,contract_validated,contract_rejected',
        ]);

        if ($validator->fails()) {
            return ApiResponse::error(
                'Parameter status tidak valid. Gunakan salah satu dari: contract_generated, contract_uploaded, contract_validated, contract_rejected.',
                422,
                $validator->errors()
            );
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $contracts = $query->orderBy('created_at', 'desc')->paginate(10);

        return ApiResponse::success(
            'Daftar semua kontrak.',
            ContractCollection::make($contracts)
        );
    }

    public function show(Contract $contract)
    {
        $user = Auth::user();

        if ($user->role->name !== 'admin' && $contract->author->user_id !== $user->id) {
            return ApiResponse::error('Akses ditolak.', 403);
        }

        return ApiResponse::success(
            'Detail kontrak.',
            new ContractResource($contract)
        );
    }

    /**
     * Preview file PDF
     * Endpoint: GET /api/contracts/{contract}/download
     * Access: Author pemilik kontrak atau Admin
     */
    public function previewPdf(Contract $contract)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if ($user->role->name !== 'admin' && $contract->author->user_id !== $user->id) {
            abort(403, 'Akses ditolak.');
        }

        if (!Storage::disk('public')->exists($contract->file_path)) {
            abort(404, 'File tidak ditemukan.');
        }

        $path = Storage::disk('public')->path($contract->file_path);

        return response()->file($path, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $contract->original_name . '"'
        ]);
    }

    /**
     * Download File Kontrak
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
