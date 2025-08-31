<?php

namespace App\Services\Woo;

use App\Tenancy\TenantContext;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
use Psr\Http\Message\ResponseInterface;

class WooCommerceService
{
    private TenantContext $tenant;
    private Client $httpClient;
    private string $baseUrl;
    private array $auth;

    public function __construct(TenantContext $tenant)
    {
        $this->tenant = $tenant;
        $config = $tenant->getWooCommerceConfig();
        
        $this->baseUrl = rtrim($config['base_url'], '/') . '/wp-json/wc/v3';
        $this->auth = [$config['consumer_key'], $config['consumer_secret']];
        
        $this->httpClient = new Client([
            'timeout' => 30,
            'connect_timeout' => 10,
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'User-Agent' => 'Aroi-PCK-Integration/1.0',
            ],
        ]);
    }

    /**
     * Create a new product in WooCommerce
     */
    public function createProduct(array $productData): array
    {
        $response = $this->makeRequest('POST', '/products', $productData);
        
        Log::info('WooCommerce: Product created', [
            'tenant_id' => $this->tenant->getTenantId(),
            'product_id' => $response['id'],
            'name' => $response['name'],
            'sku' => $response['sku'],
        ]);

        return $response;
    }

    /**
     * Update an existing product in WooCommerce
     */
    public function updateProduct(int $productId, array $productData): array
    {
        $response = $this->makeRequest('PUT', "/products/{$productId}", $productData);
        
        Log::info('WooCommerce: Product updated', [
            'tenant_id' => $this->tenant->getTenantId(),
            'product_id' => $productId,
            'name' => $response['name'],
        ]);

        return $response;
    }

    /**
     * Get a product from WooCommerce
     */
    public function getProduct(int $productId): array
    {
        return $this->makeRequest('GET', "/products/{$productId}");
    }

    /**
     * Delete a product from WooCommerce
     */
    public function deleteProduct(int $productId): array
    {
        $response = $this->makeRequest('DELETE', "/products/{$productId}", ['force' => true]);
        
        Log::info('WooCommerce: Product deleted', [
            'tenant_id' => $this->tenant->getTenantId(),
            'product_id' => $productId,
        ]);

        return $response;
    }

    /**
     * Create a product variation
     */
    public function createVariation(int $productId, array $variationData): array
    {
        $response = $this->makeRequest('POST', "/products/{$productId}/variations", $variationData);
        
        Log::info('WooCommerce: Product variation created', [
            'tenant_id' => $this->tenant->getTenantId(),
            'product_id' => $productId,
            'variation_id' => $response['id'],
        ]);

        return $response;
    }

    /**
     * Update a product variation
     */
    public function updateVariation(int $productId, int $variationId, array $variationData): array
    {
        $response = $this->makeRequest('PUT', "/products/{$productId}/variations/{$variationId}", $variationData);
        
        Log::info('WooCommerce: Product variation updated', [
            'tenant_id' => $this->tenant->getTenantId(),
            'product_id' => $productId,
            'variation_id' => $variationId,
        ]);

        return $response;
    }

    /**
     * Get product variations
     */
    public function getVariations(int $productId): array
    {
        return $this->makeRequest('GET', "/products/{$productId}/variations");
    }

    /**
     * Create or update product attribute
     */
    public function createOrUpdateAttribute(array $attributeData): array
    {
        // Try to find existing attribute by name
        $existingAttributes = $this->makeRequest('GET', '/products/attributes');
        
        foreach ($existingAttributes as $attr) {
            if ($attr['slug'] === $attributeData['slug']) {
                // Update existing attribute
                return $this->makeRequest('PUT', "/products/attributes/{$attr['id']}", $attributeData);
            }
        }
        
        // Create new attribute
        return $this->makeRequest('POST', '/products/attributes', $attributeData);
    }

    /**
     * Create or update attribute term
     */
    public function createOrUpdateAttributeTerm(int $attributeId, array $termData): array
    {
        // Try to find existing term by name
        $existingTerms = $this->makeRequest('GET', "/products/attributes/{$attributeId}/terms");
        
        foreach ($existingTerms as $term) {
            if ($term['slug'] === $termData['slug']) {
                // Update existing term
                return $this->makeRequest('PUT', "/products/attributes/{$attributeId}/terms/{$term['id']}", $termData);
            }
        }
        
        // Create new term
        return $this->makeRequest('POST', "/products/attributes/{$attributeId}/terms", $termData);
    }

    /**
     * Get orders with filters
     */
    public function getOrders(array $params = []): array
    {
        $defaultParams = [
            'per_page' => 100,
            'orderby' => 'date',
            'order' => 'desc',
        ];
        
        $queryParams = array_merge($defaultParams, $params);
        $queryString = http_build_query($queryParams);
        
        return $this->makeRequest('GET', "/orders?{$queryString}");
    }

    /**
     * Update order status
     */
    public function updateOrderStatus(int $orderId, string $status, ?string $note = null): array
    {
        $data = ['status' => $status];
        
        if ($note) {
            $data['customer_note'] = $note;
        }
        
        $response = $this->makeRequest('PUT', "/orders/{$orderId}", $data);
        
        Log::info('WooCommerce: Order status updated', [
            'tenant_id' => $this->tenant->getTenantId(),
            'order_id' => $orderId,
            'status' => $status,
            'note' => $note,
        ]);

        return $response;
    }

    /**
     * Create refund for order
     */
    public function createRefund(int $orderId, array $refundData): array
    {
        $response = $this->makeRequest('POST', "/orders/{$orderId}/refunds", $refundData);
        
        Log::info('WooCommerce: Refund created', [
            'tenant_id' => $this->tenant->getTenantId(),
            'order_id' => $orderId,
            'refund_id' => $response['id'],
            'amount' => $response['amount'],
        ]);

        return $response;
    }

    /**
     * Get product categories
     */
    public function getCategories(array $params = []): array
    {
        $defaultParams = ['per_page' => 100];
        $queryParams = array_merge($defaultParams, $params);
        $queryString = http_build_query($queryParams);
        
        return $this->makeRequest('GET', "/products/categories?{$queryString}");
    }

    /**
     * Create product category
     */
    public function createCategory(array $categoryData): array
    {
        $response = $this->makeRequest('POST', '/products/categories', $categoryData);
        
        Log::info('WooCommerce: Category created', [
            'tenant_id' => $this->tenant->getTenantId(),
            'category_id' => $response['id'],
            'name' => $response['name'],
        ]);

        return $response;
    }

    /**
     * Batch update/create products
     */
    public function batchProducts(array $batchData): array
    {
        $response = $this->makeRequest('POST', '/products/batch', $batchData);
        
        Log::info('WooCommerce: Batch products processed', [
            'tenant_id' => $this->tenant->getTenantId(),
            'create_count' => count($batchData['create'] ?? []),
            'update_count' => count($batchData['update'] ?? []),
            'delete_count' => count($batchData['delete'] ?? []),
        ]);

        return $response;
    }

    /**
     * Search products
     */
    public function searchProducts(string $search, array $params = []): array
    {
        $defaultParams = [
            'search' => $search,
            'per_page' => 100,
        ];
        
        $queryParams = array_merge($defaultParams, $params);
        $queryString = http_build_query($queryParams);
        
        return $this->makeRequest('GET', "/products?{$queryString}");
    }

    /**
     * Find product by SKU
     */
    public function findProductBySku(string $sku): ?array
    {
        $products = $this->searchProducts('', ['sku' => $sku]);
        
        foreach ($products as $product) {
            if ($product['sku'] === $sku) {
                return $product;
            }
        }
        
        return null;
    }

    /**
     * Find product by PCK article ID
     */
    public function findProductByPckArticleId(string $pckArticleId): ?array
    {
        // Search by meta data
        $products = $this->makeRequest('GET', '/products', [
            'meta_key' => '_pck_article_id',
            'meta_value' => $pckArticleId,
            'per_page' => 1,
        ]);
        
        return $products[0] ?? null;
    }

    /**
     * Make HTTP request to WooCommerce API
     */
    private function makeRequest(string $method, string $endpoint, array $data = []): array
    {
        $url = $this->baseUrl . $endpoint;
        
        $options = [
            'auth' => $this->auth,
            'verify' => false, // You may want to enable SSL verification in production
        ];
        
        if (!empty($data) && in_array($method, ['POST', 'PUT', 'PATCH'])) {
            $options['json'] = $data;
        }

        try {
            $startTime = microtime(true);
            $response = $this->httpClient->request($method, $url, $options);
            $duration = microtime(true) - $startTime;
            
            $statusCode = $response->getStatusCode();
            $body = $this->parseResponse($response);
            
            // Log successful requests (except GET requests to reduce noise)
            if ($method !== 'GET') {
                Log::debug('WooCommerce API request', [
                    'tenant_id' => $this->tenant->getTenantId(),
                    'method' => $method,
                    'endpoint' => $endpoint,
                    'status_code' => $statusCode,
                    'duration_ms' => round($duration * 1000, 2),
                ]);
            }
            
            return $body;
            
        } catch (GuzzleException $e) {
            $this->logRequestError($method, $endpoint, $e, $data);
            
            // Re-throw with more context
            throw new \RuntimeException(
                "WooCommerce API request failed: {$method} {$endpoint} - " . $e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * Parse HTTP response
     */
    private function parseResponse(ResponseInterface $response): array
    {
        $body = $response->getBody()->getContents();
        
        if (empty($body)) {
            return [];
        }
        
        $decoded = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Invalid JSON response from WooCommerce API');
        }
        
        return $decoded;
    }

    /**
     * Log request errors
     */
    private function logRequestError(string $method, string $endpoint, GuzzleException $e, array $data = []): void
    {
        $context = [
            'tenant_id' => $this->tenant->getTenantId(),
            'method' => $method,
            'endpoint' => $endpoint,
            'error' => $e->getMessage(),
            'code' => $e->getCode(),
        ];
        
        // Include response body if available
        if ($e->hasResponse()) {
            $response = $e->getResponse();
            $context['status_code'] = $response->getStatusCode();
            $context['response_body'] = $response->getBody()->getContents();
        }
        
        // Include request data for POST/PUT requests (but mask sensitive data)
        if (!empty($data) && in_array($method, ['POST', 'PUT', 'PATCH'])) {
            $context['request_data'] = $this->maskSensitiveData($data);
        }
        
        Log::error('WooCommerce API request failed', $context);
    }

    /**
     * Mask sensitive data in logs
     */
    private function maskSensitiveData(array $data): array
    {
        $sensitiveKeys = ['password', 'token', 'key', 'secret'];
        
        foreach ($data as $key => $value) {
            if (is_string($key) && in_array(strtolower($key), $sensitiveKeys)) {
                $data[$key] = '***masked***';
            } elseif (is_array($value)) {
                $data[$key] = $this->maskSensitiveData($value);
            }
        }
        
        return $data;
    }
}