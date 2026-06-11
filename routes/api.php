<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\WillingnessFormController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\ContractController;
use App\Http\Controllers\Api\ManuscriptController;
use App\Http\Controllers\Api\DraftUploadController;
use App\Http\Controllers\Api\ManuscriptDownloadController;
use App\Http\Controllers\Api\AuthorDocumentController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ReviewerAssignmentController;
use App\Http\Controllers\Api\ReviewRubricController;
use App\Http\Controllers\Api\ReviewerController;
use App\Http\Controllers\Api\PublisherController;
use App\Http\Controllers\Api\NotificationLogController;
use App\Http\Controllers\Api\NotificationTemplateController;
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
        // Dashboard
        Route::get('/dashboard', [DashboardController::class, 'index']);
        Route::get('/dashboard/activities', [DashboardController::class, 'getActivities']);
        Route::get('/admin/tasks', [DashboardController::class, 'getTasks']);

        // Form Kesediaan
        Route::get('/willingness-forms', [WillingnessFormController::class, 'index']);
        Route::get('/willingness-forms/{id}', [WillingnessFormController::class, 'show']);
        Route::patch('/willingness-forms/{id}/approve', [WillingnessFormController::class, 'approve']);
        Route::patch('/willingness-forms/{id}/reject', [WillingnessFormController::class, 'reject']);

        // User Manajemen
        Route::apiResource('users', UserController::class)->only(['index','store', 'show', 'update', 'destroy']);

        // Kontrak Manajemen
        Route::get('/contracts', [ContractController::class, 'index']);
        Route::get('/contracts/{contract}', [ContractController::class, 'show'])->where('contract', '[0-9]+');
        Route::get('/contracts/{contract}/preview', [ContractController::class, 'previewPdf']);
        Route::patch('/contracts/{contract}/validate', [ContractController::class, 'validateContract']);
        Route::patch('/contracts/{contract}/reject', [ContractController::class, 'rejectContract']);
        Route::get('/contracts/{contract}/download', [ContractController::class, 'download']);
    });

    // Penulis Only
    Route::middleware('role:penulis')->group(function () {
        // Kontrak
        Route::get('/contracts/me', [ContractController::class, 'myContract']);
        Route::post('/contracts', [ContractController::class, 'upload']);

        // Naskah (Manuscript)
        Route::get('/manuscripts/dashboard', [ManuscriptController::class, 'dashboard']);
        Route::get('/manuscripts/me', [ManuscriptController::class, 'myManuscripts']);
        Route::post('/manuscripts/upload-draft', [DraftUploadController::class, 'uploadDraft']);
        Route::get('/manuscripts/{manuscript}', [ManuscriptController::class, 'show']);
        Route::post('/manuscripts/{manuscript}/upload-revision', [DraftUploadController::class, 'uploadRevision']);
        Route::get('/manuscripts/{manuscript}/status', [ManuscriptController::class, 'status']);
        Route::get('/manuscripts/{manuscript}/download', [ManuscriptDownloadController::class, 'download']);

        // Dokumen Administrasi Penulis (Author Documents)
        Route::get('/manuscripts/me/documents', [AuthorDocumentController::class, 'index']);
        Route::post('/manuscripts/me/documents', [AuthorDocumentController::class, 'upload']);
        Route::delete('/manuscripts/me/documents/{document_type}', [AuthorDocumentController::class, 'destroy']);
    });

    // Reviewer Assignments & Rubrics
    Route::middleware('role:admin')->group(function () {
        Route::get('/reviewers', [ReviewerController::class, 'index']);
        Route::post('/assignments', [ReviewerAssignmentController::class, 'store']);
        Route::post('/assignments/{assignment}/notify', [ReviewerAssignmentController::class, 'notify']);
    });

    Route::middleware('role:reviewer,admin')->group(function () {
        Route::get('/reviewers/{reviewer}/assignments', [ReviewerAssignmentController::class, 'indexByReviewer']);
        Route::get('/assignments/{assignment}', [ReviewerAssignmentController::class, 'show']);
        Route::get('/assignments/{assignment}/preview', [ReviewerAssignmentController::class, 'preview']);
        Route::get('/assignments/{assignment}/results', [ReviewerAssignmentController::class, 'results']);
        Route::post('/assignments/{assignment}/reviews', [ReviewerAssignmentController::class, 'submitReview']);
        Route::get('/rubrics', [ReviewRubricController::class, 'index']);
    });

    Route::middleware('role:penerbit')->group(function () {
        Route::get('/manuscripts/{manuscript}', [ManuscriptController::class, 'show']);
        Route::get('/manuscripts/{manuscript}/download', [ManuscriptDownloadController::class, 'download']);
        Route::get('/publisher/dashboard', [PublisherController::class, 'dashboard']);
        Route::get('/publisher/manuscripts', [PublisherController::class, 'index']);
        Route::get('/publisher/manuscripts/{id}', [PublisherController::class, 'show']);
        Route::post('/publisher/manuscripts/{id}/decision', [PublisherController::class, 'storeDecision']);
    });

    // Notification templates & logs
    Route::prefix('notification-templates')->group(function () {
        Route::get('/', [NotificationTemplateController::class, 'index']);
        Route::post('/', [NotificationTemplateController::class, 'store']);
        Route::get('/{id}', [NotificationTemplateController::class, 'show']);
        Route::put('/{id}', [NotificationTemplateController::class, 'update']);
    });

    Route::prefix('notification-logs')->group(function () {
        Route::get('/', [NotificationLogController::class, 'index']);
        Route::get('/summary', [NotificationLogController::class, 'summary']);
        Route::get('/{id}', [NotificationLogController::class, 'show']);
        Route::delete('/{id}', [NotificationLogController::class, 'destroy']);
    });

});
