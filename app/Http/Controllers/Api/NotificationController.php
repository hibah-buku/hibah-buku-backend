<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\NotificationLog;

class NotificationController extends Controller
{
    /**
     * GET /api/notifications/logs
     * Log seluruh email yang telah terkirim
     */
    public function indexLogs(Request $request)
    {
        $query = NotificationLog::with('recipient');

        // Optional filter status (sent/failed)
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $logs = $query->orderBy('created_at', 'desc')->paginate(15);

        return response()->json([
            'status' => 'success',
            'message' => 'Daftar log notifikasi berhasil diambil.',
            'data' => $logs
        ], 200);
    }

    /**
     * GET /api/notifications/logs/:id
     * Detail log notifikasi tertentu
     */
    public function showLog(string $id)
    {
        $log = NotificationLog::with(['recipient', 'template'])->find($id);

        if (!$log) {
            return response()->json([
                'status' => 'error',
                'message' => 'Log notifikasi tidak ditemukan.',
                'data' => null
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Detail log notifikasi berhasil diambil.',
            'data' => $log
        ], 200);
    }
}