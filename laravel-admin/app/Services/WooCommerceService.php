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
     * Automatically handles pagination to fetch all orders
     *
     * @param array $params Query parameters (status, per_page, after, before, etc.)
     * @param bool $paginate Whether to automatically paginate and fetch all results (default: true)
     * @return array|null List of orders
     */
    public function getOrders($params = [], $paginate = true)
    {
        try {
            $url = "{$this->baseUrl}/wp-json/wc/v3/orders";
            $allOrders = [];
            $page = 1;
            $perPage = $params['per_page'] ?? 100;

            Log::info('WooCommerce: Starting order fetch', [
                'url' => $url,
                'params' => $params,
                'paginate' => $paginate,
            ]);

            do {
                $queryParams = array_merge($params, [
                    'per_page' => $perPage,
                    'page' => $page,
                ]);

                $response = Http::withBasicAuth($this->consumerKey, $this->consumerSecret)
                    ->timeout(30)
                    ->get($url, $queryParams);

                if (!$response->successful()) {
                    Log::warning('WooCommerce API orders request failed', [
                        'params' => $queryParams,
                        'status' => $response->status(),
                        'body' => $response->body(),
                        'url' => $url,
                    ]);

                    // Return what we have so far, or null if first page
                    return $page === 1 ? null : $allOrders;
                }

                $orders = $response->json();

                if (empty($orders)) {
                    break;
                }

                $allOrders = array_merge($allOrders, $orders);

                Log::info('WooCommerce: Page fetched', [
                    'page' => $page,
                    'count_this_page' => count($orders),
                    'total_so_far' => count($allOrders),
                ]);

                // If not paginating, just return first page
                if (!$paginate) {
                    break;
                }

                // If we got fewer results than per_page, we're done
                if (count($orders) < $perPage) {
                    break;
                }

                $page++;

                // Safety limit to prevent infinite loops
                if ($page > 50) {
                    Log::warning('WooCommerce: Reached maximum page limit (50)');
                    break;
                }

            } while ($paginate);

            Log::info('WooCommerce: Order fetch complete', [
                'total_orders' => count($allOrders),
                'pages_fetched' => $page,
            ]);

            return $allOrders;

        } catch (\Exception $e) {
            Log::error('WooCommerce API orders exception', [
                'params' => $params,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
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
     * @param bool $filterBySiteId Whether to filter by site_id in meta_data (default: false)
     * @return array|null
     */
    public function getOrdersByStatus($status, $siteId = null, $params = [], $filterBySiteId = false)
    {
        $queryParams = array_merge([
            'status' => $status,
            'per_page' => 100,
        ], $params);

        Log::info('WooCommerce: Fetching orders', [
            'site_id' => $this->siteId,
            'status' => $status,
            'params' => $queryParams,
            'base_url' => $this->baseUrl,
        ]);

        // Don't paginate by default for performance (unless per_page > 100)
        $shouldPaginate = isset($params['per_page']) && $params['per_page'] > 100;
        $orders = $this->getOrders($queryParams, $shouldPaginate);

        Log::info('WooCommerce: Orders fetched', [
            'site_id' => $this->siteId,
            'status' => $status,
            'count_before_filter' => $orders ? count($orders) : 0,
            'filter_by_site_id' => $filterBySiteId,
        ]);

        // If site ID is provided AND filterBySiteId is true, filter by meta data
        // NOTE: In WordPress multisite, each site has its own WooCommerce instance
        // so filtering by site_id in meta_data is usually NOT needed
        if ($filterBySiteId && $siteId !== null && $orders !== null) {
            $originalCount = count($orders);
            $orders = array_filter($orders, function($order) use ($siteId) {
                foreach ($order['meta_data'] ?? [] as $meta) {
                    if ($meta['key'] === 'site_id' && $meta['value'] == $siteId) {
                        return true;
                    }
                }
                return false;
            });

            Log::info('WooCommerce: Orders filtered by site_id', [
                'site_id' => $siteId,
                'count_before' => $originalCount,
                'count_after' => count($orders),
            ]);
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

    /**
     * Get pending orders for a site
     * Note: In WordPress multisite, each site has its own WooCommerce instance
     * so we don't need to filter by site_id in metadata
     *
     * @param int $siteId
     * @return array|null
     */
    public function getPendingOrders($siteId)
    {
        // Don't filter by site_id metadata - each site URL is already site-specific
        $params = [
            'status' => 'pending',
            'per_page' => 100,
        ];

        Log::info('WooCommerce: Fetching pending orders', [
            'site_id' => $siteId,
            'params' => $params,
        ]);

        $orders = $this->getOrders($params, false); // Don't paginate for pending (should be few)

        Log::info('WooCommerce: Pending orders fetched', [
            'site_id' => $siteId,
            'count' => $orders ? count($orders) : 0,
        ]);

        return $orders;
    }

    /**
     * Get revenue statistics from WooCommerce Analytics API
     * Uses the fast wc-analytics/reports/revenue/stats endpoint
     *
     * @param string $after Start date (Y-m-d format)
     * @param string $before End date (Y-m-d format)
     * @param string $interval Interval (day, week, month, year)
     * @return array|null ['total_sales' => float, 'orders_count' => int]
     */
    public function getRevenueStats($after, $before, $interval = 'day')
    {
        try {
            $url = "{$this->baseUrl}/wp-json/wc-analytics/reports/revenue/stats";

            $params = [
                'after' => $after . 'T00:00:00',
                'before' => $before . 'T23:59:59',
                'interval' => $interval,
            ];

            Log::info('WooCommerce Analytics: Fetching revenue stats', [
                'url' => $url,
                'params' => $params,
            ]);

            $response = Http::withBasicAuth($this->consumerKey, $this->consumerSecret)
                ->timeout(15)
                ->get($url, $params);

            if ($response->successful()) {
                $data = $response->json();
                $totals = $data['totals'] ?? [];

                Log::info('WooCommerce Analytics: Revenue stats fetched', [
                    'total_sales' => $totals['total_sales'] ?? 0,
                    'orders_count' => $totals['orders_count'] ?? 0,
                ]);

                return [
                    'total_sales' => floatval($totals['total_sales'] ?? 0),
                    'orders_count' => intval($totals['orders_count'] ?? 0),
                    'net_revenue' => floatval($totals['net_revenue'] ?? 0),
                ];
            }

            Log::warning('WooCommerce Analytics API failed, will use fallback', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            // Fallback to manual calculation
            return $this->getRevenueStatsFallback($after, $before);

        } catch (\Exception $e) {
            Log::error('WooCommerce Analytics exception, using fallback', [
                'error' => $e->getMessage(),
            ]);

            return $this->getRevenueStatsFallback($after, $before);
        }
    }

    /**
     * Fallback method: Calculate revenue stats manually from orders
     *
     * @param string $after Start date (Y-m-d format)
     * @param string $before End date (Y-m-d format)
     * @return array|null
     */
    private function getRevenueStatsFallback($after, $before)
    {
        Log::info('WooCommerce: Using fallback method for revenue stats');

        $params = [
            'status' => 'completed',
            'after' => $after . 'T00:00:00',
            'before' => $before . 'T23:59:59',
            'per_page' => 100,
        ];

        // Use pagination for large date ranges, but limit to reasonable amount
        $orders = $this->getOrders($params, false);

        if ($orders === null) {
            return null;
        }

        $totalSales = 0;
        foreach ($orders as $order) {
            $totalSales += floatval($order['total'] ?? 0);
        }

        return [
            'total_sales' => $totalSales,
            'orders_count' => count($orders),
            'net_revenue' => $totalSales,
        ];
    }

    /**
     * Get comprehensive statistics for a site
     * Uses WooCommerce Analytics API for fast performance
     *
     * @param int $siteId
     * @return array
     */
    public function getSiteStatistics($siteId)
    {
        $now = now();

        // Current year
        $yearStart = $now->copy()->startOfYear()->format('Y-m-d');
        $yearEnd = $now->format('Y-m-d');

        // Current month
        $monthStart = $now->copy()->startOfMonth()->format('Y-m-d');
        $monthEnd = $now->format('Y-m-d');

        // Previous year (same period)
        $prevYearStart = $now->copy()->subYear()->startOfYear()->format('Y-m-d');
        $prevYearEnd = $now->copy()->subYear()->format('Y-m-d');

        // Previous month (full month)
        $prevMonthStart = $now->copy()->subMonth()->startOfMonth()->format('Y-m-d');
        $prevMonthEnd = $now->copy()->subMonth()->endOfMonth()->format('Y-m-d');

        Log::info('WooCommerce: Fetching site statistics', [
            'site_id' => $siteId,
            'year_period' => [$yearStart, $yearEnd],
            'month_period' => [$monthStart, $monthEnd],
        ]);

        // Fetch all statistics using Analytics API
        $yearStats = $this->getRevenueStats($yearStart, $yearEnd);
        $monthStats = $this->getRevenueStats($monthStart, $monthEnd);
        $prevYearStats = $this->getRevenueStats($prevYearStart, $prevYearEnd);
        $prevMonthStats = $this->getRevenueStats($prevMonthStart, $prevMonthEnd);

        // Check pending orders count only (no order details)
        // Note: Pending orders in WooCommerce may indicate payment failures
        try {
            $pendingStats = $this->getRevenueStats($yearStart, $yearEnd, 'day');
            // Analytics API doesn't provide pending count, so we skip it
            $pendingCount = 0;
        } catch (\Exception $e) {
            $pendingCount = 0;
        }

        $pendingData = [
            'count' => $pendingCount,
            'orders' => [] // Never fetch order list from WooCommerce
        ];

        // Calculate percentage changes
        $yearChange = $prevYearStats['total_sales'] > 0
            ? (($yearStats['total_sales'] - $prevYearStats['total_sales']) / $prevYearStats['total_sales']) * 100
            : ($yearStats['total_sales'] > 0 ? 100 : 0);

        $monthChange = $prevMonthStats['total_sales'] > 0
            ? (($monthStats['total_sales'] - $prevMonthStats['total_sales']) / $prevMonthStats['total_sales']) * 100
            : ($monthStats['total_sales'] > 0 ? 100 : 0);

        Log::info('WooCommerce: Statistics calculated', [
            'year_revenue' => $yearStats['total_sales'],
            'year_count' => $yearStats['orders_count'],
            'year_change' => round($yearChange, 2) . '%',
            'month_revenue' => $monthStats['total_sales'],
            'month_count' => $monthStats['orders_count'],
            'month_change' => round($monthChange, 2) . '%',
        ]);

        return [
            'year' => [
                'count' => $yearStats['orders_count'],
                'revenue' => $yearStats['total_sales'],
                'change_percent' => round($yearChange, 2),
                'change_direction' => $yearChange >= 0 ? 'up' : 'down',
            ],
            'month' => [
                'count' => $monthStats['orders_count'],
                'revenue' => $monthStats['total_sales'],
                'change_percent' => round($monthChange, 2),
                'change_direction' => $monthChange >= 0 ? 'up' : 'down',
            ],
            'previous_year' => [
                'count' => $prevYearStats['orders_count'],
                'revenue' => $prevYearStats['total_sales'],
            ],
            'previous_month' => [
                'count' => $prevMonthStats['orders_count'],
                'revenue' => $prevMonthStats['total_sales'],
            ],
            'pending' => $pendingData,
            'fetched_at' => $now->toDateTimeString(),
        ];
    }

    /**
     * Get order status from WooCommerce.
     *
     * @param int $orderId WooCommerce order ID
     * @return string|null Order status (pending, processing, completed, etc.)
     */
    public function getOrderStatus($orderId)
    {
        $order = $this->getOrder($orderId);
        return $order ? $order['status'] : null;
    }

    /**
     * Check if order is completed in WooCommerce.
     *
     * @param int $orderId WooCommerce order ID
     * @return bool
     */
    public function isOrderCompleted($orderId)
    {
        $status = $this->getOrderStatus($orderId);
        return $status === 'completed';
    }

    /**
     * Update order status in WooCommerce.
     *
     * @param int $orderId WooCommerce order ID
     * @param string $status New status (pending, processing, completed, cancelled, etc.)
     * @return bool Success status
     */
    public function updateOrderStatus($orderId, $status)
    {
        try {
            $url = "{$this->baseUrl}/wp-json/wc/v3/orders/{$orderId}";

            Log::info("WooCommerce: Updating order {$orderId} to status: {$status}");

            $response = Http::withBasicAuth($this->consumerKey, $this->consumerSecret)
                ->timeout(15)
                ->put($url, ['status' => $status]);

            if ($response->successful()) {
                Log::info("WooCommerce: Successfully updated order {$orderId} to {$status}");
                return true;
            }

            Log::warning('WooCommerce: Failed to update order status', [
                'order_id' => $orderId,
                'status' => $status,
                'http_code' => $response->status(),
                'response' => $response->body()
            ]);

            return false;

        } catch (\Exception $e) {
            Log::error('WooCommerce: Exception updating order status', [
                'order_id' => $orderId,
                'status' => $status,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Mark order as completed in WooCommerce.
     *
     * @param int $orderId WooCommerce order ID
     * @return bool Success status
     */
    public function markOrderCompleted($orderId)
    {
        return $this->updateOrderStatus($orderId, 'completed');
    }
}
