<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\NotificationLogController;
use App\Http\Controllers\Api\NotificationTemplateController;

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