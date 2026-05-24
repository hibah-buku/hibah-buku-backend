<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Author;
use App\Models\WillingnessForm;
use App\Models\Contract;
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
            ->where('roles.name', 'penulis')
            ->latest('users.created_at')
            ->take($perTableLimit)
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'type' => 'user_registered',
                    'title' => 'Penulis baru mendaftar',
                    'description' => "{$user->name} - {$user->institution}", // Asumsi ada field institution
                    'created_at' => $user->created_at,
                    'link' => "/api/users/{$user->id}"
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
}
