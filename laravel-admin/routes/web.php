<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\SiteController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\LocationController;
use App\Http\Controllers\Admin\OpeningHoursController;
use App\Http\Controllers\Admin\CateringController;
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

// Public locations page (root domain)
Route::get('/', [App\Http\Controllers\PublicController::class, 'locations'])->name('public.locations');

// Main dashboard routes - use custom auth middleware
Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['custom.auth'])
    ->name('dashboard');

// Delivery time route
Route::post('/admin/delivery-time/update', [DashboardController::class, 'updateDeliveryTime'])
    ->middleware(['custom.auth'])
    ->name('admin.delivery-time.update');

// Admin routes with custom authentication
Route::middleware(['custom.auth'])->group(function () {
    Route::get('/admin/dashboard', [AdminDashboardController::class, 'index'])->name('admin.dashboard');
    Route::post('/admin/dashboard/toggle-status', [AdminDashboardController::class, 'toggleStatus'])->name('admin.dashboard.toggle-status');

    // Stop impersonate route - needs to be outside admin middleware since impersonated users don't have admin rights
    Route::post('/admin/stop-impersonate', [UserController::class, 'stopImpersonate'])->name('admin.stop-impersonate');

    // Opening hours management routes
    Route::prefix('admin/opening-hours')->name('admin.opening-hours.')->group(function () {
        Route::get('/', [OpeningHoursController::class, 'index'])->name('index');
        Route::get('/calendar-data', [OpeningHoursController::class, 'getCalendarData'])->name('calendar-data');
        Route::get('/special/{id}', [OpeningHoursController::class, 'getSpecialHours'])->name('get-special');
        Route::put('/regular/{locationId}', [OpeningHoursController::class, 'updateRegularHours'])->name('update-regular');
        Route::post('/special', [OpeningHoursController::class, 'storeSpecialHours'])->name('store-special');
        Route::put('/special/{id}', [OpeningHoursController::class, 'updateSpecialHours'])->name('update-special');
        Route::delete('/special/{id}', [OpeningHoursController::class, 'destroySpecialHours'])->name('destroy-special');
    });

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

    // Catering management routes
    Route::prefix('admin/catering')->name('admin.catering.')->group(function () {
        Route::get('/', [CateringController::class, 'index'])->name('index');
        Route::get('/orders/{order}', [CateringController::class, 'show'])->name('show');
        Route::patch('/orders/{order}/status', [CateringController::class, 'updateStatus'])->name('update-status');
        Route::get('/settings', [CateringController::class, 'settings'])->name('settings');
        Route::post('/settings/{siteId}', [CateringController::class, 'updateSettings'])->name('update-settings');
        Route::get('/blocked-dates/{siteId}', [CateringController::class, 'blockedDates'])->name('blocked-dates');
        Route::post('/blocked-dates/{siteId}/add', [CateringController::class, 'addBlockedDate'])->name('add-blocked-date');
        Route::post('/blocked-dates/{siteId}/remove', [CateringController::class, 'removeBlockedDate'])->name('remove-blocked-date');
        Route::get('/export', [CateringController::class, 'export'])->name('export');
    });
});

// Admin-only routes - use both custom auth and admin middleware
Route::middleware(['custom.auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    // User management
    Route::resource('users', UserController::class);
    Route::post('users/{user}/impersonate', [UserController::class, 'impersonate'])->name('users.impersonate');

    // Site management
    Route::resource('sites', SiteController::class);
    Route::get('sites/{site}/users', [SiteController::class, 'users'])->name('sites.users');
    Route::post('sites/{site}/assign-user', [SiteController::class, 'assignUser'])->name('sites.assign-user');

    // Location management
    Route::resource('locations', LocationController::class);

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
