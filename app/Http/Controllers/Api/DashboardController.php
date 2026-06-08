<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Author;
use App\Models\WillingnessForm;
use App\Models\Contract;
use App\Models\Manuscript;
use App\Helpers\ApiResponse;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    /**
     * GET /api/admin/dashboard
     * Ringkasan data untuk Dashboard Admin Awal, bisa berubah
     */
    public function index()
    {
        // Hitung statistik dasar
        $stats = [
            'total_users' => User::count(),
            'total_authors' => Author::count(),
            'pending_willingness_forms' => WillingnessForm::where('status', 'pending')->count(),
            'contracts_waiting_validation' => Contract::where('status', 'contract_uploaded')->count(),
            'validated_contracts' => Contract::where('status', 'contract_validated')->count(),
        ];

        $response = array_merge($stats, [
            '_links' => [
                'self' => '/api/admin/dashboard',
                'manage_users' => [
                    'href' => '/api/users',
                    'method' => 'GET',
                    'message' => 'Kelola akun reviewer, penerbit, dan admin.'
                ],
                'manage_willingness_forms' => [
                    'href' => '/api/willingness-forms',
                    'method' => 'GET',
                    'message' => 'Validasi form kesediaan penulis baru.'
                ],
                'manage_contracts' => [
                    'href' => '/api/contracts',
                    'method' => 'GET',
                    'message' => 'Validasi kontrak hibah buku.'
                ]
            ]
        ]);


        return ApiResponse::success(
            'Ringkasan Dashboard Admin.',
            $response,
            200
        );
    }


    // Method tambahan untuk mendapatkan aktivitas terbaru di dashboard admin
    public function getActivities(Request $request)
    {
        $globalLimit = $request->query('limit', 10);
        $perTableLimit = $globalLimit * 2;

        // User Baru (Role Penulis)
        $users = User::join('roles', 'users.role_id', '=', 'roles.id')
            ->join('authors', 'users.id', '=', 'authors.user_id') // JOIN ke tabel authors
            ->where('roles.name', 'penulis')
            ->select('users.*', 'authors.institution', 'authors.field_of_study') // Ambil kolom tambahan dari authors
            ->distinct()
            ->latest('users.created_at')
            ->take($perTableLimit)
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'type' => 'user_registered',
                    'title' => 'Penulis baru mendaftar',
                    'description' => "{$user->name} - {$user->institution}",
                    'created_at' => $user->created_at,
                    'link' => "/admin/users/{$user->id}"
                ];
            });

        // Formulir Kesediaan Baru (Status Pending)
        $forms = WillingnessForm::latest('created_at')
            ->take($perTableLimit)
            ->get()
            ->map(function ($form) {
                 $statusLower = strtolower($form->status ?? '');

                if ($statusLower === 'pending') {
                    $type = 'willingness_submitted';
                    $title = 'Formulir kesediaan dikirim';
                } elseif ($statusLower === 'approved' || $statusLower === 'validated') {
                    $type = 'willingness_approved';
                    $title = 'Formulir kesediaan disetujui';
                } elseif ($statusLower === 'rejected') {
                    $type = 'willingness_rejected';
                    $title = 'Formulir kesediaan ditolak';
                } else {
                    // Fallback untuk status lain
                    $type = 'willingness_updated';
                    $title = 'Status formulir diperbarui';
                }

                return [
                    'id' => $form->id,
                    'type' => $type,
                    'title' => $title,
                    'description' => "Oleh: {$form->main_author_name} ({$form->book_title})",
                    'created_at' => $form->created_at,
                    'link' => "/api/willingness-form/{$form->id}"
                ];
            });

        // Kontrak Baru Diupload (Status Uploaded/Pending)
        $contracts = Contract::latest('created_at')
            ->take($perTableLimit)
            ->get()
            ->map(function ($contract) {

                $statusLower = strtolower($contract->status ?? '');
                $title = ($statusLower === 'contract_uploaded') ? 'Kontrak diupload' : 'Status kontrak diperbarui';
                if ($statusLower === 'contract_uploaded') {
                    $type = 'contract_uploaded';
                    $title = 'Kontrak diupload';
                } elseif ($statusLower === 'contract_approved' || $statusLower === 'contract_validated') {
                    $type = 'contract_approved';
                    $title = 'Kontrak disetujui';
                } elseif ($statusLower === 'contract_rejected') {
                    $type = 'contract_rejected';
                    $title = 'Kontrak ditolak';
                } else {
                    // Fallback untuk status lain
                    $type = 'contract_uploaded';
                    $title = 'Status kontrak diupload';
                }
                return [
                    'id' => $contract->id,
                    'type' => $type,
                    'title' => $title,
                    'description' => "Kontrak #{$contract->id} oleh {$contract->author->user->name}",
                    'created_at' => $contract->created_at,
                    'link' => "/api/contracts/{$contract->id}"
                ];
            });

        $allActivities = $users->concat($forms)->concat($contracts);
        //  Global Sort Descending
        $sortedActivities = $allActivities->sortByDesc('created_at');

        $finalActivities = $sortedActivities->take($globalLimit)->values();

        // Format Waktu Relatif
        $formattedActivities = $finalActivities->map(function ($activity) {
            $activity['time_ago'] = Carbon::parse($activity['created_at'])->diffForHumans();
            return $activity;
        });

        return ApiResponse::success(
            'Aktivitas terbaru untuk dashboard admin.',
            $formattedActivities,
            200
        );
    }

    /**
     * GET /api/admin/tasks
     * Endpoint untuk mendapatkan naskah yang belum diplot ke reviewer (Khusus Frontend Kel3).
     */
    public function getTasks()
    {
        // Unassigned manuscripts (status draft_uploaded, or any status that needs a reviewer and doesn't have an assignment)
        // Note: Manuscript might not have a draft if not uploaded, but we usually plot when draft_uploaded.
        // Let's get manuscripts that do not exist in reviewer_assignments.
        
        $unassigned = Manuscript::with(['user.author', 'latestFile'])
            ->whereNotIn('id', function($query) {
                $query->select('manuscript_id')->from('reviewer_assignments');
            })
            // ->where('status', 'draft_uploaded') // Optional: only those with draft
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($m) {
                $fileUrl = null;
                if ($m->latestFile) {
                    $fileUrl = \Illuminate\Support\Facades\Storage::url($m->latestFile->file_path);
                }
                
                return [
                    'manuscript_id' => $m->id,
                    'book_title' => $m->title ?: 'Naskah Tanpa Judul',
                    'author_id' => $m->user?->author?->id,
                    'author_email' => $m->user?->email,
                    'manuscript_file_url' => $fileUrl
                ];
            });

        return ApiResponse::success('Daftar tugas plotting', [
            'unassigned' => $unassigned
        ]);
    }
}
