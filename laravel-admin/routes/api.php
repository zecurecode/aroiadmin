<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ApiController;
use App\Http\Controllers\Admin\OrderController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// API routes for WordPress integration
Route::prefix('v1')->group(function () {
    // Main processing endpoint (replaces the original api.php)
    Route::get('/process-orders', [ApiController::class, 'processOrders']);
    Route::post('/process-orders', [ApiController::class, 'processOrders']);

    // Order management endpoints
    Route::post('/orders', [ApiController::class, 'createOrder']);
    Route::post('/orders/mark-paid', [ApiController::class, 'markOrderPaid']);
    Route::get('/orders', [ApiController::class, 'getOrders']);

    // Legacy compatibility endpoint
    Route::get('/', [ApiController::class, 'processOrders']);
    Route::post('/', [ApiController::class, 'processOrders']);
});

// Internal API routes for admin panel
Route::middleware(['custom.auth'])->group(function () {
    Route::get('/orders/count', [OrderController::class, 'getOrderCount']);
});
