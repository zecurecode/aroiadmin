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

class ProcessStockUpdatePayload implements ShouldQueue
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
        
        if (!$payload) {
            Log::error('ProcessStockUpdatePayload: Payload not found', [
                'payload_id' => $this->payloadId,
            ]);
            return;
        }

        try {
            $this->processStockPayload($payload);
            $payload->markProcessed();

        } catch (\Exception $e) {
            Log::error('ProcessStockUpdatePayload: Processing failed', [
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

    private function processStockPayload(PckInboundPayload $payload): void
    {
        $data = $payload->payload;
        $updateStock = $data['updateStock'] ?? [];
        $tenantId = $payload->tenant_id;

        // Resolve tenant context
        $tenant = TenantResolver::resolveFromTenantId($tenantId);
        if (!$tenant) {
            throw new \RuntimeException("Tenant {$tenantId} not found");
        }

        if (!$tenant->hasValidWooCommerceConfig()) {
            throw new \RuntimeException("Tenant {$tenantId} has invalid WooCommerce configuration");
        }

        $articleId = (string)($updateStock['articleId'] ?? '');
        $stockCount = (int)($updateStock['count'] ?? 0);
        $sizeColorId = $updateStock['sizeColorId'] ?? null;
        $timestamp = $updateStock['timestamp'] ?? null;

        if (empty($articleId)) {
            throw new \RuntimeException('Article ID is required for stock update');
        }

        // Find the product mapping
        $mapping = PckEntityMap::findByPckArticle($tenantId, $articleId);
        if (!$mapping || !$mapping->woo_product_id) {
            Log::warning('ProcessStockUpdatePayload: Product mapping not found', [
                'payload_id' => $this->payloadId,
                'tenant_id' => $tenantId,
                'article_id' => $articleId,
            ]);
            throw new \RuntimeException("Product mapping not found for article {$articleId}");
        }

        // Check if we should ignore this update based on timestamp
        if ($mapping->shouldIgnoreUpdate($timestamp)) {
            Log::info('ProcessStockUpdatePayload: Ignoring older stock update', [
                'payload_id' => $this->payloadId,
                'tenant_id' => $tenantId,
                'article_id' => $articleId,
                'incoming_timestamp' => $timestamp,
                'existing_timestamp' => $mapping->last_timestamp?->getTimestamp(),
            ]);
            return;
        }

        // Update stock in WooCommerce
        $this->updateWooCommerceStock($tenant, $mapping, $stockCount, $sizeColorId);

        // Update mapping timestamp
        $mapping->update([
            'last_timestamp' => $timestamp ? now()->setTimestamp($timestamp) : now(),
        ]);

        Log::info('ProcessStockUpdatePayload: Stock updated successfully', [
            'payload_id' => $this->payloadId,
            'tenant_id' => $tenantId,
            'article_id' => $articleId,
            'stock_count' => $stockCount,
            'size_color_id' => $sizeColorId,
            'woo_product_id' => $mapping->woo_product_id,
        ]);
    }

    private function updateWooCommerceStock(TenantContext $tenant, PckEntityMap $mapping, int $stockCount, $sizeColorId): void
    {
        $wooService = new WooCommerceService($tenant);

        try {
            if ($sizeColorId) {
                // Update variation stock if this is a variant
                $this->updateVariationStock($wooService, $tenant, $mapping, $stockCount, $sizeColorId);
            } else {
                // Update main product stock
                $this->updateProductStock($wooService, $mapping, $stockCount);
            }

        } catch (\Exception $e) {
            Log::error('ProcessStockUpdatePayload: WooCommerce stock update failed', [
                'tenant_id' => $tenant->getTenantId(),
                'article_id' => $mapping->pck_article_id,
                'woo_product_id' => $mapping->woo_product_id,
                'size_color_id' => $sizeColorId,
                'stock_count' => $stockCount,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    private function updateProductStock(WooCommerceService $wooService, PckEntityMap $mapping, int $stockCount): void
    {
        $stockStatus = $stockCount > 0 ? 'instock' : 'outofstock';
        
        $updateData = [
            'manage_stock' => true,
            'stock_quantity' => $stockCount,
            'stock_status' => $stockStatus,
            'meta_data' => [
                [
                    'key' => '_pck_last_stock_update',
                    'value' => now()->toISOString(),
                ],
            ],
        ];

        $wooService->updateProduct($mapping->woo_product_id, $updateData);

        Log::info('ProcessStockUpdatePayload: Product stock updated', [
            'woo_product_id' => $mapping->woo_product_id,
            'stock_count' => $stockCount,
            'stock_status' => $stockStatus,
        ]);
    }

    private function updateVariationStock(WooCommerceService $wooService, TenantContext $tenant, PckEntityMap $mapping, int $stockCount, $sizeColorId): void
    {
        // Find variation mapping by size/color ID
        $variationMapping = PckEntityMap::where('tenant_id', $tenant->getTenantId())
            ->where('pck_article_id', $mapping->pck_article_id)
            ->where('pck_variant_id', (string)$sizeColorId)
            ->first();

        if (!$variationMapping || !$variationMapping->woo_variation_id) {
            Log::warning('ProcessStockUpdatePayload: Variation mapping not found', [
                'tenant_id' => $tenant->getTenantId(),
                'article_id' => $mapping->pck_article_id,
                'size_color_id' => $sizeColorId,
            ]);
            
            // Fall back to updating main product stock
            $this->updateProductStock($wooService, $mapping, $stockCount);
            return;
        }

        $stockStatus = $stockCount > 0 ? 'instock' : 'outofstock';
        
        $updateData = [
            'manage_stock' => true,
            'stock_quantity' => $stockCount,
            'stock_status' => $stockStatus,
            'meta_data' => [
                [
                    'key' => '_pck_last_stock_update',
                    'value' => now()->toISOString(),
                ],
                [
                    'key' => '_pck_size_color_id',
                    'value' => $sizeColorId,
                ],
            ],
        ];

        $wooService->updateVariation($mapping->woo_product_id, $variationMapping->woo_variation_id, $updateData);

        Log::info('ProcessStockUpdatePayload: Variation stock updated', [
            'woo_product_id' => $mapping->woo_product_id,
            'woo_variation_id' => $variationMapping->woo_variation_id,
            'size_color_id' => $sizeColorId,
            'stock_count' => $stockCount,
            'stock_status' => $stockStatus,
        ]);

        // Also update the variation mapping timestamp
        $variationMapping->update([
            'last_timestamp' => now(),
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessStockUpdatePayload: Job failed permanently', [
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