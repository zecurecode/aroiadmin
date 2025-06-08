<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use Illuminate\Support\Facades\Route;

// Simple authentication routes - no complex middleware
Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
Route::post('login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
