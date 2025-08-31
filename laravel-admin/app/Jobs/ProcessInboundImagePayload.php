<?php

namespace App\Jobs;

use App\Models\PckEntityMap;
use App\Models\PckInboundPayload;
use App\Services\Woo\WooCommerceService;
use App\Services\Woo\WordPressMediaService;
use App\Tenancy\TenantResolver;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcessInboundImagePayload implements ShouldQueue
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
            Log::error('ProcessInboundImagePayload: Payload not found', [
                'payload_id' => $this->payloadId,
            ]);
            return;
        }

        try {
            $this->processImagePayload($payload);
            $payload->markProcessed();

        } catch (\Exception $e) {
            Log::error('ProcessInboundImagePayload: Processing failed', [
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

    private function processImagePayload(PckInboundPayload $payload): void
    {
        $data = $payload->payload;
        $imageData = $data['image'] ?? '';
        $articleId = (string)($data['articleid'] ?? '');
        $tenantId = $payload->tenant_id;

        // Resolve tenant context
        $tenant = TenantResolver::resolveFromTenantId($tenantId);
        if (!$tenant) {
            throw new \RuntimeException("Tenant {$tenantId} not found");
        }

        if (!$tenant->hasValidWooCommerceConfig()) {
            throw new \RuntimeException("Tenant {$tenantId} has invalid WooCommerce configuration");
        }

        // Handle special case: company logo (articleId = -10)
        if ($articleId === '-10') {
            $this->processCompanyLogo($tenant, $imageData);
            return;
        }

        // Handle empty image (deletion)
        if (empty($imageData)) {
            $this->removeProductImage($tenant, $articleId);
            return;
        }

        // Find the product mapping
        $mapping = PckEntityMap::findByPckArticle($tenantId, $articleId);
        if (!$mapping || !$mapping->woo_product_id) {
            Log::warning('ProcessInboundImagePayload: Product mapping not found', [
                'payload_id' => $this->payloadId,
                'tenant_id' => $tenantId,
                'article_id' => $articleId,
            ]);
            throw new \RuntimeException("Product mapping not found for article {$articleId}");
        }

        // Process the image
        $this->uploadProductImage($tenant, $mapping, $imageData);
    }

    private function processCompanyLogo(TenantContext $tenant, string $imageData): void
    {
        Log::info('ProcessInboundImagePayload: Processing company logo', [
            'tenant_id' => $tenant->getTenantId(),
            'image_size' => strlen($imageData),
        ]);

        if (empty($imageData)) {
            // TODO: Remove company logo
            return;
        }

        try {
            $mediaService = new WordPressMediaService($tenant);
            $uploadResult = $mediaService->uploadImage($imageData, [
                'filename' => 'company-logo.jpg',
                'title' => 'Company Logo',
                'alt_text' => $tenant->getTenantName() . ' Logo',
            ]);

            Log::info('ProcessInboundImagePayload: Company logo uploaded', [
                'tenant_id' => $tenant->getTenantId(),
                'media_id' => $uploadResult['id'],
                'url' => $uploadResult['source_url'],
            ]);

            // TODO: Set as site logo in WordPress theme customizer
            // This would require additional WordPress REST API calls

        } catch (\Exception $e) {
            Log::error('ProcessInboundImagePayload: Company logo upload failed', [
                'tenant_id' => $tenant->getTenantId(),
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    private function removeProductImage(TenantContext $tenant, string $articleId): void
    {
        $mapping = PckEntityMap::findByPckArticle($tenant->getTenantId(), $articleId);
        if (!$mapping || !$mapping->woo_product_id) {
            return;
        }

        try {
            $wooService = new WooCommerceService($tenant);
            
            // Get current product to check for existing images
            $product = $wooService->getProduct($mapping->woo_product_id);
            
            if (!empty($product['images'])) {
                // Remove all images from the product
                $wooService->updateProduct($mapping->woo_product_id, [
                    'images' => [],
                ]);

                Log::info('ProcessInboundImagePayload: Product images removed', [
                    'tenant_id' => $tenant->getTenantId(),
                    'article_id' => $articleId,
                    'woo_product_id' => $mapping->woo_product_id,
                    'removed_count' => count($product['images']),
                ]);
            }

        } catch (\Exception $e) {
            Log::error('ProcessInboundImagePayload: Image removal failed', [
                'tenant_id' => $tenant->getTenantId(),
                'article_id' => $articleId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    private function uploadProductImage(TenantContext $tenant, PckEntityMap $mapping, string $imageData): void
    {
        try {
            // Upload image to WordPress media library
            $mediaService = new WordPressMediaService($tenant);
            $uploadResult = $mediaService->uploadImage($imageData, [
                'filename' => "product-{$mapping->pck_article_id}.jpg",
                'title' => "Product {$mapping->pck_article_id} Image",
                'alt_text' => "Product {$mapping->pck_article_id}",
            ]);

            // Attach image to WooCommerce product
            $wooService = new WooCommerceService($tenant);
            $currentProduct = $wooService->getProduct($mapping->woo_product_id);
            
            $images = $currentProduct['images'] ?? [];
            
            // Add new image (replace existing or add as first image)
            $newImage = [
                'id' => $uploadResult['id'],
                'src' => $uploadResult['source_url'],
                'name' => $uploadResult['title']['rendered'] ?? '',
                'alt' => $uploadResult['alt_text'] ?? '',
            ];

            // Replace existing images with the new one (PCK typically sends single main image)
            $images = [$newImage];

            $wooService->updateProduct($mapping->woo_product_id, [
                'images' => $images,
            ]);

            Log::info('ProcessInboundImagePayload: Product image uploaded and attached', [
                'tenant_id' => $tenant->getTenantId(),
                'article_id' => $mapping->pck_article_id,
                'woo_product_id' => $mapping->woo_product_id,
                'media_id' => $uploadResult['id'],
                'url' => $uploadResult['source_url'],
            ]);

        } catch (\Exception $e) {
            Log::error('ProcessInboundImagePayload: Product image upload failed', [
                'tenant_id' => $tenant->getTenantId(),
                'article_id' => $mapping->pck_article_id,
                'woo_product_id' => $mapping->woo_product_id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessInboundImagePayload: Job failed permanently', [
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