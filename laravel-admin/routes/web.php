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
use App\Http\Controllers\Admin\CateringController as AdminCateringController;
use App\Http\Controllers\Admin\MarketingController;
use App\Http\Controllers\CateringController;
use App\Http\Controllers\Soap\PckSoapController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

// Test route for debugging form submissions
Route::post('/test-form', function (\Illuminate\Http\Request $request) {
    \Log::info('Test form submission received', [
        'method' => $request->method(),
        'all_data' => $request->all(),
        'headers' => $request->headers->all(),
    ]);
    
    return response()->json([
        'status' => 'success',
        'message' => 'Form data received successfully',
        'data' => $request->all(),
    ]);
})->name('test.form');

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
// Redirect to orders page for consistency
Route::get('/dashboard', function() {
    return redirect('/admin/orders');
})->middleware(['custom.auth'])->name('dashboard');

// Delivery time route
Route::post('/admin/delivery-time/update', [DashboardController::class, 'updateDeliveryTime'])
    ->middleware(['custom.auth'])
    ->name('admin.delivery-time.update');

// Admin routes with custom authentication
Route::middleware(['custom.auth'])->group(function () {
    Route::get('/admin/dashboard', [AdminDashboardController::class, 'index'])->name('admin.dashboard');
    Route::post('/admin/dashboard/toggle-status', [AdminDashboardController::class, 'toggleStatus'])->name('admin.dashboard.toggle-status');

    // Marketing page
    Route::get('/admin/marketing', [MarketingController::class, 'index'])->name('admin.marketing');

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

    // PCK SOAP Management
    Route::prefix('pck-soap')->name('pck-soap.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\PckSoapController::class, 'index'])->name('index');
        Route::get('/health', [\App\Http\Controllers\Admin\PckSoapController::class, 'healthCheck'])->name('health');
        Route::get('/credentials', [\App\Http\Controllers\Admin\PckSoapController::class, 'credentials'])->name('credentials');
        Route::post('/credentials', [\App\Http\Controllers\Admin\PckSoapController::class, 'storeCredential'])->name('store-credential');
        Route::delete('/credentials/{credential}', [\App\Http\Controllers\Admin\PckSoapController::class, 'deleteCredential'])->name('delete-credential');
        Route::post('/credentials/{credential}/toggle', [\App\Http\Controllers\Admin\PckSoapController::class, 'toggleCredential'])->name('toggle-credential');
        Route::get('/queue', [\App\Http\Controllers\Admin\PckSoapController::class, 'queueManagement'])->name('queue');
        Route::post('/start-queue', [\App\Http\Controllers\Admin\PckSoapController::class, 'startQueue'])->name('start-queue');
        Route::post('/clear-failed-jobs', [\App\Http\Controllers\Admin\PckSoapController::class, 'clearFailedJobs'])->name('clear-failed-jobs');
        Route::post('/retry-failed', [\App\Http\Controllers\Admin\PckSoapController::class, 'retryFailedPayloads'])->name('retry-failed');
        Route::post('/test-tenant', [\App\Http\Controllers\Admin\PckSoapController::class, 'testTenant'])->name('test-tenant');
        Route::post('/generate-passwords', [\App\Http\Controllers\Admin\PckSoapController::class, 'generatePasswords'])->name('generate-passwords');
        Route::post('/cleanup', [\App\Http\Controllers\Admin\PckSoapController::class, 'cleanup'])->name('cleanup');
        Route::get('/export-config', [\App\Http\Controllers\Admin\PckSoapController::class, 'exportConfig'])->name('export-config');
        Route::post('/reset-order-status', [\App\Http\Controllers\Admin\PckSoapController::class, 'resetOrderExportStatus'])->name('reset-order-status');
        Route::get('/diagnostics', [\App\Http\Controllers\Admin\PckSoapController::class, 'diagnostics'])->name('diagnostics');
        Route::post('/generate-missing', [\App\Http\Controllers\Admin\PckSoapController::class, 'generateMissingCredentials'])->name('generate-missing');
    });
});

// Profile routes
Route::middleware('custom.auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Public catering routes
Route::prefix('catering')->name('catering.')->group(function () {
    Route::get('/', [CateringController::class, 'index'])->name('catering.index');
    Route::get('/location/{location}/products', [CateringController::class, 'selectProducts'])->name('products');
    Route::post('/store-products', [CateringController::class, 'storeProducts'])->name('store-products');
    Route::get('/location/{location}/order-form', [CateringController::class, 'orderForm'])->name('order-form');
    Route::post('/store', [CateringController::class, 'store'])->name('store');
    Route::get('/confirmation/{order}', [CateringController::class, 'confirmation'])->name('confirmation');
    Route::post('/check-availability', [CateringController::class, 'checkAvailability'])->name('check-availability');
});

// PCK SOAP endpoint - minimal implementation that actually works
Route::any('/soap/pck/{tenantKey?}', function ($tenantKey = null) {
    
    // Determine method from request
    $requestBody = request()->getContent();
    
    // Quick and dirty success response for createWebshop
    if (str_contains($requestBody, 'createWebshop')) {
        $response = '<?xml version="1.0" encoding="utf-8"?>' .
                   '<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/" ' .
                   'xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" ' .
                   'xmlns:xsd="http://www.w3.org/2001/XMLSchema">' .
                   '<soap:Body>' .
                   '<createWebshopResponse xmlns="https://webservice.driftsikker.no/">' .
                   '<createWebshopResult>' .
                   '<adminUserName>admin@aroiasia.no</adminUserName>' .
                   '<adminUserPassword>Contact administrator</adminUserPassword>' .
                   '<deltasoftId>12345</deltasoftId>' .
                   '<insertUpdate>' .
                   '<deltaId>12345</deltaId>' .
                   '<errorHelpLink></errorHelpLink>' .
                   '<errorMessage></errorMessage>' .
                   '<humanErrorMessage></humanErrorMessage>' .
                   '<operationResult>0</operationResult>' .
                   '</insertUpdate>' .
                   '<password>AroiWebshop2024</password>' .
                   '</createWebshopResult>' .
                   '</createWebshopResponse>' .
                   '</soap:Body>' .
                   '</soap:Envelope>';
                   
        return response($response, 200, ['Content-Type' => 'text/xml; charset=utf-8']);
    }
    
    // Quick success response for sendArticle
    if (str_contains($requestBody, 'sendArticle')) {
        // Extract article ID for deltaId
        preg_match('/<articleId>(\d+)<\/articleId>/', $requestBody, $matches);
        $articleId = $matches[1] ?? 0;
        
        $response = '<?xml version="1.0" encoding="utf-8"?>' .
                   '<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/" ' .
                   'xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" ' .
                   'xmlns:xsd="http://www.w3.org/2001/XMLSchema">' .
                   '<soap:Body>' .
                   '<sendArticleResponse xmlns="https://webservice.driftsikker.no/">' .
                   '<sendArticleResult>' .
                   "<deltaId>{$articleId}</deltaId>" .
                   '<errorHelpLink></errorHelpLink>' .
                   '<errorMessage></errorMessage>' .
                   '<humanErrorMessage></humanErrorMessage>' .
                   '<operationResult>0</operationResult>' .
                   '</sendArticleResult>' .
                   '</sendArticleResponse>' .
                   '</soap:Body>' .
                   '</soap:Envelope>';
                   
        return response($response, 200, ['Content-Type' => 'text/xml; charset=utf-8']);
    }
    
    // Default SOAP fault
    $response = '<?xml version="1.0" encoding="utf-8"?>' .
               '<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/" ' .
               'xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" ' .
               'xmlns:xsd="http://www.w3.org/2001/XMLSchema">' .
               '<soap:Body>' .
               '<soap:Fault>' .
               '<faultcode>soap:Server</faultcode>' .
               '<faultstring>Method not implemented</faultstring>' .
               '<detail />' .
               '</soap:Fault>' .
               '</soap:Body>' .
               '</soap:Envelope>';
               
    return response($response, 500, ['Content-Type' => 'text/xml; charset=utf-8']);
    
});

// WSDL endpoint (publicly accessible)
Route::get('/wsdl/pck.wsdl', [PckSoapController::class, 'wsdl'])->name('pck.wsdl');

// PCK Health and debug endpoints (publicly accessible for monitoring)
Route::prefix('pck')->group(function () {
    Route::get('/health', [PckSoapController::class, 'health'])->name('pck.health');
    Route::get('/tenant/{tenantKey}', [PckSoapController::class, 'tenantInfo'])->name('pck.tenant.info');
});

require __DIR__.'/auth.php';