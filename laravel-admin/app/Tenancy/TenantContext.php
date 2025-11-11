<?php

namespace App\Tenancy;

use App\Models\Avdeling;
use App\Models\AvdelingAlternative;
use App\Models\PckCredential;

class TenantContext
{
    private int $tenantId;

    private PckCredential $credential;

    private ?AvdelingAlternative $avdelingAlternative = null;

    private ?Avdeling $avdeling = null;

    public function __construct(int $tenantId, PckCredential $credential)
    {
        $this->tenantId = $tenantId;
        $this->credential = $credential;
    }

    /**
     * Get tenant ID
     */
    public function getTenantId(): int
    {
        return $this->tenantId;
    }

    /**
     * Get PCK credential
     */
    public function getCredential(): PckCredential
    {
        return $this->credential;
    }

    /**
     * Get avdeling (primary tenant table)
     */
    public function getAvdeling(): ?Avdeling
    {
        if ($this->avdeling === null) {
            $this->avdeling = Avdeling::where('siteid', $this->tenantId)->first();
        }

        return $this->avdeling;
    }

    /**
     * Get alternative avdeling (secondary tenant table with WooCommerce keys)
     */
    public function getAvdelingAlternative(): ?AvdelingAlternative
    {
        if ($this->avdelingAlternative === null) {
            $this->avdelingAlternative = AvdelingAlternative::where('SiteID', $this->tenantId)->first();
        }

        return $this->avdelingAlternative;
    }

    /**
     * Get WooCommerce configuration for this tenant
     */
    public function getWooCommerceConfig(): array
    {
        $avdelingAlt = $this->getAvdelingAlternative();

        if (! $avdelingAlt) {
            throw new \RuntimeException("No WooCommerce configuration found for tenant {$this->tenantId}");
        }

        return [
            'base_url' => rtrim($avdelingAlt->APIUrl ?? '', '/'),
            'consumer_key' => $avdelingAlt->APIKey ?? '',
            'consumer_secret' => $avdelingAlt->APISecret ?? '',
            'site_id' => $this->tenantId,
            'site_name' => $avdelingAlt->Navn ?? "Site {$this->tenantId}",
        ];
    }

    /**
     * Get tenant display name
     */
    public function getTenantName(): string
    {
        $avdeling = $this->getAvdeling();
        if ($avdeling && $avdeling->navn) {
            return $avdeling->navn;
        }

        $avdelingAlt = $this->getAvdelingAlternative();
        if ($avdelingAlt && $avdelingAlt->Navn) {
            return $avdelingAlt->Navn;
        }

        return "Tenant {$this->tenantId}";
    }

    /**
     * Check if tenant has valid WooCommerce configuration
     */
    public function hasValidWooCommerceConfig(): bool
    {
        try {
            $config = $this->getWooCommerceConfig();

            return ! empty($config['base_url']) &&
                   ! empty($config['consumer_key']) &&
                   ! empty($config['consumer_secret']);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get PCK license number
     */
    public function getPckLicense(): string
    {
        return $this->credential->pck_license;
    }

    /**
     * Get PCK username
     */
    public function getPckUsername(): string
    {
        return $this->credential->pck_username;
    }

    /**
     * Check if tenant is enabled
     */
    public function isEnabled(): bool
    {
        return $this->credential->is_enabled;
    }

    /**
     * Update last seen timestamp
     */
    public function updateLastSeen(): void
    {
        $this->credential->update(['last_seen_at' => now()]);
    }

    /**
     * Get tenant-specific database query scope
     */
    public function scopeQuery(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('site', $this->tenantId);
    }

    /**
     * Get tenant orders query
     */
    public function getOrdersQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return \App\Models\Order::where('site', $this->tenantId);
    }

    /**
     * Get tenant entity mappings query
     */
    public function getEntityMapsQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return \App\Models\PckEntityMap::where('tenant_id', $this->tenantId);
    }

    /**
     * Get tenant inbound payloads query
     */
    public function getInboundPayloadsQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return \App\Models\PckInboundPayload::where('tenant_id', $this->tenantId);
    }

    /**
     * Convert to array for serialization
     */
    public function toArray(): array
    {
        return [
            'tenant_id' => $this->tenantId,
            'tenant_name' => $this->getTenantName(),
            'pck_username' => $this->getPckUsername(),
            'pck_license' => $this->getPckLicense(),
            'is_enabled' => $this->isEnabled(),
            'has_woo_config' => $this->hasValidWooCommerceConfig(),
        ];
    }

    /**
     * String representation
     */
    public function __toString(): string
    {
        return "Tenant {$this->tenantId} ({$this->getTenantName()})";
    }
}
