<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\SiteController;
use App\Http\Controllers\Admin\SettingController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

// Create test users for authentication testing
Route::get('/create-users', function () {
    try {
        Log::info('Creating test users');

        // Create admin user
        $admin = User::updateOrCreate(
            ['username' => 'admin'],
            [
                'siteid' => 0,
                'password' => Hash::make('admin123'),
                'license' => 9999
            ]
        );

        // Create regular user
        $user = User::updateOrCreate(
            ['username' => 'namsos'],
            [
                'siteid' => 7,
                'password' => Hash::make('user123'),
                'license' => 123
            ]
        );

        Log::info('Test users created successfully', [
            'admin_id' => $admin->id,
            'user_id' => $user->id
        ]);

        return '<h2>✅ Test Users Created</h2>
                <p><strong>Admin:</strong> admin / admin123 or AroMat1814</p>
                <p><strong>User:</strong> namsos / user123 or AroMat1814</p>
                <p><a href="/login">Go to Login</a></p>';

    } catch (Exception $e) {
        Log::error('Error creating test users', ['error' => $e->getMessage()]);
        return '<h2>❌ Error</h2><p>' . $e->getMessage() . '</p>';
    }
});

// Redirect root to login
Route::get('/', function () {
    Log::info('Root route accessed, redirecting to login');
    return redirect('/login');
});

// Main dashboard routes - use custom auth middleware
Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['custom.auth'])
    ->name('dashboard');

// Admin routes with custom authentication
Route::middleware(['custom.auth'])->group(function () {
    Route::get('/admin/dashboard', [AdminDashboardController::class, 'index'])->name('admin.dashboard');
    Route::post('/admin/dashboard/toggle-status', [AdminDashboardController::class, 'toggleStatus'])->name('admin.dashboard.toggle-status');

    // Order management routes
    Route::prefix('admin/orders')->name('admin.orders.')->group(function () {
        Route::get('/', [OrderController::class, 'index'])->name('index');
        Route::get('/{order}', [OrderController::class, 'show'])->name('show');
        Route::post('/{order}/mark-paid', [OrderController::class, 'markPaid'])->name('mark-paid');
        Route::post('/{order}/send-pos', [OrderController::class, 'sendToPOS'])->name('send-pos');
        Route::post('/{order}/send-sms', [OrderController::class, 'sendSMS'])->name('send-sms');
        Route::patch('/{order}/status', [OrderController::class, 'updateStatus'])->name('update-status');
        Route::delete('/{order}', [OrderController::class, 'destroy'])->name('destroy');
    });
});

// Admin-only routes - use both custom auth and admin middleware
Route::middleware(['custom.auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    // User management
    Route::resource('users', UserController::class);
    Route::post('users/{user}/impersonate', [UserController::class, 'impersonate'])->name('users.impersonate');
    Route::post('stop-impersonate', [UserController::class, 'stopImpersonate'])->name('stop-impersonate');

    // Site management
    Route::resource('sites', SiteController::class);
    Route::get('sites/{site}/users', [SiteController::class, 'users'])->name('sites.users');
    Route::post('sites/{site}/assign-user', [SiteController::class, 'assignUser'])->name('sites.assign-user');

    // Settings management
    Route::get('settings', [SettingController::class, 'index'])->name('settings.index');
    Route::post('settings', [SettingController::class, 'update'])->name('settings.update');
    Route::post('settings/create', [SettingController::class, 'store'])->name('settings.store');
    Route::delete('settings/{setting}', [SettingController::class, 'destroy'])->name('settings.destroy');
    Route::post('settings/test-sms', [SettingController::class, 'testSms'])->name('settings.test-sms');
});

// Profile routes
Route::middleware('custom.auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
