<?php

// ============================================================
// TAMBAHKAN INI KE routes/api.php (di dalam middleware auth)
// ============================================================

use App\Http\Controllers\Api\NotificationLogController;
use App\Http\Controllers\Api\NotificationTemplateController;
use Illuminate\Support\Facades\Route;


// Pastikan di dalam Route::middleware(['auth:sanctum'])->group(...) atau sesuai auth yang dipakai kelompok

// Notification Templates (admin only)
Route::prefix('notification-templates')->group(function () {
    Route::get('/', [NotificationTemplateController::class, 'index']);
    Route::post('/', [NotificationTemplateController::class, 'store']);
    Route::get('/{id}', [NotificationTemplateController::class, 'show']);
    Route::put('/{id}', [NotificationTemplateController::class, 'update']);
});

// Notification Logs (admin only)
Route::prefix('notification-logs')->group(function () {
    Route::get('/', [NotificationLogController::class, 'index']);
    Route::get('/summary', [NotificationLogController::class, 'summary']);
    Route::get('/{id}', [NotificationLogController::class, 'show']);
    Route::delete('/{id}', [NotificationLogController::class, 'destroy']);
});

// ============================================================
// CARA PAKAI NotificationService di controller lain:
// ============================================================
//
// use App\Services\NotificationService;
//
// class SomeController extends Controller {
//     public function __construct(private NotificationService $notif) {}
//
//     public function someAction() {
//         // Kirim notif akun baru ke penulis:
//         $this->notif->sendAccountCreated($email, $name, $password, $loginUrl);
//
//         // Kirim notif reviewer di-assign:
//         $this->notif->sendReviewerAssigned($email, $reviewerName, $authorName, $bookTitle, $deadline, $url);
//     }
// }
