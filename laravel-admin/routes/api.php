<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ApiController;
use App\Http\Controllers\Api\CateringController;
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
    
    // WordPress integration endpoints
    Route::prefix('wordpress')->group(function () {
        Route::get('/location/{siteId}', [App\Http\Controllers\Api\WordPressController::class, 'getLocation']);
        Route::get('/location/{siteId}/delivery-time', [App\Http\Controllers\Api\WordPressController::class, 'getDeliveryTime']);
        Route::get('/location/{siteId}/opening-hours', [App\Http\Controllers\Api\WordPressController::class, 'getOpeningHours']);
        Route::get('/location/{siteId}/all-hours', [App\Http\Controllers\Api\WordPressController::class, 'getAllOpeningHours']);
        Route::get('/location/{siteId}/is-open', [App\Http\Controllers\Api\WordPressController::class, 'isOpenNow']);
        Route::post('/location/{siteId}/update-status', [App\Http\Controllers\Api\WordPressController::class, 'updateStatus']);
    });

    // Catering endpoints
    Route::prefix('catering')->group(function () {
        Route::get('/location/{siteId}/settings', [CateringController::class, 'getSettings']);
        Route::post('/location/{siteId}/check-availability', [CateringController::class, 'checkAvailability']);
        Route::get('/location/{siteId}/blocked-dates', [CateringController::class, 'getBlockedDates']);
        Route::post('/orders', [CateringController::class, 'createOrder']);
        Route::post('/orders/mark-paid', [CateringController::class, 'markPaid']);
        Route::put('/orders/{orderId}/status', [CateringController::class, 'updateStatus']);
    });
});

// Internal API routes for admin panel
Route::middleware(['web', 'custom.auth'])->group(function () {
    Route::get('/orders/count', [OrderController::class, 'getOrderCount']);
});
