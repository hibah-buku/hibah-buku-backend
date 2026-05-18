<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Manuscript; 
use App\Models\PublisherDecision; 
use App\Models\NotificationLog;
use App\Models\NotificationTemplate;
use App\Models\Deadline;
use Illuminate\Support\Facades\Validator;

class PublisherController extends Controller
{
    /**
     * GET /api/publisher/dashboard
     * Ringkasan naskah pra-cetak, revised, approved
     */
    public function dashboard(Request $request)
    {
        $totalSubmissions = Manuscript::count();
        $praCetak = Manuscript::where('status', 'preprint')->count();
        $revisionRequests = Manuscript::where('status', 'publisher_revised')->count();
        $approved = Manuscript::where('status', 'ready_to_print')->count();
        $openDeadlines = Deadline::where('is_completed', false)->count();

        $recentNotifications = NotificationLog::latest('sent_at')
            ->take(3)
            ->get(['event_name', 'recipient_email', 'status', 'sent_at'])
            ->map(function ($notification) {
                return [
                    'event_name' => $notification->event_name,
                    'recipient_email' => $notification->recipient_email,
                    'status' => $notification->status,
                    'sent_at' => $notification->sent_at ? $notification->sent_at->toDateTimeString() : null,
                ];
            });

        return response()->json([
            'status' => 'success',
            'message' => 'Data dashboard penerbit berhasil diambil.',
            'data' => [
                'total_submissions' => $totalSubmissions,
                'pending_checks' => $praCetak,
                'revision_requests' => $revisionRequests,
                'approved_manuscripts' => $approved,
                'open_deadlines' => $openDeadlines,
                'recent_notifications' => $recentNotifications,
            ]
        ], 200);
    }

    /**
     * GET /api/publisher/manuscripts
     * Daftar naskah yang masuk tahap pra-cetak
     */
    public function index(Request $request)
    {
        $manuscripts = Manuscript::with('author')
            ->where('status', 'preprint')
            ->orderBy('updated_at', 'desc')
            ->paginate(10);

        return response()->json([
            'status' => 'success',
            'message' => 'Daftar naskah pra-cetak.',
            'data' => $manuscripts
        ], 200);
    }

    /**
     * GET /api/publisher/manuscripts/:id
     * Detail naskah dan dokumen administrasi
     */
    public function show(string $id)
    {
        $manuscript = Manuscript::with(['author', 'publisherChecks'])->find($id);

        if (!$manuscript) {
            return response()->json([
                'status' => 'error',
                'message' => 'Naskah tidak ditemukan.',
                'data' => null
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Detail naskah berhasil diambil.',
            'data' => $manuscript
        ], 200);
    }

    /**
     * POST /api/publisher/manuscripts/:id/decision
     * Submit keputusan approved atau revised
     */
    public function storeDecision(Request $request, string $id)
    {
        $manuscript = Manuscript::with('author')->find($id);

        if (!$manuscript) {
            return response()->json([
                'status' => 'error',
                'message' => 'Naskah tidak ditemukan.',
                'data' => null
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'decision' => 'required|string|in:approved,revised',
            'revision_notes' => 'required_if:decision,revised|string|nullable',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal.',
                'errors' => $validator->errors()
            ], 422);
        }

        $validated = $validator->validated();

        $decision = PublisherDecision::create([
            'manuscript_id' => $manuscript->id,
            'publisher_id' => auth()->id(), 
            'decision' => $validated['decision'],
            'revision_notes' => $validated['revision_notes'] ?? null,
            'decided_at' => now(),
        ]);

        $eventName = '';
        if ($validated['decision'] === 'approved') {
            $manuscript->status = 'ready_to_print'; 
            $eventName = 'PublisherApproved';
        } else {
            $manuscript->status = 'publisher_revised'; 
            $eventName = 'PublisherRevised';
        }
        $manuscript->save();

        $template = NotificationTemplate::where('event_name', $eventName)->first();

        NotificationLog::create([
            'template_id' => $template ? $template->id : 1,
            'recipient_id' => $manuscript->author_id,
            'recipient_email' => $manuscript->author->email ?? 'author@dummy.com',
            'event_name' => $eventName,
            'payload' => json_encode([
                'manuscript_id' => $manuscript->id, 
                'notes' => $validated['revision_notes'] ?? null
            ]),
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Keputusan penerbit berhasil disimpan',
            'data' => $decision
        ], 201);
    }
}