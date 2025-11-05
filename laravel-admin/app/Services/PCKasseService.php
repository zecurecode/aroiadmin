<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Site;
use Illuminate\Support\Facades\Log;

class PCKasseService
{
    /**
     * Trigger PCKasse to fetch orders from the queue.
     *
     * How it works:
     * - Calls https://min.pckasse.no/QueueGetOrders.aspx?licenceno=XXXX
     * - This tells PCKasse POS app to check for new orders and download them
     * - PCKasse will then print the order in the kitchen
     * - After successful print, PCKasse marks the order as "completed" in WooCommerce
     *
     * When to use:
     * - When WooCommerce's automatic call to QueueGetOrders failed
     * - When an order is not marked as "completed" in WooCommerce (no kitchen print)
     *
     * @param  int  $siteId  Site ID to get license number
     * @return array ['success' => bool, 'message' => string, 'http_code' => int, 'response' => array]
     */
    public function triggerQueue($siteId)
    {
        try {
            $site = Site::findBySiteId($siteId);

            if (! $site) {
                Log::warning("PCKasse: No site found for site ID {$siteId}");

                return [
                    'success' => false,
                    'message' => 'No site configuration found',
                    'http_code' => 0,
                    'response' => null,
                ];
            }

            $license = $site->license;

            if (! $license || $license == 0) {
                Log::warning("PCKasse: No license configured for site ID {$siteId}");

                return [
                    'success' => false,
                    'message' => 'No license configured for this location',
                    'http_code' => 0,
                    'response' => null,
                ];
            }

            // Trigger PCKasse queue
            $url = "https://min.pckasse.no/QueueGetOrders.aspx?licenceno={$license}";

            Log::info("PCKasse: Triggering queue for site {$siteId} with license {$license}");

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            $output = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($curlError) {
                Log::error('PCKasse: CURL error: '.$curlError);

                return [
                    'success' => false,
                    'message' => 'Connection error: '.$curlError,
                    'http_code' => 0,
                    'response' => null,
                ];
            }

            $success = in_array($httpCode, [200, 201]);
            $responseData = json_decode($output, true);

            if ($success) {
                Log::info('PCKasse: Queue triggered successfully', [
                    'site_id' => $siteId,
                    'license' => $license,
                    'http_code' => $httpCode,
                    'response' => $responseData,
                ]);
            } else {
                Log::warning('PCKasse: Queue trigger failed', [
                    'site_id' => $siteId,
                    'license' => $license,
                    'http_code' => $httpCode,
                    'response' => $output,
                ]);
            }

            return [
                'success' => $success,
                'message' => $success ? 'PCKasse queue triggered successfully' : 'Failed to trigger PCKasse queue',
                'http_code' => $httpCode,
                'response' => $responseData,
            ];

        } catch (\Exception $e) {
            Log::error('PCKasse: Exception triggering queue: '.$e->getMessage());

            return [
                'success' => false,
                'message' => 'Exception: '.$e->getMessage(),
                'http_code' => 0,
                'response' => null,
            ];
        }
    }

    /**
     * Check if PCKasse successfully processed orders.
     * Parses the response to check OkCount and FailedCount.
     *
     * @param  array  $response  PCKasse API response
     * @return array ['ok_count' => int, 'failed_count' => int, 'success' => bool]
     */
    public function parseQueueResponse($response)
    {
        if (! is_array($response)) {
            return [
                'ok_count' => 0,
                'failed_count' => 0,
                'success' => false,
                'message' => 'Invalid response format',
            ];
        }

        $okCount = $response['OkCount'] ?? 0;
        $failedCount = $response['FailedCount'] ?? 0;
        $dbResults = $response['DbResults'] ?? [];

        return [
            'ok_count' => $okCount,
            'failed_count' => $failedCount,
            'success' => $okCount > 0 && $failedCount == 0,
            'message' => "OK: {$okCount}, Failed: {$failedCount}",
            'db_results' => $dbResults,
        ];
    }

    /**
     * Mark order as ready for PCK export (reset curl status).
     *
     * @return bool
     */
    public function markOrderForRetry(Order $order)
    {
        try {
            $order->update([
                'curl' => 0,
                'curltime' => null,
                'pck_export_status' => 'new',
            ]);

            Log::info("PCKasse: Order {$order->id} marked for retry");

            return true;

        } catch (\Exception $e) {
            Log::error('PCKasse: Failed to mark order for retry: '.$e->getMessage());

            return false;
        }
    }
}
