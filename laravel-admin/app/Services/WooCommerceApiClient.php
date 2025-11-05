<?php

namespace App\Services;

use App\Models\Location;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class WooCommerceApiClient
{
    protected $baseUrl;

    protected $consumerKey;

    protected $consumerSecret;

    public function __construct()
    {
        $this->baseUrl = config('services.woocommerce.base_url', 'https://aroiasia.no');
        $this->consumerKey = config('services.woocommerce.consumer_key');
        $this->consumerSecret = config('services.woocommerce.consumer_secret');
    }

    /**
     * Get products for a specific location
     */
    public function getProductsForLocation($siteId)
    {
        // Map site ID to WooCommerce site URL
        $siteUrl = $this->getSiteUrl($siteId);

        // Cache key for products
        $cacheKey = "woocommerce_products_{$siteId}";

        // Try to get from cache first
        return Cache::remember($cacheKey, 3600, function () use ($siteUrl) {
            $response = Http::withBasicAuth($this->consumerKey, $this->consumerSecret)
                ->get("{$siteUrl}/wp-json/wc/v3/products", [
                    'per_page' => 100,
                    'status' => 'publish',
                    'orderby' => 'menu_order',
                    'order' => 'asc',
                ]);

            if ($response->successful()) {
                $products = $response->json();

                // Format products for catering
                return collect($products)->map(function ($product) {
                    return [
                        'id' => $product['id'],
                        'name' => $product['name'],
                        'price' => $product['price'],
                        'description' => strip_tags($product['short_description'] ?? $product['description'] ?? ''),
                        'image' => $product['images'][0]['src'] ?? null,
                        'categories' => collect($product['categories'] ?? [])->pluck('name')->toArray(),
                        'sku' => $product['sku'] ?? '',
                        'in_stock' => $product['in_stock'] ?? true,
                    ];
                })->filter(function ($product) {
                    // Filter out products that shouldn't be available for catering
                    // You can add custom logic here
                    return $product['in_stock'];
                })->values()->toArray();
            }

            return [];
        });
    }

    /**
     * Get single product details
     */
    public function getProduct($siteId, $productId)
    {
        $siteUrl = $this->getSiteUrl($siteId);

        $response = Http::withBasicAuth($this->consumerKey, $this->consumerSecret)
            ->get("{$siteUrl}/wp-json/wc/v3/products/{$productId}");

        if ($response->successful()) {
            return $response->json();
        }

        return null;
    }

    /**
     * Get product categories for a location
     */
    public function getCategories($siteId)
    {
        $siteUrl = $this->getSiteUrl($siteId);
        $cacheKey = "woocommerce_categories_{$siteId}";

        return Cache::remember($cacheKey, 3600, function () use ($siteUrl) {
            $response = Http::withBasicAuth($this->consumerKey, $this->consumerSecret)
                ->get("{$siteUrl}/wp-json/wc/v3/products/categories", [
                    'per_page' => 100,
                    'orderby' => 'menu_order',
                    'order' => 'asc',
                ]);

            if ($response->successful()) {
                return $response->json();
            }

            return [];
        });
    }

    /**
     * Map site ID to WooCommerce site URL
     */
    protected function getSiteUrl($siteId)
    {
        // Get location to find the order URL
        $location = Location::where('site_id', $siteId)->first();

        if ($location && $location->order_url) {
            // Extract base URL from order URL
            $parsed = parse_url($location->order_url);

            return $parsed['scheme'].'://'.$parsed['host'];
        }

        // Fallback mapping
        $siteMapping = [
            7 => 'https://namsos.aroiasia.no',
            4 => 'https://lade.aroiasia.no',
            6 => 'https://moan.aroiasia.no',
            5 => 'https://gramyra.aroiasia.no',
            10 => 'https://frosta.aroiasia.no',
            11 => 'https://hell.aroiasia.no',
            12 => 'https://steinkjer.aroiasia.no',
        ];

        return $siteMapping[$siteId] ?? $this->baseUrl;
    }

    /**
     * Clear cache for a specific site
     */
    public function clearCache($siteId)
    {
        Cache::forget("woocommerce_products_{$siteId}");
        Cache::forget("woocommerce_categories_{$siteId}");
    }
}
