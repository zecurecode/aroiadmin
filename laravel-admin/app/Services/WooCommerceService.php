<?php

namespace App\Services;

use App\Models\Site;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WooCommerceService
{
    private $baseUrl;
    private $consumerKey;
    private $consumerSecret;
    private $siteId;

    /**
     * Create a new class instance.
     *
     * @param int $siteId The site ID to fetch credentials for
     */
    public function __construct($siteId = null)
    {
        $this->siteId = $siteId;

        if ($siteId) {
            // Fetch credentials from database for this specific site
            $site = Site::where('site_id', $siteId)->first();

            if ($site) {
                $this->baseUrl = rtrim($site->url, '/');
                $this->consumerKey = $site->consumer_key;
                $this->consumerSecret = $site->consumer_secret;
            } else {
                Log::warning("WooCommerce: Site not found for site_id: {$siteId}");
                $this->baseUrl = null;
                $this->consumerKey = null;
                $this->consumerSecret = null;
            }
        } else {
            // Fallback to config (for global operations)
            $this->baseUrl = config('services.woocommerce.base_url');
            $this->consumerKey = config('services.woocommerce.consumer_key');
            $this->consumerSecret = config('services.woocommerce.consumer_secret');
        }
    }

    /**
     * Fetch order details from WooCommerce by order ID
     *
     * @param int $orderId WooCommerce order ID
     * @return array|null Order data including total amount
     */
    public function getOrder($orderId)
    {
        try {
            $url = "{$this->baseUrl}/wp-json/wc/v3/orders/{$orderId}";

            $response = Http::withBasicAuth($this->consumerKey, $this->consumerSecret)
                ->timeout(10)
                ->get($url);

            if ($response->successful()) {
                $data = $response->json();

                return [
                    'id' => $data['id'] ?? null,
                    'total' => $data['total'] ?? null,
                    'subtotal' => $data['subtotal'] ?? null,
                    'total_tax' => $data['total_tax'] ?? null,
                    'shipping_total' => $data['shipping_total'] ?? null,
                    'status' => $data['status'] ?? null,
                    'payment_method' => $data['payment_method'] ?? null,
                    'payment_method_title' => $data['payment_method_title'] ?? null,
                    'date_paid' => $data['date_paid'] ?? null,
                ];
            }

            Log::warning('WooCommerce API request failed', [
                'order_id' => $orderId,
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return null;

        } catch (\Exception $e) {
            Log::error('WooCommerce API exception', [
                'order_id' => $orderId,
                'error' => $e->getMessage()
            ]);

            return null;
        }
    }

    /**
     * Get order total amount by order ID
     *
     * @param int $orderId
     * @return float|null
     */
    public function getOrderTotal($orderId)
    {
        $order = $this->getOrder($orderId);

        return $order ? (float) $order['total'] : null;
    }

    /**
     * Batch fetch multiple order totals
     *
     * @param array $orderIds
     * @return array Associative array [orderId => total]
     */
    public function getOrderTotals(array $orderIds)
    {
        $totals = [];

        foreach ($orderIds as $orderId) {
            $totals[$orderId] = $this->getOrderTotal($orderId);
        }

        return $totals;
    }

    /**
     * Fetch orders from WooCommerce by criteria
     *
     * @param array $params Query parameters (status, per_page, after, before, etc.)
     * @return array|null List of orders
     */
    public function getOrders($params = [])
    {
        try {
            $url = "{$this->baseUrl}/wp-json/wc/v3/orders";

            $response = Http::withBasicAuth($this->consumerKey, $this->consumerSecret)
                ->timeout(30)
                ->get($url, $params);

            if ($response->successful()) {
                return $response->json();
            }

            Log::warning('WooCommerce API orders request failed', [
                'params' => $params,
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            return null;

        } catch (\Exception $e) {
            Log::error('WooCommerce API orders exception', [
                'params' => $params,
                'error' => $e->getMessage()
            ]);

            return null;
        }
    }

    /**
     * Get orders by status for a specific location (using meta data)
     *
     * @param string $status Order status (pending, processing, completed, etc.)
     * @param int|null $siteId Site ID to filter by (searches in order meta)
     * @param array $params Additional params (after, before, per_page, etc.)
     * @return array|null
     */
    public function getOrdersByStatus($status, $siteId = null, $params = [])
    {
        $queryParams = array_merge([
            'status' => $status,
            'per_page' => 100,
        ], $params);

        $orders = $this->getOrders($queryParams);

        // If site ID is provided, filter by meta data
        if ($siteId !== null && $orders !== null) {
            $orders = array_filter($orders, function($order) use ($siteId) {
                foreach ($order['meta_data'] ?? [] as $meta) {
                    if ($meta['key'] === 'site_id' && $meta['value'] == $siteId) {
                        return true;
                    }
                }
                return false;
            });
        }

        return $orders;
    }

    /**
     * Calculate total revenue from orders
     *
     * @param array $orders List of WooCommerce orders
     * @return float Total revenue
     */
    public function calculateRevenue($orders)
    {
        if (!$orders) {
            return 0.0;
        }

        $total = 0.0;
        foreach ($orders as $order) {
            $total += (float) ($order['total'] ?? 0);
        }

        return $total;
    }

    /**
     * Get statistics for completed orders within a date range
     *
     * @param int $siteId
     * @param string $after Date in Y-m-d format
     * @param string|null $before Date in Y-m-d format
     * @return array ['count' => int, 'revenue' => float]
     */
    public function getCompletedOrdersStats($siteId, $after, $before = null)
    {
        $params = [
            'after' => $after . 'T00:00:00',
            'per_page' => 100,
        ];

        if ($before) {
            $params['before'] = $before . 'T23:59:59';
        }

        $orders = $this->getOrdersByStatus('completed', $siteId, $params);

        return [
            'count' => $orders ? count($orders) : 0,
            'revenue' => $this->calculateRevenue($orders),
            'orders' => $orders
        ];
    }
}
