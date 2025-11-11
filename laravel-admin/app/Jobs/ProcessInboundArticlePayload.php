<?php

namespace App\Jobs;

use App\Models\PckEntityMap;
use App\Models\PckInboundPayload;
use App\Services\Woo\WooCommerceService;
use App\Tenancy\TenantResolver;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessInboundArticlePayload implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $payloadId;

    public int $tries = 3;

    public int $maxExceptions = 2;

    public function __construct(int $payloadId)
    {
        $this->payloadId = $payloadId;
        $this->onQueue('pck-inbound');
    }

    public function handle(): void
    {
        $payload = PckInboundPayload::find($this->payloadId);

        if (! $payload) {
            Log::error('ProcessInboundArticlePayload: Payload not found', [
                'payload_id' => $this->payloadId,
            ]);

            return;
        }

        try {
            $this->processArticlePayload($payload);
            $payload->markProcessed();

        } catch (\Exception $e) {
            Log::error('ProcessInboundArticlePayload: Processing failed', [
                'payload_id' => $this->payloadId,
                'tenant_id' => $payload->tenant_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $payload->markFailed($e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'attempt' => $this->attempts(),
            ]);

            throw $e;
        }
    }

    private function processArticlePayload(PckInboundPayload $payload): void
    {
        $data = $payload->payload;
        $article = $data['article'] ?? [];
        $tenantId = $payload->tenant_id;

        // Resolve tenant context
        $tenant = TenantResolver::resolveFromTenantId($tenantId);
        if (! $tenant) {
            throw new \RuntimeException("Tenant {$tenantId} not found");
        }

        if (! $tenant->hasValidWooCommerceConfig()) {
            throw new \RuntimeException("Tenant {$tenantId} has invalid WooCommerce configuration");
        }

        // Check if we should ignore this update based on timestamp
        $articleId = (string) ($article['articleId'] ?? '');
        $timestamp = $article['timestamp'] ?? null;

        if (empty($articleId)) {
            throw new \RuntimeException('Article ID is required');
        }

        // Find or create entity mapping
        $mapping = PckEntityMap::findByPckArticle($tenantId, $articleId);

        if ($mapping && $mapping->shouldIgnoreUpdate($timestamp)) {
            Log::info('ProcessInboundArticlePayload: Ignoring older update', [
                'payload_id' => $this->payloadId,
                'tenant_id' => $tenantId,
                'article_id' => $articleId,
                'incoming_timestamp' => $timestamp,
                'existing_timestamp' => $mapping->last_timestamp?->getTimestamp(),
            ]);

            return;
        }

        // Process the article data
        $processedData = $this->normalizeArticleData($article);

        // Generate content hash for idempotency
        $contentHash = PckEntityMap::generateHash($article);

        // Check if content has actually changed
        if ($mapping && $mapping->last_hash === $contentHash) {
            Log::info('ProcessInboundArticlePayload: No content changes detected', [
                'payload_id' => $this->payloadId,
                'tenant_id' => $tenantId,
                'article_id' => $articleId,
            ]);

            // Update timestamp but don't sync to WooCommerce
            $mapping->update([
                'last_timestamp' => $timestamp ? now()->setTimestamp($timestamp) : now(),
            ]);

            return;
        }

        // Sync to WooCommerce
        $wooService = new WooCommerceService($tenant);

        try {
            if ($mapping && $mapping->woo_product_id) {
                // Update existing product
                $wooProduct = $wooService->updateProduct($mapping->woo_product_id, $processedData);
                Log::info('ProcessInboundArticlePayload: Product updated in WooCommerce', [
                    'payload_id' => $this->payloadId,
                    'tenant_id' => $tenantId,
                    'article_id' => $articleId,
                    'woo_product_id' => $mapping->woo_product_id,
                ]);
            } else {
                // Create new product
                $wooProduct = $wooService->createProduct($processedData);
                Log::info('ProcessInboundArticlePayload: Product created in WooCommerce', [
                    'payload_id' => $this->payloadId,
                    'tenant_id' => $tenantId,
                    'article_id' => $articleId,
                    'woo_product_id' => $wooProduct['id'],
                ]);
            }

            // Update or create mapping
            PckEntityMap::updateOrCreateMapping(
                $tenantId,
                $articleId,
                null, // No variant for main product
                $wooProduct['id'],
                null, // No variation for main product
                $timestamp,
                $contentHash
            );

        } catch (\Exception $e) {
            Log::error('ProcessInboundArticlePayload: WooCommerce sync failed', [
                'payload_id' => $this->payloadId,
                'tenant_id' => $tenantId,
                'article_id' => $articleId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    private function normalizeArticleData(array $article): array
    {
        $name = $article['name'] ?? "Product {$article['articleId']}";
        $description = $article['description'] ?? '';
        $price = (float) ($article['salesPrice'] ?? 0);
        $stockCount = (int) ($article['stockCount'] ?? 0);
        $sku = $article['articleNo'] ?? $article['articleId'];
        $visible = (bool) ($article['visibleOnWeb'] ?? true);

        return [
            'name' => $name,
            'description' => $description,
            'short_description' => $description,
            'sku' => $sku,
            'regular_price' => number_format($price, 2, '.', ''),
            'manage_stock' => true,
            'stock_quantity' => $stockCount,
            'stock_status' => $stockCount > 0 ? 'instock' : 'outofstock',
            'status' => $visible ? 'publish' : 'draft',
            'type' => 'simple',
            'catalog_visibility' => $visible ? 'visible' : 'hidden',
            'meta_data' => [
                [
                    'key' => '_pck_article_id',
                    'value' => $article['articleId'],
                ],
                [
                    'key' => '_pck_timestamp',
                    'value' => $article['timestamp'] ?? time(),
                ],
            ],
        ];
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessInboundArticlePayload: Job failed permanently', [
            'payload_id' => $this->payloadId,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);

        $payload = PckInboundPayload::find($this->payloadId);
        if ($payload) {
            $payload->markFailed('Job failed after maximum attempts', [
                'final_error' => $exception->getMessage(),
                'attempts' => $this->attempts(),
            ]);
        }
    }
}
