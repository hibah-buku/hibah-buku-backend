<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Manuscript;
use App\Models\ManuscriptFile;
use App\Models\BookMetadata;
use App\Models\Contract;
use App\Models\WillingnessForm;
use App\Models\StatusLog;
use App\Http\Resources\ManuscriptResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Helpers\ApiResponse;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Log;

class DraftUploadController extends Controller
{
    public function __construct(protected NotificationService $notificationService)
    {
    }
    /**
     * UC-05: Penulis Upload Naskah Awal (Draft)
     * Endpoint: POST /api/manuscripts/upload-draft
     * Access: Penulis Only
     * 
     * Penulis bisa langsung upload draft tanpa menunggu row dari admin.
     * Manuscript row dibuat otomatis saat pertama kali upload.
     */
    public function uploadDraft(Request $request)
    {
        $user = Auth::user();

        // Cek apakah penulis sudah memiliki kontrak yang divalidasi oleh admin
        $contract = null;
        if ($user->author) {
            $contract = Contract::where('author_id', $user->author->id)
                ->where('status', 'contract_validated')
                ->latest()
                ->first();
        }

        if (!$contract) {
            return ApiResponse::error(
                'Anda tidak dapat mengunggah draft naskah sebelum kontrak Anda disetujui/divalidasi oleh admin.',
                403
            );
        }

        // Cek apakah penulis sudah punya manuscript aktif
        $existingManuscript = Manuscript::where('user_id', $user->id)
            ->whereNotIn('status', [Manuscript::STATUS_PUBLISHED])
            ->first();

        if ($existingManuscript && !$existingManuscript->canUploadDraft()) {
            return ApiResponse::error(
                'Anda sudah memiliki naskah aktif. Status saat ini: ' . $existingManuscript->status_label,
                422
            );
        }

        // Validasi input
        $validator = Validator::make($request->all(), [
            'manuscript_file' => 'required|file|mimes:pdf,doc,docx|max:20480',
            'title' => 'required|string|max:255',
            'abstract' => 'required|string',
            'page_count' => 'nullable|integer|min:1',
            'category' => 'required|string|in:saintek,soshum',
            'field_of_study' => 'nullable|string|max:255',
            'institution' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('Validasi gagal.', 422, $validator->errors());
        }

        try {
            DB::beginTransaction();

            // 2. Ambil book_type dari willingness_forms (data Kelompok 1)
            $willingnessForm = WillingnessForm::where('main_author_email', $user->email)
                ->where('status', 'approved')
                ->latest()
                ->first();

            // 3. Buat atau update manuscript
            $oldStatus = 'initial_draft_requested';
            if ($existingManuscript) {
                $manuscript = $existingManuscript;
                $oldStatus = $manuscript->status;
                $manuscript->update([
                    'title' => $request->title,
                    'book_type' => $willingnessForm?->book_type,
                    'status' => Manuscript::STATUS_DRAFT_UPLOADED,
                ]);
            } else {
                $manuscript = Manuscript::create([
                    'user_id' => $user->id,
                    'contract_id' => $contract?->id,
                    'title' => $request->title,
                    'book_type' => $willingnessForm?->book_type,
                    'status' => Manuscript::STATUS_DRAFT_UPLOADED,
                    'deadline_draft' => $contract?->draft_deadline ?? now()->addDays(14),
                ]);
            }

            // 4. Upload file naskah
            $file = $request->file('manuscript_file');
            $originalName = $file->getClientOriginalName();
            $fileName = time() . '_' . $user->id . '_' . $originalName;
            $path = $file->storeAs('manuscripts', $fileName, 'public');

            ManuscriptFile::create([
                'manuscript_id' => $manuscript->id,
                'file_type' => 'draft_awal',
                'file_path' => $path,
                'original_name' => $originalName,
                'file_size_kb' => round($file->getSize() / 1024),
                'mime_type' => $file->getClientMimeType(),
            ]);

            // 5. Simpan book metadata
            $metadataData = [];
            foreach (['abstract', 'page_count', 'category', 'field_of_study', 'institution'] as $field) {
                if ($request->filled($field)) {
                    $metadataData[$field] = $request->input($field);
                }
            }
            if (!empty($metadataData)) {
                BookMetadata::updateOrCreate(
                    ['manuscript_id' => $manuscript->id],
                    $metadataData
                );
            }

            // 6. Catat status log
            if ($user->author) {
                StatusLog::create([
                    'author_id' => $user->author->id,
                    'contract_id' => $manuscript->contract_id,
                    'from_status' => $oldStatus,
                    'to_status' => Manuscript::STATUS_DRAFT_UPLOADED,
                    'triggered_by' => 'penulis:' . $user->id,
                    'triggered_at' => now(),
                    'notes' => "Naskah awal diunggah: {$originalName}",
                ]);
            }

            DB::commit();

            // Kirim notifikasi email ke Admin untuk plotting reviewer
            try {
                $reviewUrl = 'http://localhost:5173/admin/dashboard';
                $this->notificationService->sendNewDraftUploadToAdmins(
                    manuscriptId: $manuscript->id,
                    authorName: $user->name,
                    bookTitle: $manuscript->title,
                    bookType: $manuscript->book_type,
                    uploadedAt: now()->format('Y-m-d H:i:s'),
                    reviewUrl: $reviewUrl
                );
            } catch (\Throwable $e) {
                Log::error('Failed to send admin notification for new draft upload', [
                    'manuscript_id' => $manuscript->id,
                    'error' => $e->getMessage()
                ]);
            }

            $manuscript->load(['bookMetadata', 'latestFile', 'manuscriptFiles', 'user']);

            return ApiResponse::success(
                'Naskah awal berhasil diunggah. Menunggu proses review.',
                new ManuscriptResource($manuscript),
                201
            );

        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponse::error('Gagal mengunggah naskah: ' . $e->getMessage(), 500);
        }
    }

    /**
     * UC-05: Penulis Upload Revisi Naskah
     * Endpoint: POST /api/manuscripts/{manuscript}/upload-revision
     * Access: Penulis Only (pemilik naskah, status harus: revision_needed)
     */
    public function uploadRevision(Request $request, Manuscript $manuscript)
    {
        $user = Auth::user();

        if ($manuscript->user_id !== $user->id) {
            return ApiResponse::error('Anda tidak memiliki akses ke naskah ini.', 403);
        }

        if ($manuscript->status !== Manuscript::STATUS_REVISION_NEEDED) {
            return ApiResponse::error(
                'Naskah tidak dalam status revisi. Status saat ini: ' . $manuscript->status_label,
                422
            );
        }

        $validator = Validator::make($request->all(), [
            'manuscript_file' => 'required|file|mimes:pdf,doc,docx|max:20480',
            'title' => 'nullable|string|max:255',
            'abstract' => 'nullable|string',
            'page_count' => 'nullable|integer|min:1',
            'category' => 'nullable|string|in:saintek,soshum',
            'field_of_study' => 'nullable|string|max:255',
            'institution' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('Validasi gagal.', 422, $validator->errors());
        }

        try {
            DB::beginTransaction();

            $file = $request->file('manuscript_file');
            $originalName = $file->getClientOriginalName();
            $fileName = time() . '_' . $user->id . '_' . $originalName;
            $path = $file->storeAs('manuscripts', $fileName, 'public');

            $revisionCount = $manuscript->manuscriptFiles()
                ->where('file_type', 'like', 'revisi_%')
                ->count();

            ManuscriptFile::create([
                'manuscript_id' => $manuscript->id,
                'file_type' => 'revisi_' . ($revisionCount + 1),
                'file_path' => $path,
                'original_name' => $originalName,
                'file_size_kb' => round($file->getSize() / 1024),
                'mime_type' => $file->getClientMimeType(),
            ]);

            $oldStatus = $manuscript->status;
            $updateData = ['status' => Manuscript::STATUS_REVISION_UPLOADED];
            if ($request->filled('title')) {
                $updateData['title'] = $request->title;
            }
            $manuscript->update($updateData);

            // Update metadata jika ada
            $metadataData = [];
            foreach (['abstract', 'page_count', 'category', 'field_of_study', 'institution'] as $field) {
                if ($request->filled($field)) {
                    $metadataData[$field] = $request->input($field);
                }
            }
            if (!empty($metadataData)) {
                BookMetadata::updateOrCreate(
                    ['manuscript_id' => $manuscript->id],
                    $metadataData
                );
            }

            if ($user->author) {
                StatusLog::create([
                    'author_id' => $user->author->id,
                    'contract_id' => $manuscript->contract_id,
                    'from_status' => $oldStatus,
                    'to_status' => Manuscript::STATUS_REVISION_UPLOADED,
                    'triggered_by' => 'penulis:' . $user->id,
                    'triggered_at' => now(),
                    'notes' => "Revisi naskah diunggah: {$originalName}",
                ]);
            }

            DB::commit();

            $manuscript->load(['bookMetadata', 'latestFile', 'manuscriptFiles', 'user']);

            return ApiResponse::success(
                'Revisi naskah berhasil diunggah. Menunggu review ulang.',
                new ManuscriptResource($manuscript)
            );

        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponse::error('Gagal mengunggah revisi: ' . $e->getMessage(), 500);
        }
    }
}
