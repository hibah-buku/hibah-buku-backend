<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Author;
use App\Models\WillingnessForm;
use App\Models\Contract;
use App\Helpers\ApiResponse;

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
}
