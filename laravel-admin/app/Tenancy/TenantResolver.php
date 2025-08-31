<?php

namespace App\Tenancy;

use App\Models\PckCredential;
use Illuminate\Http\Request;
use InvalidArgumentException;

class TenantResolver
{
    /**
     * Resolve tenant from SOAP request
     */
    public static function resolveFromSoapRequest(array $soapParams, Request $request): ?TenantContext
    {
        // Try to resolve from route parameters first (e.g., /soap/pck/{tenantKey})
        $tenantKey = $request->route('tenantKey');
        if ($tenantKey) {
            return self::resolveFromTenantKey($tenantKey);
        }

        // Try to resolve from SOAP parameters
        if (isset($soapParams['login']) && is_numeric($soapParams['login'])) {
            return self::resolveFromLogin((int)$soapParams['login']);
        }

        // Try to resolve from CompanyId if present in SOAP header
        $companyId = self::extractCompanyIdFromSoapHeader($request);
        if ($companyId) {
            return self::resolveFromTenantKey($companyId);
        }

        return null;
    }

    /**
     * Resolve tenant from tenant key (can be numeric ID or string identifier)
     */
    public static function resolveFromTenantKey(string $tenantKey): ?TenantContext
    {
        // If numeric, treat as direct tenant ID
        if (is_numeric($tenantKey)) {
            return self::resolveFromTenantId((int)$tenantKey);
        }

        // Map string identifiers to tenant IDs based on existing system
        $tenantMapping = [
            'steinkjer' => 12,
            'namsos' => 7,
            'lade' => 4,
            'moan' => 6,
            'gramyra' => 5,
            'frosta' => 10,
            'hell' => 11,
        ];

        $tenantId = $tenantMapping[$tenantKey] ?? null;
        if (!$tenantId) {
            return null;
        }

        return self::resolveFromTenantId($tenantId);
    }

    /**
     * Resolve tenant from tenant ID
     */
    public static function resolveFromTenantId(int $tenantId): ?TenantContext
    {
        // Get PCK credential for this tenant to validate it exists
        $credential = PckCredential::where('tenant_id', $tenantId)
            ->where('is_enabled', true)
            ->first();

        if (!$credential) {
            return null;
        }

        return new TenantContext($tenantId, $credential);
    }

    /**
     * Resolve tenant from login parameter (PCK uses login as tenant identifier)
     */
    public static function resolveFromLogin(int $login): ?TenantContext
    {
        // In PCK system, login maps to tenant_id
        return self::resolveFromTenantId($login);
    }

    /**
     * Extract CompanyId from SOAP header if present
     */
    private static function extractCompanyIdFromSoapHeader(Request $request): ?string
    {
        // Parse SOAP XML to extract CompanyId from header
        $soapXml = $request->getContent();
        if (empty($soapXml)) {
            return null;
        }

        try {
            $xml = simplexml_load_string($soapXml);
            if ($xml === false) {
                return null;
            }

            // Look for CompanyId in SOAP header
            $namespaces = $xml->getNamespaces(true);
            
            // Check common SOAP header paths
            $headerPaths = [
                '//soap:Header/CompanyId',
                '//soap:Header//CompanyId', 
                '//Header/CompanyId',
                '//CompanyId'
            ];

            foreach ($headerPaths as $path) {
                $nodes = $xml->xpath($path);
                if (!empty($nodes)) {
                    return (string)$nodes[0];
                }
            }
        } catch (\Exception $e) {
            // Ignore XML parsing errors
        }

        return null;
    }

    /**
     * Validate tenant access for request
     */
    public static function validateTenantAccess(TenantContext $tenant, Request $request): bool
    {
        // Check IP whitelist if configured
        $clientIp = $request->ip();
        if (!$tenant->getCredential()->isIpWhitelisted($clientIp)) {
            return false;
        }

        return true;
    }

    /**
     * Get tenant context from multiple resolution strategies
     */
    public static function resolveWithFallback(array $soapParams, Request $request): ?TenantContext
    {
        // Strategy 1: From route parameters
        $tenantKey = $request->route('tenantKey');
        if ($tenantKey) {
            $context = self::resolveFromTenantKey($tenantKey);
            if ($context) {
                return $context;
            }
        }

        // Strategy 2: From SOAP login parameter
        if (isset($soapParams['login']) && is_numeric($soapParams['login'])) {
            $context = self::resolveFromLogin((int)$soapParams['login']);
            if ($context) {
                return $context;
            }
        }

        // Strategy 3: From SOAP header CompanyId
        $companyId = self::extractCompanyIdFromSoapHeader($request);
        if ($companyId) {
            $context = self::resolveFromTenantKey($companyId);
            if ($context) {
                return $context;
            }
        }

        // Strategy 4: Try to infer from authentication if all else fails
        return self::resolveFromAuthentication($soapParams, $request);
    }

    /**
     * Resolve tenant from authentication parameters
     */
    private static function resolveFromAuthentication(array $soapParams, Request $request): ?TenantContext
    {
        $username = $soapParams['username'] ?? $soapParams['login'] ?? null;
        $password = $soapParams['password'] ?? null;

        if (!$username || !$password) {
            return null;
        }

        // Search for credential that matches username/password
        $credentials = PckCredential::where('is_enabled', true)->get();
        
        foreach ($credentials as $credential) {
            try {
                if ($credential->pck_username === $username && 
                    $credential->pck_password === $password) {
                    return new TenantContext($credential->tenant_id, $credential);
                }
            } catch (\Exception $e) {
                // Continue to next credential if decryption fails
                continue;
            }
        }

        return null;
    }
}