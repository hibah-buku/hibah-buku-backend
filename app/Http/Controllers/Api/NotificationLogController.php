<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NotificationLog;
use App\Models\NotificationTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationLogController extends Controller
{
    /**
     * GET /api/notification-logs
     * List logs with filters. Admin only.
     */
    public function index(Request $request): JsonResponse
    {
        $query = NotificationLog::with('template')
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('template_code')) {
            $query->byTemplate($request->template_code);
        }
        if ($request->filled('email')) {
            $query->where('recipient_email', 'like', '%' . $request->email . '%');
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $logs = $query->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'message' => 'Notification logs retrieved',
            'data'    => $logs,
        ]);
    }

    /**
     * GET /api/notification-logs/{id}
     */
    public function show(int $id): JsonResponse
    {
        $log = NotificationLog::with('template')->findOrFail($id);

        return response()->json([
            'success' => true,
            'message' => 'Notification log detail',
            'data'    => $log,
        ]);
    }

    /**
     * GET /api/notification-logs/summary
     * Stats for dashboard.
     */
    public function summary(): JsonResponse
    {
        $summary = [
            'total'   => NotificationLog::count(),
            'sent'    => NotificationLog::sent()->count(),
            'failed'  => NotificationLog::failed()->count(),
            'pending' => NotificationLog::where('status', 'pending')->count(),
            'by_template' => NotificationLog::selectRaw('template_code, status, count(*) as total')
                ->groupBy('template_code', 'status')
                ->get()
                ->groupBy('template_code'),
        ];

        return response()->json([
            'success' => true,
            'message' => 'Notification summary',
            'data'    => $summary,
        ]);
    }

    /**
     * DELETE /api/notification-logs/{id}
     * Admin only.
     */
    public function destroy(int $id): JsonResponse
    {
        NotificationLog::findOrFail($id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Notification log deleted',
            'data'    => null,
        ]);
    }
}
