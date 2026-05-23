<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Manuscript;
use App\Models\WillingnessForm;
use App\Models\StatusLog;
use App\Http\Resources\ManuscriptResource;
use App\Http\Resources\ManuscriptCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Helpers\ApiResponse;

class ManuscriptController extends Controller
{
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
