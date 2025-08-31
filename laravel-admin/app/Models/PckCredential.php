<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PckCredential extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'pck_username',
        'pck_password',
        'pck_license',
        'wsdl_version',
        'ip_whitelist',
        'is_enabled',
        'last_seen_at',
    ];

    protected function casts(): array
    {
        return [
            'tenant_id' => 'integer',
            'ip_whitelist' => 'array',
            'is_enabled' => 'boolean',
            'last_seen_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Encrypt/decrypt password field
     */
    protected function pckPassword(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: fn (?string $value) => $value ? decrypt($value) : null,
            set: fn (?string $value) => $value ? encrypt($value) : null,
        );
    }

    /**
     * Get the tenant/location this credential belongs to
     */
    public function avdeling(): BelongsTo
    {
        return $this->belongsTo(Avdeling::class, 'tenant_id', 'siteid');
    }

    /**
     * Get the alternative avdeling this credential belongs to
     */
    public function avdelingAlternative(): BelongsTo
    {
        return $this->belongsTo(AvdelingAlternative::class, 'tenant_id', 'SiteID');
    }

    /**
     * Get the entity mappings for this tenant
     */
    public function entityMaps(): HasMany
    {
        return $this->hasMany(PckEntityMap::class, 'tenant_id', 'tenant_id');
    }

    /**
     * Get the inbound payloads for this tenant
     */
    public function inboundPayloads(): HasMany
    {
        return $this->hasMany(PckInboundPayload::class, 'tenant_id', 'tenant_id');
    }

    /**
     * Authenticate PCK credentials
     */
    public static function authenticate(int $tenantId, string $username, string $password, string $license): ?self
    {
        $credential = self::where('tenant_id', $tenantId)
            ->where('pck_username', $username)
            ->where('pck_license', $license)
            ->where('is_enabled', true)
            ->first();

        if (!$credential) {
            return null;
        }

        // Check password
        if ($credential->pck_password !== $password) {
            return null;
        }

        // Update last seen
        $credential->update(['last_seen_at' => now()]);

        return $credential;
    }

    /**
     * Check if IP address is whitelisted
     */
    public function isIpWhitelisted(string $ip): bool
    {
        if (empty($this->ip_whitelist)) {
            return true; // No whitelist means all IPs allowed
        }

        foreach ($this->ip_whitelist as $allowedIp) {
            if ($this->ipMatches($ip, $allowedIp)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if IP matches pattern (supports CIDR notation)
     */
    private function ipMatches(string $ip, string $pattern): bool
    {
        if ($ip === $pattern) {
            return true;
        }

        // Simple CIDR support
        if (str_contains($pattern, '/')) {
            [$subnet, $mask] = explode('/', $pattern);
            return (ip2long($ip) & ~((1 << (32 - $mask)) - 1)) === ip2long($subnet);
        }

        return false;
    }

    /**
     * Find credential by tenant key
     */
    public static function findByTenantKey(string $tenantKey): ?self
    {
        // Try to find by tenant_id directly if numeric
        if (is_numeric($tenantKey)) {
            return self::where('tenant_id', (int)$tenantKey)
                ->where('is_enabled', true)
                ->first();
        }

        // Try to find by username mapping
        $userMapping = [
            'steinkjer' => 12,
            'namsos' => 7,
            'lade' => 4,
            'moan' => 6,
            'gramyra' => 5,
            'frosta' => 10,
            'hell' => 11,
        ];

        $tenantId = $userMapping[$tenantKey] ?? null;
        if ($tenantId) {
            return self::where('tenant_id', $tenantId)
                ->where('is_enabled', true)
                ->first();
        }

        return null;
    }
}