<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\WillingnessFormController;
use App\Http\Controllers\Api\AuthorController;
use App\Http\Controllers\Api\ContractController;
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
        Route::get('/willingness-forms', [WillingnessFormController::class, 'index']);
        Route::patch('/willingness-forms/{id}/approve', [WillingnessFormController::class, 'approve']);
        Route::post('/users', [AuthorController::class, 'createManualUser']);
        Route::patch('/contracts/{contract}/validate', [ContractController::class, 'validate']);
        Route::patch('/contracts/{contract}/reject', [ContractController::class, 'reject']);
        Route::get('willingness-forms', [WillingnessFormController::class, 'index']);
        Route::patch('willingness-forms/{id}/approve', [WillingnessFormController::class, 'approve']);
        Route::patch('willingness-forms/{id}/rejected', [WillingnessFormController::class, 'reject']);
    });

    // Penulis Only
    Route::middleware('role:penulis')->group(function () {
        Route::post('/contracts', [ContractController::class, 'upload']);
        Route::get('/contracts/me', [ContractController::class, 'myContract']);
    });

    // Reviewer & Penerbit Placeholders
    Route::middleware('role:reviewer')->group(function () {
        Route::get('/reviews/pending', fn() => response()->json(['message' => 'Reviewer Endpoint']));
    });

    Route::middleware('role:penerbit')->group(function () {
        Route::get('/publisher/checks', fn() => response()->json(['message' => 'Publisher Endpoint']));
    
    });
});
