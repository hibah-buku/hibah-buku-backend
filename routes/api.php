<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\WillingnessFormController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\ContractController;
use App\Http\Controllers\Api\DraftController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public Routes
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register-willingness', [WillingnessFormController::class, 'store']);
});

// Protected Routes
Route::middleware('auth:api')->group(function () {

    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);

    // Admin Only
    Route::middleware('role:admin')->group(function () {
        // Form Kesediaan
        Route::get('/willingness-forms', [WillingnessFormController::class, 'index']);
        Route::patch('/willingness-forms/{id}/approve', [WillingnessFormController::class, 'approve']);
        Route::patch('/willingness-forms/{id}/reject', [WillingnessFormController::class, 'reject']);

        // User Manajemen
        Route::apiResource('users', UserController::class)->only(['index']);

        // Kontrak Manajemen
        Route::get('/contracts', [ContractController::class, 'index']);
        Route::patch('/contracts/{contract}/validate', [ContractController::class, 'validateContract']);
        Route::patch('/contracts/{contract}/reject', [ContractController::class, 'rejectContract']);
        Route::get('/contracts/{contract}/download', [ContractController::class, 'download']);
    });

    // Penulis Only
    Route::middleware('role:penulis')->group(function () {
        // Kontrak
        Route::post('/contracts', [ContractController::class, 'upload']);
        Route::get('/contracts/me', [ContractController::class, 'myContract']);
        Route::get('/contracts/{contract}/download', [ContractController::class, 'download']);

        // Naskah (Manuscript)
        Route::get('/manuscripts/dashboard', [DraftController::class, 'dashboard']);
        Route::get('/manuscripts/me', [DraftController::class, 'myManuscripts']);
        Route::post('/manuscripts/upload-draft', [DraftController::class, 'uploadDraft']);
        Route::get('/manuscripts/{manuscript}', [DraftController::class, 'show']);
        Route::post('/manuscripts/{manuscript}/upload-revision', [DraftController::class, 'uploadRevision']);
        Route::get('own/manuscripts/{manuscript}/status', [DraftController::class, 'status']);
        Route::get('/manuscripts/{manuscript}/download', [DraftController::class, 'download']);
    });

    // Reviewer & Penerbit Placeholders
    Route::middleware('role:reviewer')->group(function () {
        Route::get('/reviews/pending', fn() => response()->json(['message' => 'Reviewer Endpoint']));
    });

    Route::middleware('role:penerbit')->group(function () {
        Route::get('/publisher/checks', fn() => response()->json(['message' => 'Publisher Endpoint']));

    });
});
