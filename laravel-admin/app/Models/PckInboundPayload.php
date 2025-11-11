<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PckInboundPayload extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'method',
        'idempotency_key',
        'payload',
        'status',
        'received_at',
        'processed_at',
        'error',
    ];

    protected function casts(): array
    {
        return [
            'tenant_id' => 'integer',
            'payload' => 'array',
            'error' => 'array',
            'received_at' => 'datetime',
            'processed_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Status constants
     */
    const STATUS_RECEIVED = 'received';

    const STATUS_PROCESSED = 'processed';

    const STATUS_FAILED = 'failed';

    /**
     * Method constants
     */
    const METHOD_SEND_ARTICLE = 'sendArticle';

    const METHOD_SEND_PRODUCT_LINE = 'sendProductLine';

    const METHOD_SEND_IMAGE = 'sendImage';

    const METHOD_SEND_IMAGE_COLOR = 'sendImageColor';

    const METHOD_SEND_ARTICLE_GROUP = 'sendArticleGroup';

    const METHOD_SEND_MANUFACTURER = 'sendManufacturer';

    const METHOD_SEND_SIZE = 'sendSize';

    const METHOD_SEND_COLOR = 'sendColor';

    const METHOD_UPDATE_STOCK_COUNT = 'updateStockCount';

    const METHOD_REMOVE_ARTICLE = 'removeArticle';

    const METHOD_SEND_DISCOUNT = 'sendDiscount';

    const METHOD_SEND_CUSTOMER_INFO = 'sendCustomerInfo';

    /**
     * Get the tenant/location this payload belongs to
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
     * Generate idempotency key
     */
    public static function generateIdempotencyKey(
        int $tenantId,
        string $method,
        array $payload
    ): string {
        // Extract primary identifiers based on method
        $identifiers = self::extractPrimaryIdentifiers($method, $payload);

        // Create hash from tenant, method, identifiers, and timestamp
        $hashData = [
            'tenant_id' => $tenantId,
            'method' => $method,
            'identifiers' => $identifiers,
            'timestamp' => $payload['timestamp'] ?? time(),
        ];

        return hash('sha256', json_encode($hashData, 64)); // JSON_SORT_KEYS = 64
    }

    /**
     * Extract primary identifiers from payload based on method
     */
    private static function extractPrimaryIdentifiers(string $method, array $payload): array
    {
        return match ($method) {
            self::METHOD_SEND_ARTICLE => [
                'articleId' => $payload['article']['articleId'] ?? null,
            ],
            self::METHOD_SEND_IMAGE, self::METHOD_SEND_IMAGE_COLOR => [
                'articleId' => $payload['articleid'] ?? null,
                'colorId' => $payload['colorid'] ?? null,
                'imageId' => $payload['imageid'] ?? null,
            ],
            self::METHOD_UPDATE_STOCK_COUNT => [
                'articleId' => $payload['updateStock']['articleId'] ?? null,
                'sizeColorId' => $payload['updateStock']['sizeColorId'] ?? null,
            ],
            self::METHOD_REMOVE_ARTICLE => [
                'articleId' => $payload['articleid'] ?? null,
            ],
            self::METHOD_SEND_ARTICLE_GROUP => [
                'articleGroupId' => $payload['articleGroup']['articleGroupId'] ?? null,
                'groupNumber' => $payload['articleGroup']['groupNumber'] ?? null,
            ],
            self::METHOD_SEND_MANUFACTURER => [
                'manufacturerId' => $payload['manufacturer']['manufacturerId'] ?? null,
            ],
            self::METHOD_SEND_SIZE => [
                'sizeId' => $payload['size']['sizeId'] ?? null,
            ],
            self::METHOD_SEND_COLOR => [
                'colorId' => $payload['color']['colorId'] ?? null,
            ],
            self::METHOD_SEND_DISCOUNT => [
                'discountId' => $payload['discount']['discountId'] ?? null,
            ],
            self::METHOD_SEND_CUSTOMER_INFO => [
                'customerId' => $payload['customerInfo']['deltaCustomerId'] ?? null,
            ],
            default => []
        };
    }

    /**
     * Create or find existing payload by idempotency key
     */
    public static function findOrCreateIdempotent(
        int $tenantId,
        string $method,
        array $payload
    ): array {
        $idempotencyKey = self::generateIdempotencyKey($tenantId, $method, $payload);

        $existing = self::where('idempotency_key', $idempotencyKey)->first();

        if ($existing) {
            return [
                'payload' => $existing,
                'is_duplicate' => true,
            ];
        }

        $newPayload = self::create([
            'tenant_id' => $tenantId,
            'method' => $method,
            'idempotency_key' => $idempotencyKey,
            'payload' => $payload,
            'status' => self::STATUS_RECEIVED,
            'received_at' => now(),
        ]);

        return [
            'payload' => $newPayload,
            'is_duplicate' => false,
        ];
    }

    /**
     * Mark as processed
     */
    public function markProcessed(): void
    {
        $this->update([
            'status' => self::STATUS_PROCESSED,
            'processed_at' => now(),
            'error' => null,
        ]);
    }

    /**
     * Mark as failed with error
     */
    public function markFailed(string $errorMessage, array $errorDetails = []): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'processed_at' => now(),
            'error' => [
                'message' => $errorMessage,
                'details' => $errorDetails,
                'timestamp' => now()->toISOString(),
            ],
        ]);
    }

    /**
     * Get pending payloads for processing
     */
    public static function getPendingForProcessing(int $limit = 100): \Illuminate\Database\Eloquent\Collection
    {
        return self::where('status', self::STATUS_RECEIVED)
            ->orderBy('received_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Get failed payloads for retry
     */
    public static function getFailedForRetry(int $limit = 50): \Illuminate\Database\Eloquent\Collection
    {
        return self::where('status', self::STATUS_FAILED)
            ->where('processed_at', '<', now()->subMinutes(5)) // Wait 5 minutes before retry
            ->orderBy('processed_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Clean up old processed payloads (older than 30 days)
     */
    public static function cleanupOld(): int
    {
        return self::where('status', self::STATUS_PROCESSED)
            ->where('processed_at', '<', now()->subDays(30))
            ->delete();
    }
}
