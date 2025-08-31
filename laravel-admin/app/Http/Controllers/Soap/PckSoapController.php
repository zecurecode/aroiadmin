<?php

namespace App\Http\Controllers\Soap;

use App\Http\Controllers\Controller;
use App\Soap\PckSoapHandler;
use App\Tenancy\TenantResolver;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use SoapServer;
use SoapFault;

class PckSoapController extends Controller
{
    /**
     * Serve the WSDL file
     */
    public function wsdl(Request $request): Response
    {
        $wsdlPath = public_path('wsdl/pck.wsdl');
        
        if (!file_exists($wsdlPath)) {
            abort(404, 'WSDL file not found');
        }

        // Update WSDL location dynamically based on request
        $wsdlContent = file_get_contents($wsdlPath);
        $baseUrl = $request->getSchemeAndHttpHost();
        $soapEndpoint = $baseUrl . '/soap/pck';
        
        // Replace placeholder with actual endpoint
        $wsdlContent = str_replace(
            'http://localhost/soap/pck',
            $soapEndpoint,
            $wsdlContent
        );

        // Check if browser request (for viewing) or SOAP client request (for download)
        $userAgent = $request->userAgent();
        $accept = $request->header('Accept', '');
        $forceXml = $request->has('xml') || $request->has('raw');
        
        // If it's a browser request and not forced XML, show as HTML for easy viewing
        if (!$forceXml && str_contains($userAgent, 'Mozilla') && str_contains($accept, 'text/html')) {
            try {
                return response()->view('soap.wsdl-viewer', [
                    'wsdl_content' => htmlspecialchars($wsdlContent),
                    'endpoint_url' => $soapEndpoint,
                    'wsdl_url' => $request->url(),
                ], 200, [
                    'Content-Type' => 'text/html; charset=utf-8',
                    'Cache-Control' => 'public, max-age=3600',
                ]);
            } catch (\Exception $e) {
                // Fallback to raw XML if view doesn't exist
                Log::warning('WSDL viewer template not found, falling back to raw XML', [
                    'error' => $e->getMessage()
                ]);
            }
        }

        // For SOAP clients, direct XML requests, or fallback, return raw XML
        // Use application/wsdl+xml for proper SOAP client compatibility
        return response($wsdlContent, 200, [
            'Content-Type' => 'application/wsdl+xml; charset=utf-8',
            'Cache-Control' => 'public, max-age=3600',
            'X-Content-Type-Options' => 'nosniff',
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'GET, POST, OPTIONS',
        ]);
    }

    /**
     * Handle SOAP requests
     */
    public function handle(Request $request, ?string $tenantKey = null): Response
    {
        try {
            // Check if this is a WSDL request (GET with ?wsdl parameter)
            if ($request->isMethod('GET') && $request->has('wsdl')) {
                return $this->wsdl($request);
            }

            // Check if this is a GET request without SOAP data (browser access)
            if ($request->isMethod('GET')) {
                return response('
                    <html>
                        <head><title>PCK SOAP Endpoint</title></head>
                        <body>
                            <h1>🔧 PCK SOAP Endpoint</h1>
                            <p>This is a SOAP endpoint for PCKasse integration.</p>
                            <p><strong>Tenant:</strong> ' . ($tenantKey ?? 'Auto-resolved') . '</p>
                            <p><strong>WSDL:</strong> <a href="/wsdl/pck.wsdl">View WSDL</a></p>
                            <p><strong>Health Check:</strong> <a href="/pck/health">System Status</a></p>
                            <hr>
                            <p><small>To use this endpoint, send SOAP requests via POST with proper XML payload.</small></p>
                        </body>
                    </html>
                ', 200, ['Content-Type' => 'text/html']);
            }

            // Enable SOAP error reporting
            ini_set('soap.wsdl_cache_enabled', '0');
            
            // Use local WSDL file path instead of HTTP URL to avoid circular loading
            $wsdlPath = public_path('wsdl/pck.wsdl');
            
            if (!file_exists($wsdlPath)) {
                throw new \Exception('WSDL file not found at: ' . $wsdlPath);
            }
            
            // Create SOAP server with document/literal for .NET ASMX compatibility
            $server = new SoapServer($wsdlPath, [
                'soap_version' => SOAP_1_1, // BasicProfile 1.1
                'cache_wsdl' => WSDL_CACHE_NONE,
                'features' => SOAP_SINGLE_ELEMENT_ARRAYS, // safer array serialization
                'location' => $request->getSchemeAndHttpHost() . '/soap/pck/' . ($tenantKey ?? ''),
            ]);

            // Create handler with request context
            $handler = new PckSoapHandler($request, $tenantKey);
            
            // Set the service object
            $server->setObject($handler);

            // Log the incoming request
            Log::info('PCK SOAP request received', [
                'method' => $request->getMethod(),
                'url' => $request->getUri(),
                'tenant_key' => $tenantKey,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'content_length' => $request->header('Content-Length'),
                'content_type' => $request->header('Content-Type'),
                'soap_action' => $request->header('SOAPAction'),
                'request_body' => $request->getContent(), // Log actual SOAP XML request
            ]);

            // Clean any output buffer before SOAP handling
            if (ob_get_level() > 0) { 
                ob_end_clean(); 
            }
            
            // Let SoapServer handle everything and serialize PHP objects automatically
            ob_start();
            $server->handle();
            $response = ob_get_clean();

            Log::info('PCK SOAP response sent', [
                'tenant_key' => $tenantKey,
                'response_length' => strlen($response),
                'ip' => $request->ip(),
                'actual_response_content' => $response,
            ]);

            return response($response, 200, [
                'Content-Type' => 'text/xml; charset=utf-8',
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
                'Pragma' => 'no-cache',
                'Expires' => '0',
            ]);

        } catch (SoapFault $e) {
            Log::error('PCK SOAP fault', [
                'code' => $e->getCode(),
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'tenant_key' => $tenantKey,
                'request_ip' => $request->ip(),
            ]);

            // Return SOAP fault response
            return response()->json([
                'faultcode' => $e->getCode(),
                'faultstring' => $e->getMessage(),
            ], 500);

        } catch (\Exception $e) {
            Log::error('PCK SOAP server error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'tenant_key' => $tenantKey,
                'request_ip' => $request->ip(),
            ]);

            // Return generic error response
            return response('Internal Server Error', 500, [
                'Content-Type' => 'text/plain',
            ]);
        }
    }


    /**
     * Health check endpoint
     */
    public function health(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            $wsdlPath = public_path('wsdl/pck.wsdl');
            $wsdlExists = file_exists($wsdlPath);
            
            $status = [
                'status' => 'ok',
                'timestamp' => now()->toISOString(),
                'wsdl_exists' => $wsdlExists,
                'soap_extension' => extension_loaded('soap'),
                'php_version' => PHP_VERSION,
            ];

            // Check database connectivity
            try {
                \DB::connection()->getPdo();
                $status['database'] = 'connected';
            } catch (\Exception $e) {
                $status['database'] = 'error';
                $status['database_error'] = $e->getMessage();
            }

            // Check tenant configuration
            $tenantCount = \App\Models\PckCredential::where('is_enabled', true)->count();
            $status['enabled_tenants'] = $tenantCount;

            return response()->json($status, 200);
            
        } catch (\Exception $e) {
            Log::error('PCK SOAP health check failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Health check failed',
                'timestamp' => now()->toISOString(),
            ], 500);
        }
    }

    /**
     * Get tenant information (for debugging)
     */
    public function tenantInfo(Request $request, string $tenantKey): \Illuminate\Http\JsonResponse
    {
        try {
            $tenant = TenantResolver::resolveFromTenantKey($tenantKey);
            
            if (!$tenant) {
                return response()->json([
                    'error' => 'Tenant not found',
                    'tenant_key' => $tenantKey,
                ], 404);
            }

            return response()->json([
                'tenant' => $tenant->toArray(),
                'woo_config_valid' => $tenant->hasValidWooCommerceConfig(),
                'last_seen' => $tenant->getCredential()->last_seen_at?->toISOString(),
            ]);

        } catch (\Exception $e) {
            Log::error('PCK tenant info error', [
                'tenant_key' => $tenantKey,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to retrieve tenant information',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}