<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\DashboardController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

// Test route to verify authentication
Route::get('/test-auth-status', function () {
    return response()->json([
        'authenticated' => Auth::check(),
        'user' => Auth::user() ? Auth::user()->username : null,
        'user_id' => Auth::user() ? Auth::user()->id : null,
        'session_id' => session()->getId(),
        'session_data' => session()->all()
    ]);
});

// Admin routes - TEMPORARILY removed 'auth' middleware for testing
Route::prefix('admin')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('admin.dashboard');
});

// User routes - TEMPORARILY removed 'auth' middleware for testing
Route::group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
});

require __DIR__.'/auth.php'; 