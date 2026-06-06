<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Manuscript; 
use App\Models\PublisherDecision; 
use App\Models\NotificationLog;
use App\Models\Deadline;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Validator;

class PublisherController extends Controller
{
    public function __construct(protected NotificationService $notificationService)
    {
    }

    /**
     * GET /api/publisher/dashboard
     * Ringkasan naskah pra-cetak, revised, approved
     */
    public function dashboard(Request $request)
    {
        try {
            $praCetak = Manuscript::where('status', 'preprint')->count();
            $revisionRequests = Manuscript::where('status', 'publisher_revised')->count();
            $approved = Manuscript::where('status', 'to_print')->count();

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

        $links = [
                [
                    'rel' => 'self',
                    'href' => url('/api/publisher/dashboard'),
                    'method' => 'GET',
                    'description' => 'Memuat ulang data dashboard'
                ],
                [
                    'rel' => 'manuscripts_list',
                    'href' => url('/api/publisher/manuscripts'),
                    'method' => 'GET',
                    'description' => 'Melihat daftar naskah yang siap diperiksa di tahap pra-cetak'
                ]
            ];

            return response()->json([
                'status' => 'success',
                'message' => 'Dashboard penerbit ditampilkan.',
                'data' => [
                    'pending_checks' => $praCetak,
                    'revision_requests' => $revisionRequests,
                    'approved_manuscripts' => $approved,
                    'recent_notifications' => $recentNotifications,
                ],
                '_links' => $links
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * GET /api/publisher/manuscripts
     * Daftar naskah yang masuk tahap pra-cetak
     */
    public function index(Request $request)
    {
        try {
            $paginator = Manuscript::with('author')
                ->orderBy('updated_at', 'desc')
                ->paginate(10);

            $formattedItems = collect($paginator->items())->map(function ($manuscript) {
                return [
                    'id' => $manuscript->id,
                    'title' => $manuscript->title,
                    
                    'author' => [
                        'id' => $manuscript->author?->id,
                        'name' => $manuscript->author?->name ?? 'N/A',
                        'email' => $manuscript->author?->email ?? 'N/A'
                    ],
                    
                    'status' => $manuscript->status,                    
                    'updated_at' => $manuscript->updated_at ? $manuscript->updated_at->toDateTimeString() : null,
                    
                    '_links' => [
                        [
                            'rel' => 'details',
                            'href' => url("/api/publisher/manuscripts/{$manuscript->id}"),
                            'method' => 'GET',
                            'description' => 'Melihat detail dokumen administrasi dan melakukan checklist'
                        ],
                        [
                            'rel' => 'submit_decision',
                            'href' => url("/api/publisher/manuscripts/{$manuscript->id}/decision"),
                            'method' => 'POST',
                            'description' => 'Mengirim keputusan approved atau revised untuk naskah ini'
                        ]
                    ]
                ];
            })->toArray();

            // navigasi antar halaman (Pagination Links)
            $paginationLinks = [
                [
                    'rel' => 'self',
                    'href' => $paginator->url($paginator->currentPage()),
                    'method' => 'GET'
                ]
            ];

            if ($paginator->hasMorePages()) {
                $paginationLinks[] = [
                    'rel' => 'next',
                    'href' => $paginator->nextPageUrl(),
                    'method' => 'GET'
                ];
            }

            if (!$paginator->onFirstPage()) {
                $paginationLinks[] = [
                    'rel' => 'prev',
                    'href' => $paginator->previousPageUrl(),
                    'method' => 'GET'
                ];
            }

            $paginationLinks[] = [
                'rel' => 'first',
                'href' => $paginator->url(1),
                'method' => 'GET'
            ];

            $paginationLinks[] = [
                'rel' => 'last',
                'href' => $paginator->url($paginator->lastPage()),
                'method' => 'GET'
            ];

            $paginationLinks[] = [
                'rel' => 'dashboard',
                'href' => url('/api/publisher/dashboard'),
                'method' => 'GET'
            ];

            return response()->json([
                'status' => 'success',
                'message' => 'Daftar naskah pra-cetak berhasil dimuat.',
                'data' => [
                    'items' => $formattedItems,
                    'meta' => [
                        'current_page' => $paginator->currentPage(),
                        'last_page' => $paginator->lastPage(),
                        'per_page' => $paginator->perPage(),
                        'total' => $paginator->total(),
                    ]
                ],
                '_links' => $paginationLinks
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * GET /api/publisher/manuscripts/:id
     * Detail naskah dan dokumen administrasi
     */
    public function show(string $id)
    {
        try {
            $manuscript = Manuscript::with(['author', 'publisherChecks'])->find($id);

            if (!$manuscript) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Naskah tidak ditemukan.',
                    'data' => null,
                    '_links' => [
                            [
                                'rel' => 'list',
                                'href' => url('/api/publisher/manuscripts'),
                                'method' => 'GET'
                            ],
                            [
                                'rel' => 'dashboard',
                                'href' => url('/api/publisher/dashboard'),
                                'method' => 'GET'
                            ]
                        ]
                ], 404);
            }
            $links = [
                [
                    'rel' => 'self',
                    'href' => url("/api/publisher/manuscripts/{$id}"),
                    'method' => 'GET'
                ],
                [
                    'rel' => 'list',
                    'href' => url('/api/publisher/manuscripts'),
                    'method' => 'GET'
                ]
            ];

            if ($manuscript->status === 'preprint') {
                $links[] = [
                    'rel' => 'submit_decision',
                    'href' => url("/api/publisher/manuscripts/{$id}/decision"),
                    'method' => 'POST',
                    'description' => 'Mengirim keputusan approved (siap cetak) atau revised (perlu revisi)'
                ];
            } else {
                $links[] = [
                    'rel' => 'status_history',
                    'href' => url("/api/publisher/dashboard"), 
                    'method' => 'GET',
                    'description' => 'Naskah sudah diproses. Status saat ini: ' . $manuscript->status
                ];
            }

        return response()->json([
            'status' => 'success',
            'message' => 'Detail naskah berhasil diambil.',
            'data' => $manuscript,
            '_links' => $links
        ], 200);

    } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * POST /api/publisher/manuscripts/:id/decision
     * Submit keputusan approved atau revised
     */
    public function storeDecision(Request $request, string $id)
    {
        try {
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
                'errors' => $validator->errors(),
                '_links' => [
                    [
                        'rel' => 'self',
                        'href' => url("/api/publisher/manuscripts/{$id}"),
                        'method' => 'GET',
                        'description' => 'Kembali melihat detail naskah sebelum mencoba mengirim keputusan lagi'
                    ]
                ]
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
            $manuscript->status = 'to_print'; 
            $eventName = 'PublisherApproved';
        } else {
            $manuscript->status = 'publisher_revised'; 
            $eventName = 'PublisherRevised';
        }
        $manuscript->save();

        $authorEmail = $manuscript->author->email ?? null;
        $authorName = $manuscript->author->name ?? 'Penulis';
        $actionUrl = url("/api/manuscripts/{$manuscript->id}");

        if ($authorEmail) {
            $this->notificationService->sendPublisherDecision(
                $authorEmail,
                $authorName,
                $manuscript->title,
                $validated['decision'],
                $validated['revision_notes'] ?? '',
                $actionUrl,
            );
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Keputusan penerbit berhasil disimpan',
            'data' => $decision
        ], 201);
    
    } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan internal: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }
}