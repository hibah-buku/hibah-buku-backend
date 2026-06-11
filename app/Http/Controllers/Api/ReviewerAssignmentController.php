<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Mail\ReviewerAssignedMail;
use App\Models\Manuscript;
use App\Models\Reviewer;
use App\Models\ReviewerAssignment;
use App\Models\ReviewScore;
use App\Models\StatusLog;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ReviewerAssignmentController extends Controller
{
    public function __construct(protected NotificationService $notificationService)
    {
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'manuscript_id' => 'required|exists:manuscripts,id',
            'reviewer_id' => 'required|exists:reviewers,id',
            'book_title' => 'nullable|string|max:255',
            'status' => 'nullable|in:assigned,under_review,completed',
            'deadline_review' => 'nullable|date',
            'manuscript_file_url' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('Validasi gagal.', 422, $validator->errors());
        }

        $reviewer = Reviewer::find($request->reviewer_id);
        if (!$reviewer) {
            return ApiResponse::error('Reviewer tidak valid.', 422);
        }

        $manuscript = Manuscript::with(['user.author', 'latestFile'])->find($request->manuscript_id);
        if (!$manuscript) {
            return ApiResponse::error('Manuskrip tidak ditemukan.', 404);
        }

        $alreadyAssigned = ReviewerAssignment::where('manuscript_id', $manuscript->id)
            ->where('reviewer_id', $reviewer->id)
            ->exists();

        if ($alreadyAssigned) {
            return ApiResponse::error('Reviewer sudah ditugaskan pada naskah ini.', 409);
        }

        $bookTitle = $request->input('book_title') ?: ($manuscript->title ?: 'Naskah Tanpa Judul');
        $author = $manuscript->user?->author;
        $authorEmail = $manuscript->user?->email;

        $fileUrl = $request->input('manuscript_file_url');
        if (!$fileUrl && $manuscript->latestFile) {
            $fileUrl = Storage::url($manuscript->latestFile->file_path);
        }

        $assignment = ReviewerAssignment::create([
            'manuscript_id' => $manuscript->id,
            'reviewer_id' => $reviewer->id,
            'reviewer_name' => $reviewer->name,
            'reviewer_email' => $reviewer->email,
            'book_title' => $bookTitle,
            'author_id' => $author?->id,
            'author_email' => $authorEmail,
            'manuscript_file_url' => $fileUrl,
            'status' => $request->input('status', 'assigned'),
            'deadline_review' => $request->input('deadline_review'),
        ]);

        if ($manuscript->status !== Manuscript::STATUS_UNDER_REVIEW) {
            $oldStatus = $manuscript->status;
            $manuscript->update(['status' => Manuscript::STATUS_UNDER_REVIEW]);

            if ($author) {
                StatusLog::create([
                    'author_id' => $author->id,
                    'contract_id' => $manuscript->contract_id,
                    'from_status' => $oldStatus,
                    'to_status' => Manuscript::STATUS_UNDER_REVIEW,
                    'triggered_by' => 'admin:' . Auth::id(),
                    'triggered_at' => now(),
                    'notes' => 'Reviewer ditugaskan untuk naskah.',
                ]);
            }
        }

        try {
            Mail::to($reviewer->email)->send(new ReviewerAssignedMail($assignment));
        } catch (\Exception $e) {
            \Log::error('Gagal mengirim email penugasan ke ' . $reviewer->email . ': ' . $e->getMessage());
        }

        return ApiResponse::success('Assignment berhasil dibuat.', $assignment->fresh(), 201);
    }

    public function indexByReviewer(int $reviewerId)
    {
        $user = Auth::user();
        $reviewer = Reviewer::where('user_id', $user->id)->first();
        if ($user->role->name === 'reviewer' && (!$reviewer || $reviewer->id !== $reviewerId)) {
            return ApiResponse::error('Akses ditolak.', 403);
        }

        $assignments = ReviewerAssignment::with(['author.user', 'manuscript'])
            ->where('reviewer_id', $reviewerId)
            ->orderBy('created_at', 'desc')
            ->get([
                'id',
                'manuscript_id',
                'book_title',
                'status',
                'deadline_review',
                'manuscript_file_url',
                'final_score',
                'author_id',
            ])->map(function($assignment) {
                $assignment->author_name = $assignment->author?->user?->name;
                $assignment->book_type = $assignment->manuscript?->book_type;
                unset($assignment->author); // optional, to keep response clean
                unset($assignment->manuscript);
                return $assignment;
            });

        return ApiResponse::success('Daftar assignment reviewer.', $assignments);
    }

    public function show(ReviewerAssignment $assignment)
    {
        $user = Auth::user();
        $reviewer = Reviewer::where('user_id', $user->id)->first();
        if ($user->role->name === 'reviewer' && (!$reviewer || $assignment->reviewer_id !== $reviewer->id)) {
            return ApiResponse::error('Akses ditolak.', 403);
        }

        return ApiResponse::success('Detail assignment.', $assignment);
    }

    public function preview(ReviewerAssignment $assignment)
    {
        $user = Auth::user();
        $reviewer = Reviewer::where('user_id', $user->id)->first();
        if ($user->role->name === 'reviewer' && (!$reviewer || $assignment->reviewer_id !== $reviewer->id)) {
            return ApiResponse::error('Akses ditolak.', 403);
        }

        $url = $assignment->manuscript_file_url;
        if (!$url) {
            return ApiResponse::error('File naskah tidak tersedia.', 404);
        }

        if (preg_match('#^https?://#i', $url)) {
            return ApiResponse::success('OK', ['preview_type' => 'external', 'url' => $url]);
        }

        if (str_starts_with($url, '/storage/')) {
            $path = substr($url, strlen('/storage/'));
            if (Storage::disk('public')->exists($path)) {
                $filePath = Storage::disk('public')->path($path);
                return response()->file($filePath);
            }
        } elseif (Storage::disk('public')->exists($url)) {
            $filePath = Storage::disk('public')->path($url);
            return response()->file($filePath);
        }

        return ApiResponse::success('OK', ['preview_type' => 'link', 'url' => $url]);
    }

    public function submitReview(Request $request, ReviewerAssignment $assignment)
    {
        $user = Auth::user();
        $reviewer = Reviewer::where('user_id', $user->id)->first();
        if ($user->role->name === 'reviewer' && (!$reviewer || $assignment->reviewer_id !== $reviewer->id)) {
            return ApiResponse::error('Akses ditolak.', 403);
        }

        $validator = Validator::make($request->all(), [
            'scores' => 'required|array|min:1',
            'scores.*.rubric_id' => 'required|exists:review_rubrics,id',
            'scores.*.score' => 'required|integer|min:0',
            'scores.*.comment' => 'nullable|string',
            'final_score' => 'nullable|numeric',
            'rekomendasi_akhir' => 'nullable|in:Tanpa Perbaikan,Perbaikan Minor,Perbaikan Mayor',
            'general_comments' => 'nullable|string',
            'reviewer_email' => 'nullable|email',
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('Validasi gagal.', 422, $validator->errors());
        }

        DB::beginTransaction();
        try {
            foreach ($request->scores as $score) {
                ReviewScore::updateOrCreate(
                    [
                        'assignment_id' => $assignment->id,
                        'rubric_id' => $score['rubric_id'],
                    ],
                    [
                        'score' => $score['score'],
                        'comment' => $score['comment'] ?? null,
                    ]
                );
            }

            $assignment->update([
                'final_score' => $request->input('final_score'),
                'status' => 'completed',
                'rekomendasi_akhir' => $request->input('rekomendasi_akhir'),
                'general_comments' => $request->input('general_comments'),
                'reviewer_email' => $request->input('reviewer_email') ?: $assignment->reviewer_email,
                'submitted_at' => now(),
            ]);

            DB::commit();

            $this->notifyReviewCompletion($assignment);

            return ApiResponse::success('Review berhasil dikirim.', ['assignment_id' => $assignment->id]);
        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponse::error('Internal Server Error', 500);
        }
    }

    public function results(ReviewerAssignment $assignment)
    {
        $scores = ReviewScore::with('rubric')
            ->where('assignment_id', $assignment->id)
            ->get();

        if ($scores->isEmpty()) {
            return ApiResponse::success('Belum ada nilai.', []);
        }

        $total = 0;
        $maxTotal = 0;
        $items = [];
        foreach ($scores as $score) {
            $items[] = [
                'id' => $score->id,
                'assignment_id' => $score->assignment_id,
                'rubric_id' => $score->rubric_id,
                'score' => $score->score,
                'comment' => $score->comment,
                'criteria_name' => $score->rubric?->criteria_name,
                'max_score' => $score->rubric?->max_score,
            ];
            $total += $score->score;
            $maxTotal += $score->rubric?->max_score ?? 0;
        }

        $percent = $maxTotal > 0 ? round($total / $maxTotal * 100, 2) : null;

        return ApiResponse::success('OK', [
            'scores' => $items,
            'total' => $total,
            'max_total' => $maxTotal,
            'percent' => $percent,
        ]);
    }

    private function notifyReviewCompletion(ReviewerAssignment $assignment): void
    {
        // Pastikan relasi ter-load
        $assignment->loadMissing(['author.user', 'manuscript.user']);

        $authorEmail = $assignment->author_email
            ?: $assignment->manuscript?->user?->email;
        $authorName = $assignment->author?->user?->name
            ?: $assignment->manuscript?->user?->name
            ?: 'Penulis';
        $bookTitle = $assignment->book_title
            ?: ($assignment->manuscript?->title ?? 'Naskah Tanpa Judul');
        $deadlineRevision = $assignment->deadline_review
            ? \Carbon\Carbon::parse($assignment->deadline_review)->locale('id')->isoFormat('D MMMM Y')
            : '-';
        $reviewUrl = url('/api/assignments/' . $assignment->id);

        if ($authorEmail) {
            try {
                $this->notificationService->sendReviewCompleted(
                    $authorEmail,
                    $authorName,
                    $bookTitle,
                    $deadlineRevision,
                    $reviewUrl,
                );
            } catch (\Throwable $e) {
                \Log::error('Gagal mengirim email review completed ke penulis: ' . $e->getMessage());
            }
        }

        $publishers = User::with('role')
            ->whereHas('role', function ($query) {
                $query->where('name', 'penerbit');
            })
            ->whereNotNull('email')
            ->get();

        foreach ($publishers as $publisher) {
            try {
                $subject = 'Review naskah selesai: ' . $bookTitle;
                $body = "Review untuk naskah \"{$bookTitle}\" telah selesai oleh reviewer.\n"
                    . "Silakan login ke sistem untuk melihat hasil review dan langkah berikutnya.\n"
                    . "URL: {$reviewUrl}";

                Mail::raw($body, function ($message) use ($publisher, $subject) {
                    $message->to($publisher->email, $publisher->name)->subject($subject);
                });
            } catch (\Throwable $e) {
                \Log::error('Gagal mengirim email review completed ke publisher ' . ($publisher->email ?? '-') . ': ' . $e->getMessage());
            }
        }
    }

    public function notify(Request $request, ReviewerAssignment $assignment)
    {
        $validator = Validator::make($request->all(), [
            'to_email' => 'nullable|email',
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('Validasi gagal.', 422, $validator->errors());
        }

        $to = $request->input('to_email') ?: $assignment->reviewer_email;
        if (!$to) {
            return ApiResponse::error('Email reviewer belum tersedia.', 422);
        }

        $subject = 'Tugas Review: ' . ($assignment->book_title ?? 'Naskah baru');
        $body = "Anda mendapatkan tugas review.\nAssignment ID: {$assignment->id}\nJudul: {$assignment->book_title}\nSilakan login untuk melihat detail.";

        $sent = false;
        try {
            Mail::raw($body, function ($message) use ($to, $subject) {
                $message->to($to)->subject($subject);
            });
            $sent = true;
        } catch (\Throwable $e) {
            $sent = false;
        }

        $logLine = now()->toIso8601String() . " - Email to={$to} subject=" . json_encode($subject) . " sent=" . ($sent ? '1' : '0') . "\n";
        file_put_contents(storage_path('logs/notifications.log'), $logLine, FILE_APPEND);

        if (!$sent) {
            $outboxLine = now()->toIso8601String() . " | to={$to} | subject=" . json_encode($subject) . " | body=" . json_encode($body) . "\n";
            file_put_contents(storage_path('logs/notification_outbox.log'), $outboxLine, FILE_APPEND);
        }

        return ApiResponse::success($sent ? 'Notification sent' : 'Notification queued locally', [
            'sent' => (bool) $sent,
            'transport' => $sent ? 'mail' : 'local_outbox',
        ]);
    }
}
