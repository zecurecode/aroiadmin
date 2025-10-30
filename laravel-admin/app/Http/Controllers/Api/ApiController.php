<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use App\Services\WooCommerceService;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ApiController extends Controller
{
    /**
     * Main API endpoint that processes orders and sends them to POS.
     */
    public function processOrders()
    {
        $this->checkCurl();
        $this->checkFailedOrders();

        return response()->json([
            'status' => 'success',
            'message' => 'Orders processed'
        ]);
    }

    /**
     * Get user information by site ID.
     */
    private function getUserInfo($siteid, $function)
    {
        $user = User::where('siteid', $siteid)->first();

        if (!$user) {
            return '';
        }

        switch($function) {
            case 1:
                return $user->username;
            case 2:
                return $user->license;
            case 3:
                return $user->id;
            default:
                return '';
        }
    }

    /**
     * Send SMS to customer.
     */
    private function sendSms($orderNum, $telefon)
    {
        $order = Order::where('ordreid', $orderNum)->first();

        if (!$order || $order->sms) {
            \Log::info("Skipping SMS for order {$orderNum} - already sent or not found");
            return; // SMS already sent or order not found
        }

        // Check if order is paid - don't send SMS if not paid
        if ($order->paid == 0) {
            \Log::info("Skipping SMS for unpaid order {$orderNum}");
            return;
        }

        // Get location name
        $locationName = \App\Models\Location::getNameBySiteId($order->site);

        // Build "order received" message with order ID and location
        $message = "Hei {$order->fornavn}! Vi har mottatt din ordre #{$order->ordreid}. "
                 . "Vi vil gjøre bestillingen klar så fort vi kan. "
                 . "Du får en ny melding når maten er klar til henting. "
                 . "Mvh {$locationName}";

        // Normalize phone number to +47 format
        $phoneNormalized = $this->normalizePhoneNumber($telefon);

        // Get SMS credentials from settings
        $username = \App\Models\Setting::get('sms_api_username', 'b3166vr0f0l');
        $password = \App\Models\Setting::get('sms_api_password', '2tm2bxuIo2AixNELhXhwCdP8');
        $apiUrl = \App\Models\Setting::get('sms_api_url', 'https://api1.teletopiasms.no/gateway/v3/plain');
        $sender = \App\Models\Setting::get('sms_sender', 'AroiAsia');

        $smsUrl = $apiUrl . "?" . http_build_query([
            'username' => $username,
            'password' => $password,
            'recipient' => $phoneNormalized,
            'text' => $message,
            'from' => $sender
        ]);

        \Log::info("Sending 'order received' SMS for order {$orderNum}", [
            'phone_original' => $telefon,
            'phone_normalized' => $phoneNormalized,
            'location' => $locationName,
            'message' => $message
        ]);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $smsUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $output = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($httpcode == 200) {
            $order->update(['sms' => true]);
            \Log::info("SMS sent successfully for order {$orderNum}", [
                'http_code' => $httpcode,
                'response' => $output
            ]);
        } else {
            \Log::error("Failed to send SMS for order {$orderNum}", [
                'http_code' => $httpcode,
                'response' => $output,
                'curl_error' => $curlError
            ]);
        }
    }

    /**
     * Normalize phone number to +47 format
     */
    private function normalizePhoneNumber($phone)
    {
        // Remove spaces, dashes, parentheses
        $phone = preg_replace('/[\s\-\(\)]/', '', $phone);

        // If already starts with +47, return as-is
        if (strpos($phone, '+47') === 0) {
            return $phone;
        }

        // If starts with 0047, replace with +47
        if (strpos($phone, '0047') === 0) {
            return '+47' . substr($phone, 4);
        }

        // If starts with 47 (without +), add +
        if (strpos($phone, '47') === 0 && strlen($phone) >= 10) {
            return '+' . $phone;
        }

        // If 8 digits (Norwegian mobile), prepend +47
        if (strlen($phone) == 8) {
            return '+47' . $phone;
        }

        // Otherwise return as-is
        return $phone;
    }

    /**
     * Queue orders to POS system.
     */
    private function queGetOrders($siteid)
    {
        $license = $this->getUserInfo($siteid, 2);

        if (!$license) {
            return 400;
        }

        $url = "https://min.pckasse.no/QueueGetOrders.aspx?licenceno={$license}";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $httpcode;
    }

    /**
     * Check orders that need to be sent to POS.
     */
    private function checkCurl()
    {
        // Only process paid orders
        $orders = Order::where('curl', 0)
                      ->where('paid', 1)  // Only process paid orders
                      ->get();

        foreach ($orders as $order) {
            $response = $this->queGetOrders($order->site);

            if (in_array($response, [200, 201])) {
                $this->updateDb($response, $order->id);
                $this->sendSms($order->ordreid, $order->telefon);
            }
        }
    }

    /**
     * Check for failed orders (unpaid after 5 minutes).
     */
    private function checkFailedOrders()
    {
        $orders = Order::where('paid', 0)->get();

        foreach ($orders as $order) {
            $orderTime = Carbon::parse($order->datetime);
            $now = Carbon::now();
            $diffMinutes = $now->diffInMinutes($orderTime);

            if ($diffMinutes > 5) {
                $message = "Aroi ordreid {$order->ordreid} (vogn {$order->site}) har ikke blitt betalt på {$diffMinutes} minutter!";
                $this->sendAlertSms($message);
            }
        }

        $this->deleteOldRecords();
    }

    /**
     * Send alert SMS to admins.
     */
    private function sendAlertSms($message)
    {
        $smsUrl = "https://api1.teletopiasms.no/gateway/v3/plain?" . http_build_query([
            'username' => 'p3166eu720i',
            'password' => 'Nvn4xh8HADL5YvInFI4GLlhM',
            'recipient' => '4790039911,4796017450',
            'text' => $message
        ]);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $smsUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($ch);
        curl_close($ch);
    }

    /**
     * Delete old records (older than 14 days).
     */
    private function deleteOldRecords()
    {
        Order::where('datetime', '<', Carbon::now()->subDays(14))->delete();
    }

    /**
     * Update order with POS response.
     */
    private function updateDb($curl, $id)
    {
        $order = Order::find($id);
        if ($order) {
            $order->update([
                'curl' => $curl,
                'curltime' => Carbon::now()
            ]);
        }
    }

    /**
     * Get orders for debugging.
     */
    public function getOrders()
    {
        $orders = Order::where('ordrestatus', 0)
            ->with('location')
            ->orderBy('datetime', 'desc')
            ->get()
            ->map(function($order) {
                return [
                    'ordrestatus' => $order->ordrestatus,
                    'ordreid' => $order->ordreid,
                    'curl' => $order->curl,
                    'curltime' => $order->curltime,
                    'site' => $order->site,
                    'username' => $this->getUserInfo($order->site, 1),
                    'userid' => $this->getUserInfo($order->site, 3),
                ];
            });

        return response()->json($orders);
    }

    /**
     * Handle WordPress order creation.
     */
    public function createOrder(Request $request)
    {
        $validated = $request->validate([
            'fornavn' => 'required|string|max:255',
            'etternavn' => 'required|string|max:255',
            'telefon' => 'required|string|max:20',
            'ordreid' => 'required|integer',
            'epost' => 'required|email|max:255',
            'site' => 'required|integer',
            'total_amount' => 'nullable|numeric|min:0',
            'hentes' => 'nullable|string|max:50',
        ]);

        // Fetch total_amount from WooCommerce if not provided
        $totalAmount = $validated['total_amount'] ?? null;
        if ($totalAmount === null) {
            // Initialize WooCommerce service with site-specific credentials
            $wooCommerce = new WooCommerceService($validated['site']);
            $totalAmount = $wooCommerce->getOrderTotal($validated['ordreid']);
        }

        $order = Order::create([
            'fornavn' => $validated['fornavn'],
            'etternavn' => $validated['etternavn'],
            'telefon' => $validated['telefon'],
            'ordreid' => $validated['ordreid'],
            'ordrestatus' => 0,
            'epost' => $validated['epost'],
            'curl' => 0,
            'site' => $validated['site'],
            'paid' => false,
            'sms' => false,
            'datetime' => Carbon::now(),
            'total_amount' => $totalAmount,
            'hentes' => $validated['hentes'] ?? '',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Order created successfully',
            'order_id' => $order->id,
            'total_amount' => $totalAmount
        ]);
    }

    /**
     * Mark order as paid (called by WordPress).
     */
    public function markOrderPaid(Request $request)
    {
        $validated = $request->validate([
            'ordreid' => 'required|integer',
            'site' => 'required|integer',
        ]);

        $order = Order::where('ordreid', $validated['ordreid'])
                     ->where('site', $validated['site'])
                     ->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found'
            ], 404);
        }

        $order->update(['paid' => true]);

        // Send initial SMS notification now that order is paid
        $this->sendSms($order->ordreid, $order->telefon);

        // Trigger order processing
        $this->processOrders();

        return response()->json([
            'success' => true,
            'message' => 'Order marked as paid and SMS sent'
        ]);
    }

    /**
     * Batch check WooCommerce status for multiple orders.
     * Used for auto-polling to detect when PCKasse has processed orders.
     */
    public function batchCheckStatus(Request $request)
    {
        $validated = $request->validate([
            'order_ids' => 'required|array|max:50',
            'order_ids.*' => 'required|integer|exists:orders,id',
        ]);

        $orderIds = $validated['order_ids'];
        $results = [];

        foreach ($orderIds as $orderId) {
            $order = Order::find($orderId);

            if (!$order) {
                $results[] = [
                    'id' => $orderId,
                    'success' => false,
                    'message' => 'Order not found'
                ];
                continue;
            }

            // Initialize WooCommerce service for this site
            $wooService = new WooCommerceService($order->site);

            // Check current WooCommerce status
            $wcStatus = $wooService->getOrderStatus($order->ordreid);

            if ($wcStatus === null) {
                $results[] = [
                    'id' => $orderId,
                    'order_id' => $order->ordreid,
                    'success' => false,
                    'message' => 'Could not fetch WC status',
                    'wc_status' => null,
                    'pck_acknowledged' => false
                ];
                continue;
            }

            // Update local WC status
            $previousStatus = $order->wcstatus;
            $order->update(['wcstatus' => $wcStatus]);

            // Check if PCKasse has acknowledged (status = completed)
            $pckAcknowledged = ($wcStatus === 'completed');

            // If now completed and wasn't before, update curl tracking
            if ($pckAcknowledged && $previousStatus !== 'completed' && $order->curl == 0) {
                $order->update([
                    'curl' => 200,
                    'curltime' => Carbon::now(),
                    'pck_export_status' => 'sent'
                ]);
            }

            // If NOT completed and order is paid, trigger PCKasse queue
            $triggered = false;
            if (!$pckAcknowledged && $order->paid) {
                // Use PCKasseService to trigger queue
                $pckService = new \App\Services\PCKasseService();
                $pckService->markOrderForRetry($order);
                $triggerResult = $pckService->triggerQueue($order->site);
                $triggered = $triggerResult['success'] ?? false;
            }

            $results[] = [
                'id' => $orderId,
                'order_id' => $order->ordreid,
                'success' => true,
                'wc_status' => $wcStatus,
                'pck_acknowledged' => $pckAcknowledged,
                'status_changed' => ($previousStatus !== $wcStatus),
                'pck_triggered' => $triggered
            ];
        }

        return response()->json([
            'success' => true,
            'results' => $results
        ]);
    }
}
