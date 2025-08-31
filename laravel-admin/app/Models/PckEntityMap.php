<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PckEntityMap extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'pck_article_id',
        'pck_variant_id',
        'woo_product_id',
        'woo_variation_id',
        'last_timestamp',
        'last_hash',
    ];

    protected function casts(): array
    {
        return [
            'tenant_id' => 'integer',
            'woo_product_id' => 'integer',
            'woo_variation_id' => 'integer',
            'last_timestamp' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Get the tenant/location this mapping belongs to
     */
    public function avdeling(): BelongsTo
    {
        return $this->belongsTo(Avdeling::class, 'tenant_id', 'siteid');
    }

    /**
     * Get the PCK credential for this tenant
     */
    public function pckCredential(): BelongsTo
    {
        return $this->belongsTo(PckCredential::class, 'tenant_id', 'tenant_id');
    }

    /**
     * Find mapping by PCK article ID
     */
    public static function findByPckArticle(int $tenantId, string $articleId, ?string $variantId = null): ?self
    {
        return self::where('tenant_id', $tenantId)
            ->where('pck_article_id', $articleId)
            ->where('pck_variant_id', $variantId)
            ->first();
    }

    /**
     * Find mapping by WooCommerce product ID
     */
    public static function findByWooProduct(int $tenantId, int $productId, ?int $variationId = null): ?self
    {
        $query = self::where('tenant_id', $tenantId)
            ->where('woo_product_id', $productId);

        if ($variationId) {
            $query->where('woo_variation_id', $variationId);
        } else {
            $query->whereNull('woo_variation_id');
        }

        return $query->first();
    }

    /**
     * Create or update mapping
     */
    public static function updateOrCreateMapping(
        int $tenantId,
        string $pckArticleId,
        ?string $pckVariantId = null,
        ?int $wooProductId = null,
        ?int $wooVariationId = null,
        ?string $timestamp = null,
        ?string $hash = null
    ): self {
        return self::updateOrCreate(
            [
                'tenant_id' => $tenantId,
                'pck_article_id' => $pckArticleId,
                'pck_variant_id' => $pckVariantId,
            ],
            [
                'woo_product_id' => $wooProductId,
                'woo_variation_id' => $wooVariationId,
                'last_timestamp' => $timestamp ? now()->setTimestamp($timestamp) : null,
                'last_hash' => $hash,
            ]
        );
    }

    /**
     * Check if this mapping should be ignored based on timestamp
     */
    public function shouldIgnoreUpdate(?string $incomingTimestamp): bool
    {
        if (!$this->last_timestamp || !$incomingTimestamp) {
            return false;
        }

        $incoming = (int)$incomingTimestamp;
        $existing = $this->last_timestamp->getTimestamp();

        return $incoming <= $existing;
    }

    /**
     * Generate content hash for idempotency
     */
    public static function generateHash(array $data): string
    {
        // Remove timestamp and other metadata for consistent hashing
        $filteredData = array_filter($data, function ($key) {
            return !in_array($key, ['timestamp', 'login', 'password']);
        }, ARRAY_FILTER_USE_KEY);

        return hash('sha256', json_encode($filteredData, 64)); // JSON_SORT_KEYS = 64
    }

    /**
     * Get all mappings for tenant with WooCommerce product data
     */
    public static function getWooProductsForTenant(int $tenantId): \Illuminate\Database\Eloquent\Collection
    {
        return self::where('tenant_id', $tenantId)
            ->whereNotNull('woo_product_id')
            ->orderBy('woo_product_id')
            ->get();
    }

    /**
     * Remove mapping by PCK article ID
     */
    public static function removeByPckArticle(int $tenantId, string $articleId): int
    {
        return self::where('tenant_id', $tenantId)
            ->where('pck_article_id', $articleId)
            ->delete();
    }
}