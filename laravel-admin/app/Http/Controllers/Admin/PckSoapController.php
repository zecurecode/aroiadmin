<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PckCredential;
use App\Models\PckInboundPayload;
use App\Models\PckEntityMap;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class PckSoapController extends Controller
{
    /**
     * Display PCK SOAP management dashboard
     */
    public function index()
    {
        $stats = $this->getSystemStats();
        $credentials = PckCredential::with(['avdeling', 'avdelingAlternative'])
            ->orderBy('tenant_id')
            ->get();

        return view('admin.pck-soap.index', compact('stats', 'credentials'));
    }

    /**
     * Get system statistics
     */
    private function getSystemStats(): array
    {
        return [
            'soap_extension' => extension_loaded('soap'),
            'wsdl_exists' => file_exists(public_path('wsdl/pck.wsdl')),
            'total_credentials' => PckCredential::count(),
            'enabled_credentials' => PckCredential::where('is_enabled', true)->count(),
            'total_payloads' => PckInboundPayload::count(),
            'pending_payloads' => PckInboundPayload::where('status', 'received')->count(),
            'failed_payloads' => PckInboundPayload::where('status', 'failed')->count(),
            'processed_today' => PckInboundPayload::where('status', 'processed')
                ->whereDate('processed_at', today())->count(),
            'total_mappings' => PckEntityMap::count(),
            'orders_ready_export' => Order::readyForPckExport()->count(),
            'orders_exported_today' => Order::exportedToPck()
                ->whereDate('pck_exported_at', today())->count(),
            'queue_size' => $this->getQueueSize(),
            'failed_jobs' => $this->getFailedJobsCount(),
        ];
    }

    /**
     * Get queue size
     */
    private function getQueueSize(): int
    {
        try {
            return Queue::size('pck-inbound');
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get failed jobs count
     */
    private function getFailedJobsCount(): int
    {
        try {
            return DB::table('failed_jobs')->count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * System health check
     */
    public function healthCheck()
    {
        $health = [
            'overall_status' => 'healthy',
            'checks' => [],
            'timestamp' => now()->toISOString(),
        ];

        // SOAP Extension Check
        $health['checks']['soap_extension'] = [
            'status' => extension_loaded('soap') ? 'pass' : 'fail',
            'message' => extension_loaded('soap') ? 'SOAP extension loaded' : 'SOAP extension not found',
        ];

        // WSDL File Check
        $wsdlPath = public_path('wsdl/pck.wsdl');
        $health['checks']['wsdl_file'] = [
            'status' => file_exists($wsdlPath) ? 'pass' : 'fail',
            'message' => file_exists($wsdlPath) ? 'WSDL file exists' : 'WSDL file missing',
            'path' => $wsdlPath,
        ];

        // Database Check
        try {
            DB::connection()->getPdo();
            $health['checks']['database'] = [
                'status' => 'pass',
                'message' => 'Database connection successful',
            ];
        } catch (\Exception $e) {
            $health['checks']['database'] = [
                'status' => 'fail',
                'message' => 'Database connection failed: ' . $e->getMessage(),
            ];
            $health['overall_status'] = 'unhealthy';
        }

        // Queue Check
        try {
            $queueSize = Queue::size('pck-inbound');
            $health['checks']['queue'] = [
                'status' => 'pass',
                'message' => "Queue operational ({$queueSize} jobs pending)",
                'pending_jobs' => $queueSize,
            ];
        } catch (\Exception $e) {
            $health['checks']['queue'] = [
                'status' => 'fail',
                'message' => 'Queue system error: ' . $e->getMessage(),
            ];
            $health['overall_status'] = 'unhealthy';
        }

        // Credentials Check
        $enabledCredentials = PckCredential::where('is_enabled', true)->count();
        $health['checks']['credentials'] = [
            'status' => $enabledCredentials > 0 ? 'pass' : 'warn',
            'message' => "{$enabledCredentials} enabled PCK credentials found",
            'enabled_count' => $enabledCredentials,
        ];

        // Failed Jobs Check
        $failedJobs = $this->getFailedJobsCount();
        $health['checks']['failed_jobs'] = [
            'status' => $failedJobs === 0 ? 'pass' : 'warn',
            'message' => $failedJobs === 0 ? 'No failed jobs' : "{$failedJobs} failed jobs found",
            'failed_count' => $failedJobs,
        ];

        // Overall status
        $failCount = collect($health['checks'])->where('status', 'fail')->count();
        if ($failCount > 0) {
            $health['overall_status'] = 'unhealthy';
        } elseif (collect($health['checks'])->where('status', 'warn')->count() > 0) {
            $health['overall_status'] = 'warning';
        }

        return response()->json($health);
    }

    /**
     * Manage credentials
     */
    public function credentials()
    {
        $credentials = PckCredential::with(['avdeling', 'avdelingAlternative'])
            ->orderBy('tenant_id')
            ->get();

        return view('admin.pck-soap.credentials', compact('credentials'));
    }

    /**
     * Create or update credential
     */
    public function storeCredential(Request $request)
    {
        // Log the incoming request for debugging
        Log::info('PckSoapController::storeCredential - Request received', [
            'request_data' => $request->all(),
            'method' => $request->method(),
            'url' => $request->url(),
        ]);

        try {
            $validated = $request->validate([
                'tenant_id' => 'required|integer',
                'pck_username' => 'required|string|max:100',
                'pck_password' => 'required|string|min:8',
                'pck_license' => 'required|string|max:50',
                'ip_whitelist' => 'nullable|string',
                'is_enabled' => 'nullable', // Handle checkbox properly
            ]);

            Log::info('PckSoapController::storeCredential - Validation passed', [
                'validated_data' => array_merge($validated, ['pck_password' => '***masked***'])
            ]);

            // Handle IP whitelist
            $ipWhitelist = null;
            if (!empty($validated['ip_whitelist'])) {
                $ipWhitelist = array_map('trim', explode(',', $validated['ip_whitelist']));
            }

            // Handle checkbox properly
            $isEnabled = $request->has('is_enabled');

            Log::info('PckSoapController::storeCredential - About to save', [
                'tenant_id' => $validated['tenant_id'],
                'username' => $validated['pck_username'],
                'license' => $validated['pck_license'],
                'ip_count' => $ipWhitelist ? count($ipWhitelist) : 0,
                'is_enabled' => $isEnabled,
            ]);

            $credential = PckCredential::updateOrCreate(
                [
                    'tenant_id' => $validated['tenant_id'],
                    'pck_username' => $validated['pck_username'],
                ],
                [
                    'pck_password' => $validated['pck_password'],
                    'pck_license' => $validated['pck_license'],
                    'ip_whitelist' => $ipWhitelist,
                    'is_enabled' => $isEnabled,
                    'wsdl_version' => '1.98',
                ]
            );

            Log::info('PckSoapController::storeCredential - Credential saved successfully', [
                'credential_id' => $credential->id,
                'tenant_id' => $credential->tenant_id,
                'was_recently_created' => $credential->wasRecentlyCreated,
            ]);

            return redirect()->route('admin.pck-soap.credentials')
                ->with('success', 'PCK credential saved successfully.');

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('PckSoapController::storeCredential - Validation failed', [
                'errors' => $e->errors(),
                'request_data' => array_merge($request->all(), ['pck_password' => '***masked***'])
            ]);
            throw $e;

        } catch (\Exception $e) {
            Log::error('PckSoapController::storeCredential - Unexpected error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => array_merge($request->all(), ['pck_password' => '***masked***'])
            ]);
            
            return redirect()->back()
                ->withInput()
                ->with('error', 'An error occurred while saving credential: ' . $e->getMessage());
        }
    }

    /**
     * Delete credential
     */
    public function deleteCredential(PckCredential $credential)
    {
        $credential->delete();

        return redirect()->route('admin.pck-soap.credentials')
            ->with('success', 'PCK credential deleted successfully.');
    }

    /**
     * Toggle credential status
     */
    public function toggleCredential(PckCredential $credential)
    {
        $credential->update(['is_enabled' => !$credential->is_enabled]);

        $status = $credential->is_enabled ? 'enabled' : 'disabled';
        return redirect()->back()
            ->with('success', "PCK credential {$status} successfully.");
    }

    /**
     * Queue management
     */
    public function queueManagement()
    {
        $stats = [
            'queue_size' => $this->getQueueSize(),
            'failed_jobs' => $this->getFailedJobsCount(),
            'pending_payloads' => PckInboundPayload::where('status', 'received')->count(),
            'failed_payloads' => PckInboundPayload::where('status', 'failed')
                ->with(['avdeling'])
                ->latest('received_at')
                ->limit(10)
                ->get(),
            'recent_processed' => PckInboundPayload::where('status', 'processed')
                ->with(['avdeling'])
                ->latest('processed_at')
                ->limit(10)
                ->get(),
        ];

        return view('admin.pck-soap.queue', compact('stats'));
    }

    /**
     * Start queue worker
     */
    public function startQueue()
    {
        try {
            // Start queue worker in background
            Artisan::call('queue:work', [
                '--queue' => 'pck-inbound',
                '--timeout' => 60,
                '--tries' => 3,
                '--daemon' => true,
            ]);

            return redirect()->back()
                ->with('success', 'Queue worker started successfully.');

        } catch (\Exception $e) {
            Log::error('Failed to start queue worker', ['error' => $e->getMessage()]);
            
            return redirect()->back()
                ->with('error', 'Failed to start queue worker: ' . $e->getMessage());
        }
    }

    /**
     * Clear failed jobs
     */
    public function clearFailedJobs()
    {
        try {
            Artisan::call('queue:flush');
            
            return redirect()->back()
                ->with('success', 'Failed jobs cleared successfully.');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to clear failed jobs: ' . $e->getMessage());
        }
    }

    /**
     * Retry failed payloads
     */
    public function retryFailedPayloads()
    {
        try {
            $failedPayloads = PckInboundPayload::where('status', 'failed')
                ->where('updated_at', '<', now()->subMinutes(5))
                ->get();

            $count = 0;
            foreach ($failedPayloads as $payload) {
                // Reset status to received for retry
                $payload->update([
                    'status' => 'received',
                    'error' => null,
                ]);

                // Redispatch appropriate job based on method
                $this->redispatchJob($payload);
                $count++;
            }

            return redirect()->back()
                ->with('success', "Successfully queued {$count} failed payloads for retry.");

        } catch (\Exception $e) {
            Log::error('Failed to retry payloads', ['error' => $e->getMessage()]);
            
            return redirect()->back()
                ->with('error', 'Failed to retry payloads: ' . $e->getMessage());
        }
    }

    /**
     * Redispatch job based on payload method
     */
    private function redispatchJob(PckInboundPayload $payload): void
    {
        match ($payload->method) {
            'sendArticle' => \App\Jobs\ProcessInboundArticlePayload::dispatch($payload->id)->onQueue('pck-inbound'),
            'sendImage', 'sendImageColor' => \App\Jobs\ProcessInboundImagePayload::dispatch($payload->id)->onQueue('pck-inbound'),
            'updateStockCount' => \App\Jobs\ProcessStockUpdatePayload::dispatch($payload->id)->onQueue('pck-inbound'),
            default => Log::warning("Unknown payload method for redispatch: {$payload->method}", [
                'payload_id' => $payload->id,
                'method' => $payload->method,
            ]),
        };
    }

    /**
     * Test SOAP connectivity for a tenant
     */
    public function testTenant(Request $request)
    {
        $tenantId = $request->input('tenant_id');
        
        try {
            $credential = PckCredential::where('tenant_id', $tenantId)
                ->where('is_enabled', true)
                ->first();

            if (!$credential) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No enabled credential found for tenant',
                ]);
            }

            // Test WooCommerce connectivity
            $tenant = new \App\Tenancy\TenantContext($tenantId, $credential);
            $wooTest = $this->testWooCommerceConnection($tenant);

            return response()->json([
                'status' => 'success',
                'credential_status' => 'active',
                'woo_test' => $wooTest,
                'tenant_info' => $tenant->toArray(),
            ]);

        } catch (\Exception $e) {
            Log::error('Tenant test failed', [
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Test WooCommerce connection
     */
    private function testWooCommerceConnection(\App\Tenancy\TenantContext $tenant): array
    {
        try {
            if (!$tenant->hasValidWooCommerceConfig()) {
                return [
                    'status' => 'error',
                    'message' => 'Invalid WooCommerce configuration',
                ];
            }

            $wooService = new \App\Services\Woo\WooCommerceService($tenant);
            
            // Try to get products (simple test)
            $products = $wooService->searchProducts('', ['per_page' => 1]);
            
            return [
                'status' => 'success',
                'message' => 'WooCommerce connection successful',
                'product_count' => count($products),
            ];

        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'WooCommerce connection failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Generate secure passwords for all credentials
     */
    public function generatePasswords()
    {
        try {
            $credentials = PckCredential::all();
            $updated = 0;

            foreach ($credentials as $credential) {
                $newPassword = $this->generateSecurePassword();
                $credential->update(['pck_password' => $newPassword]);
                $updated++;

                Log::info('PCK password updated', [
                    'tenant_id' => $credential->tenant_id,
                    'username' => $credential->pck_username,
                ]);
            }

            return redirect()->back()
                ->with('success', "Updated passwords for {$updated} credentials. Check logs for new passwords.");

        } catch (\Exception $e) {
            Log::error('Failed to generate passwords', ['error' => $e->getMessage()]);
            
            return redirect()->back()
                ->with('error', 'Failed to generate passwords: ' . $e->getMessage());
        }
    }

    /**
     * Generate secure password
     */
    private function generateSecurePassword(): string
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
        $password = '';
        
        for ($i = 0; $i < 16; $i++) {
            $password .= $chars[random_int(0, strlen($chars) - 1)];
        }
        
        return $password;
    }

    /**
     * Cleanup old data
     */
    public function cleanup()
    {
        try {
            $cleaned = [
                'old_payloads' => PckInboundPayload::cleanupOld(),
                'failed_jobs' => 0,
            ];

            // Clear old failed jobs
            Artisan::call('queue:prune-failed', ['--hours' => 168]); // 1 week old
            
            return redirect()->back()
                ->with('success', "Cleanup completed. Removed {$cleaned['old_payloads']} old payloads.");

        } catch (\Exception $e) {
            Log::error('Cleanup failed', ['error' => $e->getMessage()]);
            
            return redirect()->back()
                ->with('error', 'Cleanup failed: ' . $e->getMessage());
        }
    }

    /**
     * Export configuration for PCKasse setup
     */
    public function exportConfig()
    {
        $credentials = PckCredential::with(['avdeling', 'avdelingAlternative'])
            ->where('is_enabled', true)
            ->get();

        $baseUrl = request()->getSchemeAndHttpHost();

        $config = [
            'soap_endpoints' => [
                'wsdl_url' => "{$baseUrl}/wsdl/pck.wsdl",
                'soap_endpoint' => "{$baseUrl}/soap/pck",
                'health_check' => "{$baseUrl}/pck/health",
            ],
            'credentials' => [],
        ];

        foreach ($credentials as $credential) {
            $tenantName = $credential->avdeling->navn ?? 
                         $credential->avdelingAlternative->Navn ?? 
                         "Tenant {$credential->tenant_id}";

            $config['credentials'][] = [
                'tenant_id' => $credential->tenant_id,
                'tenant_name' => $tenantName,
                'pck_username' => $credential->pck_username,
                'pck_license' => $credential->pck_license,
                'soap_endpoint' => "{$baseUrl}/soap/pck/{$credential->tenant_id}",
                'last_seen' => $credential->last_seen_at?->toISOString(),
            ];
        }

        $filename = 'pck-soap-config-' . now()->format('Y-m-d-H-i-s') . '.json';

        return response()->json($config)
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }

    /**
     * Reset export status for orders (for testing)
     */
    public function resetOrderExportStatus(Request $request)
    {
        $tenantId = $request->input('tenant_id');
        
        try {
            $updated = Order::where('site', $tenantId)
                ->where('pck_export_status', '!=', 'new')
                ->update([
                    'pck_export_status' => 'new',
                    'pck_exported_at' => null,
                    'pck_last_error' => null,
                ]);

            return redirect()->back()
                ->with('success', "Reset export status for {$updated} orders.");

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to reset order status: ' . $e->getMessage());
        }
    }

    /**
     * Run system diagnostics
     */
    public function diagnostics()
    {
        $diagnostics = [
            'system_info' => [
                'php_version' => PHP_VERSION,
                'laravel_version' => app()->version(),
                'soap_extension' => extension_loaded('soap'),
                'gd_extension' => extension_loaded('gd'),
                'curl_extension' => extension_loaded('curl'),
                'openssl_extension' => extension_loaded('openssl'),
            ],
            'database_info' => [],
            'queue_info' => [],
            'recent_activity' => [],
        ];

        // Database diagnostics
        try {
            $diagnostics['database_info'] = [
                'connection' => 'successful',
                'tables' => [
                    'pck_credentials' => DB::table('pck_credentials')->count(),
                    'pck_entity_maps' => DB::table('pck_entity_maps')->count(),
                    'pck_inbound_payloads' => DB::table('pck_inbound_payloads')->count(),
                    'orders' => DB::table('orders')->count(),
                ],
            ];
        } catch (\Exception $e) {
            $diagnostics['database_info'] = [
                'connection' => 'failed',
                'error' => $e->getMessage(),
            ];
        }

        // Queue diagnostics
        try {
            $diagnostics['queue_info'] = [
                'driver' => config('queue.default'),
                'pck_queue_size' => Queue::size('pck-inbound'),
                'failed_jobs' => $this->getFailedJobsCount(),
            ];
        } catch (\Exception $e) {
            $diagnostics['queue_info'] = [
                'error' => $e->getMessage(),
            ];
        }

        // Recent activity
        try {
            $diagnostics['recent_activity'] = [
                'recent_payloads' => PckInboundPayload::with(['avdeling'])
                    ->latest('received_at')
                    ->limit(5)
                    ->get()
                    ->map(function ($payload) {
                        return [
                            'id' => $payload->id,
                            'tenant' => $payload->avdeling->navn ?? "Tenant {$payload->tenant_id}",
                            'method' => $payload->method,
                            'status' => $payload->status,
                            'received_at' => $payload->received_at->diffForHumans(),
                        ];
                    }),
                'recent_exports' => Order::exportedToPck()
                    ->latest('pck_exported_at')
                    ->limit(5)
                    ->get()
                    ->map(function ($order) {
                        return [
                            'order_id' => $order->ordreid,
                            'customer' => $order->full_name,
                            'exported_at' => $order->pck_exported_at->diffForHumans(),
                        ];
                    }),
            ];
        } catch (\Exception $e) {
            $diagnostics['recent_activity'] = [
                'error' => $e->getMessage(),
            ];
        }

        return view('admin.pck-soap.diagnostics', compact('diagnostics'));
    }

    /**
     * Generate missing PCK credentials for locations without them
     */
    public function generateMissingCredentials()
    {
        try {
            // Get all locations from avdeling table
            $existingTenants = PckCredential::pluck('tenant_id')->toArray();
            
            // Get locations that don't have PCK credentials
            $locationsWithoutCredentials = DB::table('avdeling')
                ->whereNotIn('siteid', $existingTenants)
                ->get();

            $created = 0;
            foreach ($locationsWithoutCredentials as $location) {
                $username = strtolower($location->navn) . '_pck';
                $password = $this->generateSecurePassword();
                
                PckCredential::create([
                    'tenant_id' => $location->siteid,
                    'pck_username' => $username,
                    'pck_password' => $password,
                    'pck_license' => '0000', // Default, needs manual update
                    'wsdl_version' => '1.98',
                    'is_enabled' => false, // Start disabled until configured
                ]);

                Log::info('Generated PCK credential for location', [
                    'tenant_id' => $location->siteid,
                    'location_name' => $location->navn,
                    'username' => $username,
                    'password' => $password, // Log password for initial setup
                ]);

                $created++;
            }

            if ($created === 0) {
                return redirect()->back()
                    ->with('info', 'All locations already have PCK credentials.');
            }

            return redirect()->back()
                ->with('success', "Generated {$created} new PCK credentials. Check logs for passwords.");

        } catch (\Exception $e) {
            Log::error('Failed to generate missing credentials', ['error' => $e->getMessage()]);
            
            return redirect()->back()
                ->with('error', 'Failed to generate credentials: ' . $e->getMessage());
        }
    }
}