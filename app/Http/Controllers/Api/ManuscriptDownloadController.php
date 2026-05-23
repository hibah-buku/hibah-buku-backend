<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Manuscript;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Helpers\ApiResponse;

class ManuscriptDownloadController extends Controller
{
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
}
