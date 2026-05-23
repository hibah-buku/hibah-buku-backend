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
use App\Http\Resources\ManuscriptCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use App\Helpers\ApiResponse;

class DraftController extends Controller
{
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

            // 1. Cari contract yang sudah divalidasi
            $contract = null;
            if ($user->author) {
                $contract = Contract::where('author_id', $user->author->id)
                    ->where('status', 'contract_validated')
                    ->latest()
                    ->first();
            }

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

    /**
     * UC-05: Penulis Melihat Semua Naskah Miliknya
     * Endpoint: GET /api/manuscripts/me
     * Access: Penulis Only
     */
    public function myManuscripts(Request $request)
    {
        $user = Auth::user();

        $query = Manuscript::where('user_id', $user->id)
            ->with(['bookMetadata', 'latestFile', 'user']);

        // Filter by status (opsional)
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $manuscripts = $query->orderBy('created_at', 'desc')->paginate(10);

        if ($manuscripts->isEmpty()) {
            return ApiResponse::success('Anda belum memiliki naskah.', []);
        }

        return ApiResponse::success(
            'Daftar naskah Anda.',
            ManuscriptCollection::make($manuscripts)
        );
    }

    /**
     * UC-05: Penulis Melihat Detail Satu Naskah
     * Endpoint: GET /api/manuscripts/{manuscript}
     * Access: Penulis Only (pemilik naskah)
     */
    public function show(Manuscript $manuscript)
    {
        $user = Auth::user();

        if ($manuscript->user_id !== $user->id) {
            return ApiResponse::error('Anda tidak memiliki akses ke naskah ini.', 403);
        }

        $manuscript->load(['bookMetadata', 'latestFile', 'manuscriptFiles', 'user']);

        return ApiResponse::success(
            'Detail naskah Anda.',
            new ManuscriptResource($manuscript)
        );
    }

    /**
     * UC-05: Melihat Status & Riwayat Status Naskah (Stepper UI)
     * Endpoint: GET /api/manuscripts/{manuscript}/status
     * Access: Penulis Only (pemilik naskah)
     *
     * Returns stepper-friendly data untuk UI vertikal stepper
     */
    public function status(Manuscript $manuscript)
    {
        $user = Auth::user();

        if ($manuscript->user_id !== $user->id) {
            return ApiResponse::error('Anda tidak memiliki akses ke naskah ini.', 403);
        }

        // Ambil status logs terkait manuscript ini
        $statusLogs = StatusLog::where('author_id', $user->author->id)
            ->where('contract_id', $manuscript->contract_id)
            ->orderBy('triggered_at', 'asc')
            ->get();

        // Build stepper steps sesuai UI
        $steps = $this->buildStepperSteps($manuscript, $statusLogs);

        // Raw history log
        $history = $statusLogs->map(function ($log) {
            return [
                'from_status' => $log->from_status,
                'to_status' => $log->to_status,
                'triggered_by' => $log->triggered_by,
                'triggered_at' => $log->triggered_at->toISOString(),
                'notes' => $log->notes,
            ];
        });

        return ApiResponse::success('Status naskah Anda.', [
            'manuscript_id' => $manuscript->id,
            'title' => $manuscript->title,
            'book_type' => $manuscript->book_type,
            'current_status' => [
                'code' => $manuscript->status,
                'label' => $manuscript->status_label,
            ],
            'can_upload_draft' => $manuscript->canUploadDraft(),
            'deadlines' => [
                'draft' => $manuscript->deadline_draft?->toDateString(),
                'revision' => $manuscript->deadline_revision?->toDateString(),
            ],
            'steps' => $steps,
            'status_history' => $history,
        ]);
    }

    /**
     * UC-05: Download File Naskah
     * Endpoint: GET /api/manuscripts/{manuscript}/download
     * Access: Penulis (pemilik) atau Admin
     * 
     * Query param: ?file_id=X untuk download file tertentu, default = file terbaru
     */
    public function download(Request $request, Manuscript $manuscript)
    {
        $user = Auth::user();

        // Cek akses: pemilik atau admin
        if ($user->role->name !== 'admin' && $manuscript->user_id !== $user->id) {
            return ApiResponse::error('Anda tidak memiliki akses untuk mengunduh naskah ini.', 403);
        }

        // Tentukan file yang akan didownload
        if ($request->has('file_id')) {
            $file = $manuscript->manuscriptFiles()->find($request->file_id);
        } else {
            $file = $manuscript->latestFile;
        }

        if (!$file) {
            return ApiResponse::error('File naskah tidak ditemukan.', 404);
        }

        // Cek file ada di storage
        if (!Storage::disk('public')->exists($file->file_path)) {
            return ApiResponse::error('File naskah tidak ditemukan di server.', 404);
        }

        return response()->download(
            Storage::disk('public')->path($file->file_path),
            $file->original_name
        );
    }

    /**
     * UC-05: Dashboard Penulis
     * Endpoint: GET /api/manuscripts/dashboard
     * Access: Penulis Only
     *
     * Returns summary data for the author dashboard UI
     */
    public function dashboard()
    {
        $user = Auth::user();

        $manuscript = Manuscript::where('user_id', $user->id)
            ->with(['bookMetadata', 'latestFile', 'contract'])
            ->latest()
            ->first();

        if (!$manuscript) {
            return ApiResponse::success('Dashboard penulis.', [
                'has_manuscript' => false,
                'message' => 'Anda belum memiliki naskah.',
            ]);
        }

        // Get willingness form for book info
        $willingnessForm = WillingnessForm::where('main_author_email', $user->email)
            ->where('status', 'approved')
            ->latest()
            ->first();

        // Build timeline steps for dashboard
        $timelineSteps = $this->buildDashboardTimeline($manuscript);

        return ApiResponse::success('Dashboard penulis.', [
            'has_manuscript' => true,
            'manuscript' => [
                'id' => $manuscript->id,
                'title' => $manuscript->title ?? $willingnessForm?->book_title,
                'book_type' => $manuscript->book_type ?? $willingnessForm?->book_type,
                'current_status' => [
                    'code' => $manuscript->status,
                    'label' => $manuscript->status_label,
                ],
                'can_upload_draft' => $manuscript->canUploadDraft(),
                'deadlines' => [
                    'draft' => $manuscript->deadline_draft?->toDateString(),
                    'revision' => $manuscript->deadline_revision?->toDateString(),
                ],
            ],
            'timeline' => $timelineSteps,
        ]);
    }

    /**
     * Build stepper steps sesuai UI Status Naskah
     * Maps backend statuses ke steps: completed / active / pending
     */
    private function buildStepperSteps(Manuscript $manuscript, $statusLogs): array
    {
        $allSteps = [
            [
                'order' => 1,
                'key' => 'draft_uploaded',
                'title' => 'Pengumpulan Draft Awal',
                'description' => 'Naskah awal diunggah oleh penulis untuk memulai proses review.',
                'icon' => 'upload_file',
            ],
            [
                'order' => 2,
                'key' => 'under_review',
                'title' => 'Sedang Direview',
                'description' => 'Reviewer sedang mengevaluasi naskah Anda. Estimasi waktu review adalah 14 hari kerja.',
                'icon' => 'edit_note',
            ],
            [
                'order' => 3,
                'key' => 'revision_needed',
                'title' => 'Revisi Naskah',
                'description' => 'Penulis melakukan perbaikan naskah berdasarkan catatan reviewer dan mengunggah kembali naskah revisi.',
                'icon' => 'cloud_upload',
            ],
            [
                'order' => 4,
                'key' => 'approved',
                'title' => 'Naskah Disetujui',
                'description' => 'Naskah telah dinyatakan layak dan siap masuk ke tahap selanjutnya.',
                'icon' => 'verified',
            ],
            [
                'order' => 5,
                'key' => 'published',
                'title' => 'Pra-Cetak / Terbit',
                'description' => 'Naskah masuk ke tahap layouting serta persiapan pencetakan.',
                'icon' => 'print',
            ],
        ];

        // Determine current step index based on manuscript status
        $statusOrder = [
            'initial_draft_requested' => 0,
            'draft_uploaded' => 1,
            'under_review' => 2,
            'revision_needed' => 3,
            'revision_uploaded' => 2, // Goes back to review cycle
            'approved' => 4,
            'published' => 5,
        ];

        $currentIndex = $statusOrder[$manuscript->status] ?? 0;

        // Build steps with status
        return array_map(function ($step) use ($currentIndex, $statusLogs) {
            $stepIndex = $step['order'];

            if ($stepIndex < $currentIndex) {
                $step['state'] = 'completed';
                // Find completion date from logs
                $log = $statusLogs->first(fn($l) => $l->to_status === $step['key']);
                $step['completed_at'] = $log?->triggered_at?->toDateString();
            } elseif ($stepIndex === $currentIndex) {
                $step['state'] = 'active';
                $step['completed_at'] = null;
            } else {
                $step['state'] = 'pending';
                $step['completed_at'] = null;
            }

            return $step;
        }, $allSteps);
    }

    /**
     * Build timeline steps for dashboard UI
     */
    private function buildDashboardTimeline(Manuscript $manuscript): array
    {
        $statusOrder = [
            'initial_draft_requested' => 0,
            'draft_uploaded' => 1,
            'under_review' => 2,
            'revision_needed' => 3,
            'revision_uploaded' => 2,
            'approved' => 4,
            'published' => 5,
        ];

        $currentIndex = $statusOrder[$manuscript->status] ?? 0;

        $steps = [
            ['order' => 0, 'title' => 'Pendaftaran Hibah', 'description' => 'Disetujui oleh admin.'],
            ['order' => 1, 'title' => 'Pengumpulan Draft Awal', 'description' => 'Batas akhir: ' . ($manuscript->deadline_draft?->format('d M Y') ?? 'Belum ditentukan')],
            ['order' => 2, 'title' => 'Proses Review', 'description' => 'Reviewer mengevaluasi naskah.'],
            ['order' => 3, 'title' => 'Revisi Naskah', 'description' => 'Menunggu hasil review.'],
            ['order' => 4, 'title' => 'Penerbitan', 'description' => 'Naskah siap diterbitkan.'],
        ];

        return array_map(function ($step) use ($currentIndex) {
            if ($step['order'] < $currentIndex) {
                $step['state'] = 'completed';
            } elseif ($step['order'] === $currentIndex) {
                $step['state'] = 'active';
            } else {
                $step['state'] = 'pending';
            }
            return $step;
        }, $steps);
    }
}
