<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Manuscript;
use App\Models\AuthorDocument;
use App\Http\Resources\AuthorDocumentResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use App\Helpers\ApiResponse;

class AuthorDocumentController extends Controller
{
    /**
     * UC-05: Penulis Melihat Daftar Dokumen Administrasi Naskah
     * Endpoint: GET /api/manuscripts/me/documents
     * Access: Penulis Only
     */
    public function index()
    {
        $user = Auth::user();

        $manuscript = Manuscript::where('user_id', $user->id)
            ->whereNotIn('status', [Manuscript::STATUS_PUBLISHED])
            ->latest()
            ->first();

        if (!$manuscript) {
            return ApiResponse::error('Anda belum memiliki naskah aktif.', 404);
        }

        $documents = $manuscript->authorDocuments;

        return ApiResponse::success(
            'Daftar dokumen administrasi naskah Anda.',
            AuthorDocumentResource::collection($documents)
        );
    }

    /**
     * UC-05: Penulis Unggah / Timpa Dokumen Administrasi
     * Endpoint: POST /api/manuscripts/me/documents
     * Access: Penulis Only
     */
    public function upload(Request $request)
    {
        $user = Auth::user();

        $manuscript = Manuscript::where('user_id', $user->id)
            ->whereNotIn('status', [Manuscript::STATUS_PUBLISHED])
            ->latest()
            ->first();

        if (!$manuscript) {
            return ApiResponse::error('Anda belum memiliki naskah aktif untuk mengunggah dokumen administrasi.', 404);
        }

        $validator = Validator::make($request->all(), [
            'document_type' => 'required|string|in:surat_pernyataan,scan_bermeterai,dokumen_pendukung',
            'document_file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120', // Max 5MB
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('Validasi gagal.', 422, $validator->errors());
        }

        try {
            DB::beginTransaction();

            $documentType = $request->document_type;
            $file = $request->file('document_file');

            // Cek apakah dokumen jenis ini sudah pernah diunggah sebelumnya
            $existingDocument = AuthorDocument::where('manuscript_id', $manuscript->id)
                ->where('document_type', $documentType)
                ->first();

            // Jika dokumen sudah ada dan terverifikasi, larang pengunggahan ulang
            if ($existingDocument && $existingDocument->is_verified) {
                return ApiResponse::error('Dokumen ini telah diverifikasi oleh Admin dan tidak dapat diunggah ulang.', 422);
            }

            // Hapus file lama jika ada
            if ($existingDocument && Storage::disk('public')->exists($existingDocument->file_path)) {
                Storage::disk('public')->delete($existingDocument->file_path);
            }

            // Simpan file baru ke public storage
            $fileName = time() . '_' . $user->id . '_' . $documentType . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('author_documents', $fileName, 'public');

            // Simpan atau update record ke database
            $document = AuthorDocument::updateOrCreate(
                [
                    'manuscript_id' => $manuscript->id,
                    'document_type' => $documentType,
                ],
                [
                    'file_path' => $path,
                    'file_size_kb' => round($file->getSize() / 1024),
                    'is_verified' => false,
                    'uploaded_at' => now(),
                ]
            );

            DB::commit();

            // Kirim notifikasi email ke Penerbit (Publisher)
            try {
                $reviewUrl = 'http://localhost:5173/publisher/dashboard';
                $documentTypeLabels = [
                    'surat_pernyataan' => 'Surat Pernyataan Penulis',
                    'scan_bermeterai' => 'Scan Bermeterai',
                    'dokumen_pendukung' => 'Dokumen Pendukung Lain',
                ];
                $docLabel = $documentTypeLabels[$document->document_type] ?? ucwords(str_replace('_', ' ', $document->document_type));

                $this->notificationService->sendNewDocumentUploadToPublishers(
                    authorName: $user->name,
                    bookTitle: $manuscript->title,
                    documentTypeLabel: $docLabel,
                    uploadedAt: $document->uploaded_at->format('Y-m-d H:i:s'),
                    reviewUrl: $reviewUrl
                );
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('Failed to send publisher notification for new author document', [
                    'document_id' => $document->id,
                    'error' => $e->getMessage()
                ]);
            }

            return ApiResponse::success(
                'Dokumen administrasi berhasil diunggah.',
                new AuthorDocumentResource($document),
                201
            );

        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponse::error('Gagal mengunggah dokumen: ' . $e->getMessage(), 500);
        }
    }

    /**
     * UC-05: Penulis Menghapus Dokumen Administrasi Tertentu
     * Endpoint: DELETE /api/manuscripts/me/documents/{document_type}
     * Access: Penulis Only
     */
    public function destroy($document_type)
    {
        $user = Auth::user();

        $manuscript = Manuscript::where('user_id', $user->id)
            ->whereNotIn('status', [Manuscript::STATUS_PUBLISHED])
            ->latest()
            ->first();

        if (!$manuscript) {
            return ApiResponse::error('Anda belum memiliki naskah aktif.', 404);
        }

        $document = AuthorDocument::where('manuscript_id', $manuscript->id)
            ->where('document_type', $document_type)
            ->first();

        if (!$document) {
            return ApiResponse::error('Dokumen administrasi tidak ditemukan.', 404);
        }

        if ($document->is_verified) {
            return ApiResponse::error('Dokumen ini telah diverifikasi oleh Admin dan tidak dapat dihapus.', 422);
        }

        try {
            DB::beginTransaction();

            // Hapus berkas file dari storage
            if (Storage::disk('public')->exists($document->file_path)) {
                Storage::disk('public')->delete($document->file_path);
            }

            // Hapus record dari database
            $document->delete();

            DB::commit();

            return ApiResponse::success('Dokumen administrasi berhasil dihapus.', null);

        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponse::error('Gagal menghapus dokumen: ' . $e->getMessage(), 500);
        }
    }

    /**
     * UC-05: Penulis Mengunduh Dokumen Administrasi Tertentu
     * Endpoint: GET /api/manuscripts/me/documents/{document_type}/download
     * Access: Penulis Only
     */
    public function download($document_type)
    {
        $user = Auth::user();

        $manuscript = Manuscript::where('user_id', $user->id)
            ->whereNotIn('status', [Manuscript::STATUS_PUBLISHED])
            ->latest()
            ->first();

        if (!$manuscript) {
            return ApiResponse::error('Anda belum memiliki naskah aktif.', 404);
        }

        $document = AuthorDocument::where('manuscript_id', $manuscript->id)
            ->where('document_type', $document_type)
            ->first();

        if (!$document) {
            return ApiResponse::error('Dokumen administrasi tidak ditemukan.', 404);
        }

        // Memeriksa keberadaan file di storage
        if (!Storage::disk('public')->exists($document->file_path)) {
            return ApiResponse::error('Berkas file tidak ditemukan di server.', 404);
        }

        $path = Storage::disk('public')->path($document->file_path);
        $fileExt = pathinfo($path, PATHINFO_EXTENSION);
        $fileName = strtoupper($document_type) . '.' . $fileExt;

        return response()->download($path, $fileName);
    }
}

