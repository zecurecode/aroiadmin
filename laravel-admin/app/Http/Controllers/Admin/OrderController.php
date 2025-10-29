<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Location;
use App\Models\Setting;
use App\Models\Mail;
use App\Models\ApningstidAlternative;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class OrderController extends Controller
{
    /**
     * Display a listing of orders.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // If user is not admin, show user dashboard
        if (!$user->isAdmin()) {
            return $this->userDashboard($request);
        }

        $query = Order::where('site', $user->siteid);

        // Filter by status
        if ($request->has('status') && $request->status !== 'all') {
            switch ($request->status) {
                case 'pending':
                    $query->where('ordrestatus', 0);
                    break;
                case 'unpaid':
                    $query->where('paid', 0);
                    break;
                case 'not_sent':
                    $query->where('curl', 0);
                    break;
            }
        } else {
            // By default, only show paid orders unless specifically filtering for unpaid
            if (!$request->has('show_unpaid') || !$request->show_unpaid) {
                $query->where('paid', 1);
            }
        }

        // Filter by date
        if ($request->has('date') && $request->date) {
            $query->whereDate('datetime', $request->date);
        }

        // Search by customer name or order ID
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('fornavn', 'like', "%{$search}%")
                  ->orWhere('etternavn', 'like', "%{$search}%")
                  ->orWhere('ordreid', 'like', "%{$search}%")
                  ->orWhere('telefon', 'like', "%{$search}%");
            });
        }

        $orders = $query->orderBy('datetime', 'desc')->paginate(20);
        $locationName = Location::getNameBySiteId($user->siteid);

        return view('admin.orders.index', compact('orders', 'locationName'));
    }

    /**
     * User dashboard view for non-admin users.
     */
    protected function userDashboard(Request $request)
    {
        $user = Auth::user();
        $siteid = $user->siteid;

        // Get location data including delivery time
        $location = Location::where('site_id', $siteid)->first();
        $locationName = $location ? $location->name : Location::getNameBySiteId($siteid);
        $deliveryTime = $location ? $location->delivery_time_minutes : 30;

        // Get today's date
        $today = Carbon::today();

        // New orders (status 0) - show ALL paid orders (not just today)
        $newOrders = Order::where('site', $siteid)
            ->where('ordrestatus', 0)
            ->where('paid', 1)  // Only show paid orders
            ->orderBy('datetime', 'desc')
            ->get();

        // Ready orders (status 1) - show ALL paid orders (not just today)
        $readyOrders = Order::where('site', $siteid)
            ->where('ordrestatus', 1)
            ->where('paid', 1)  // Only show paid orders
            ->orderBy('datetime', 'desc')
            ->get();

        // Completed orders (status 2) - show ALL paid orders from today and recent days
        // This allows testing with database-modified orders
        $completedOrders = Order::where('site', $siteid)
            ->where('ordrestatus', 2)
            ->where('paid', 1)  // Only show paid orders
            ->where('datetime', '>=', $today->copy()->subDays(7))  // Last 7 days
            ->orderBy('datetime', 'desc')
            ->get();

        // Check if location is open from _apningstid table
        $apningstidAlt = ApningstidAlternative::where('AvdID', $siteid)->first();
        $isOpen = false;

        if ($apningstidAlt) {
            $todayDay = Carbon::now()->format('l'); // e.g., "Monday"
            $todayDayLower = strtolower($todayDay);
            $hoursToday = $apningstidAlt->getHoursForDay($todayDayLower);

            if ($hoursToday) {
                $isClosed = $hoursToday['closed']; // 0 = open, 1 = closed
                $isOpen = ($isClosed == 0); // true if open, false if closed
            }
        }

        return view('admin.orders.user-dashboard', compact(
            'newOrders',
            'readyOrders',
            'completedOrders',
            'locationName',
            'isOpen',
            'deliveryTime',
            'location'
        ));
    }

    /**
     * Display the specified order.
     */
    public function show(Order $order)
    {
        // Check if user can access this order
        if ($order->site !== Auth::user()->siteid) {
            abort(403, 'Unauthorized access to order.');
        }

        // Debug logging for request type
        \Log::info("Order show request for order {$order->id}", [
            'is_ajax' => request()->ajax(),
            'headers' => request()->headers->all(),
            'accept' => request()->header('Accept'),
            'x_requested_with' => request()->header('X-Requested-With'),
            'user_agent' => request()->userAgent()
        ]);

        // Get WooCommerce order details
        $wooOrder = $this->fetchWooCommerceOrder($order);

        // If this is an AJAX request, just return the order details view
        if (request()->ajax()) {
            \Log::info("Returning AJAX view for order {$order->id}");
            return view('admin.orders.show', compact('order', 'wooOrder'));
        }

        \Log::info("Returning full page view for order {$order->id}");
        return view('admin.orders.show', compact('order', 'wooOrder'));
    }

    /**
     * Update the specified order status.
     */
    public function update(Request $request, Order $order)
    {
        // Check if user can access this order
        if ($order->site !== Auth::user()->siteid) {
            abort(403, 'Unauthorized access to order.');
        }

        $request->validate([
            'ordrestatus' => 'required|integer|in:0,1,2,3',
        ]);

        $order->update([
            'ordrestatus' => $request->ordrestatus
        ]);

        return redirect()->route('admin.orders.show', $order)
            ->with('success', 'Order status updated successfully.');
    }

    /**
     * Mark order as paid.
     */
    public function markPaid(Order $order)
    {
        // Check if user can access this order
        if ($order->site !== Auth::user()->siteid) {
            abort(403, 'Unauthorized access to order.');
        }

        $order->update(['paid' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Order marked as paid'
        ]);
    }

    /**
     * Send order to POS system.
     */
    public function sendToPOS(Order $order)
    {
        // Check if user can access this order
        if ($order->site !== Auth::user()->siteid) {
            abort(403, 'Unauthorized access to order.');
        }

        // Get license for this location from database
        $user = Auth::user();
        $site = \App\Models\Site::findBySiteId($user->siteid);
        $license = $site ? $site->license : 0;

        if ($license == 0) {
            return response()->json([
                'success' => false,
                'message' => 'No license configured for this location'
            ], 400);
        }

        // Send to POS system
        $url = "https://min.pckasse.no/QueueGetOrders.aspx?licenceno={$license}";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $success = in_array($httpcode, [200, 201]);

        if ($success) {
            $order->update([
                'curl' => $httpcode,
                'curltime' => Carbon::now()
            ]);
        }

        return response()->json([
            'success' => $success,
            'message' => $success ? 'Order sent to POS successfully' : 'Failed to send order to POS',
            'http_code' => $httpcode
        ]);
    }

    /**
     * Send SMS notification to customer.
     */
    public function sendSMS(Order $order)
    {
        // Check if user can access this order
        if ($order->site !== Auth::user()->siteid) {
            abort(403, 'Unauthorized access to order.');
        }

        // Check if order is paid - don't send SMS if not paid
        if ($order->paid == 0) {
            return response()->json([
                'success' => false,
                'message' => 'Kan ikke sende SMS for ubetalte ordrer'
            ], 400);
        }

        if ($order->sms) {
            return response()->json([
                'success' => false,
                'message' => 'SMS already sent for this order'
            ], 400);
        }

        // Get SMS message for this location
        $mail = Mail::where('site', $order->site)->first();
        $locationName = Location::getNameBySiteId($order->site);
        $message = $mail ? $mail->melding : "Hei! Din ordre er klar for henting. Mvh {$locationName}";
        $message = str_replace('{order_id}', $order->ordreid, $message);

        // Normalize phone number to include country code
        $phoneNumber = $this->normalizePhoneNumber($order->telefon);

        // Get SMS credentials from settings
        $username = Setting::get('sms_api_username', 'b3166vr0f0l');
        $password = Setting::get('sms_api_password', '2tm2bxuIo2AixNELhXhwCdP8');
        $apiUrl = Setting::get('sms_api_url', 'https://api1.teletopiasms.no/gateway/v3/plain');
        $sender = Setting::get('sms_sender', 'AroiAsia');

        \Log::info("Sending SMS for order {$order->ordreid}", [
            'phone_original' => $order->telefon,
            'phone_normalized' => $phoneNumber
        ]);

        $smsUrl = $apiUrl . "?" . http_build_query([
            'username' => $username,
            'password' => $password,
            'recipient' => $phoneNumber,  // Teletopia uses 'recipient' not 'to'
            'text' => $message,
            'from' => $sender
        ]);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $smsUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $success = $httpcode == 200;

        if ($success) {
            $order->update(['sms' => true]);
        }

        return response()->json([
            'success' => $success,
            'message' => $success ? 'SMS sendt!' : 'Kunne ikke sende SMS'
        ]);
    }

    /**
     * Update order status with SMS notification.
     */
    public function updateStatus(Request $request, Order $order)
    {
        // Check if user can access this order
        if ($order->site !== Auth::user()->siteid) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $request->validate([
            'status' => 'required|integer|in:0,1,2,3'
        ]);

        // Store previous status for comparison
        $previousStatus = $order->ordrestatus;
        $newStatus = $request->status;
        $silent = $request->input('silent', false); // Check if silent mode is enabled

        // Prepare update data
        $updateData = ['ordrestatus' => $newStatus];

        // Set hentet_tid when order is marked as completed (status 2)
        if ($newStatus == 2 && $previousStatus != 2) {
            $updateData['hentet_tid'] = now();
        }

        // Update order status
        $order->update($updateData);

        // Only send SMS if order is paid, status has changed, and not in silent mode
        $smsSuccess = true;
        $smsMessage = '';
        if ($order->paid == 1 && $previousStatus != $newStatus && !$silent) {
            $smsResult = $this->sendStatusChangeSMS($order, $newStatus);
            $smsSuccess = $smsResult['success'];
            $smsMessage = $smsResult['message'];
        }

        $message = 'Status oppdatert';
        if ($silent) {
            $message .= ' (uten SMS)';
        } elseif ($order->paid == 1 && $previousStatus != $newStatus) {
            if ($smsSuccess) {
                $message .= ' og SMS sendt!';
            } else {
                $message .= '. OBS: SMS kunne ikke sendes - ' . $smsMessage;
            }
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'sms_sent' => !$silent && $smsSuccess
        ]);
    }

    /**
     * Get order count for notifications.
     */
    public function getOrderCount()
    {
        $user = Auth::user();
        $count = Order::where('site', $user->siteid)
            ->where('ordrestatus', 0)
            ->where('paid', 1)  // Only count paid orders
            ->whereDate('datetime', Carbon::today())
            ->count();

        return response()->json(['count' => $count]);
    }

        /**
     * Fetch WooCommerce order details with enhanced logging.
     */
    private function fetchWooCommerceOrder(Order $order)
    {
        try {
            // Get site credentials from database
            $site = \App\Models\Site::findBySiteId($order->site);

            if (!$site) {
                \Log::warning("No site found for order {$order->id} with site ID {$order->site}");
                return null;
            }

            // Construct WooCommerce API URL
            $url = $site->url . '/wp-json/wc/v3/orders/' . $order->ordreid;
            $url .= '?consumer_key=' . $site->consumer_key . '&consumer_secret=' . $site->consumer_secret;

            \Log::info("Fetching WooCommerce order from: " . $site->url . " for order ID: " . $order->ordreid);

            // Make API request to WooCommerce
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15); // Increased timeout
            curl_setopt($ch, CURLOPT_USERAGENT, 'AroiAdmin/1.0');

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($curlError) {
                \Log::error("CURL error fetching WooCommerce order: " . $curlError);
                return null;
            }

            if ($httpCode !== 200) {
                \Log::warning("WooCommerce API returned HTTP {$httpCode} for order {$order->ordreid}");
                if ($response) {
                    \Log::warning("Response: " . $response);
                }
                return null;
            }

            if (!$response) {
                \Log::warning("Empty response from WooCommerce for order {$order->ordreid}");
                return null;
            }

            $wooOrder = json_decode($response, true);

            if (!$wooOrder || isset($wooOrder['code'])) {
                \Log::error("Invalid WooCommerce response for order {$order->ordreid}: " . $response);
                return null;
            }

            // Log what we received for debugging
            \Log::info("WooCommerce order data keys: " . implode(', ', array_keys($wooOrder)));

            if (isset($wooOrder['customer_note'])) {
                \Log::info("Customer note found for order {$order->ordreid}: " . $wooOrder['customer_note']);
            } else {
                \Log::info("No customer_note field in WooCommerce data for order {$order->ordreid}");

                // Check if it's in a different field or format
                if (isset($wooOrder['meta_data'])) {
                    foreach ($wooOrder['meta_data'] as $meta) {
                        if (in_array($meta['key'], ['customer_note', '_customer_note', 'order_comments'])) {
                            \Log::info("Found customer note in meta_data: {$meta['key']} = {$meta['value']}");
                        }
                    }
                }
            }

            return $wooOrder;

        } catch (\Exception $e) {
            \Log::error("Exception fetching WooCommerce order {$order->ordreid}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Normalize Norwegian phone number to include country code.
     */
    private function normalizePhoneNumber($phone)
    {
        // Remove all spaces, dashes, and parentheses
        $phone = preg_replace('/[\s\-\(\)]/', '', $phone);

        // If already has +47, return as is
        if (substr($phone, 0, 3) === '+47') {
            return $phone;
        }

        // If starts with 0047, replace with +47
        if (substr($phone, 0, 4) === '0047') {
            return '+47' . substr($phone, 4);
        }

        // If starts with 47 (without +), add the +
        if (substr($phone, 0, 2) === '47' && strlen($phone) >= 10) {
            return '+' . $phone;
        }

        // If 8 digits (Norwegian mobile without country code), add +47
        if (strlen($phone) === 8 && ctype_digit($phone)) {
            return '+47' . $phone;
        }

        // Otherwise return as is (might be international number)
        return $phone;
    }

    /**
     * Send SMS notification for status changes.
     */
    private function sendStatusChangeSMS(Order $order, $status)
    {
        // Don't send SMS if order is not paid
        if ($order->paid == 0) {
            \Log::info("Skipping SMS for unpaid order {$order->ordreid}");
            return ['success' => false, 'message' => 'Ordre er ikke betalt'];
        }

        $locationName = Location::getNameBySiteId($order->site);

        // Prepare status-specific message
        switch ($status) {
            case 0: // New order
                $message = "Hei {$order->fornavn}! Vi har mottatt din ordre #{$order->ordreid}. Du får en ny melding når den er klar. Mvh {$locationName}";
                break;
            case 1: // Ready for pickup
                $message = "Hei {$order->fornavn}! Din ordre #{$order->ordreid} er klar for henting. Mvh {$locationName}";
                break;
            case 2: // Completed/Picked up
                $message = "Takk for handelen! Din ordre #{$order->ordreid} er hentet. Velkommen tilbake! Mvh {$locationName}";
                break;
            case 3: // Cancelled
                $message = "Din ordre #{$order->ordreid} har blitt avbrutt. Ta kontakt med oss hvis du har spørsmål. Mvh {$locationName}";
                break;
            default:
                return ['success' => false, 'message' => 'Ugyldig status'];
        }

        // Normalize phone number to include country code
        $phoneNumber = $this->normalizePhoneNumber($order->telefon);

        // Get SMS credentials from settings
        $username = Setting::get('sms_api_username', 'b3166vr0f0l');
        $password = Setting::get('sms_api_password', '2tm2bxuIo2AixNELhXhwCdP8');
        $apiUrl = Setting::get('sms_api_url', 'https://api1.teletopiasms.no/gateway/v3/plain');
        $sender = Setting::get('sms_sender', 'AroiAsia');

        $smsUrl = $apiUrl . "?" . http_build_query([
            'username' => $username,
            'password' => $password,
            'recipient' => $phoneNumber,  // Teletopia uses 'recipient' not 'to'
            'text' => $message,
            'from' => $sender
        ]);

        \Log::info("Sending SMS for order {$order->ordreid}", [
            'status' => $status,
            'phone_original' => $order->telefon,
            'phone_normalized' => $phoneNumber,
            'api_url' => $apiUrl,
            'sender' => $sender,
            'message' => $message
        ]);

        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $smsUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            $output = curl_exec($ch);
            $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            $success = $httpcode == 200;

            if ($success) {
                \Log::info("SMS sent successfully for order {$order->ordreid}", [
                    'http_code' => $httpcode,
                    'response' => $output
                ]);
                return ['success' => true, 'message' => 'SMS sendt'];
            } else {
                \Log::error("Failed to send SMS for order {$order->ordreid}", [
                    'http_code' => $httpcode,
                    'response' => $output,
                    'curl_error' => $curlError
                ]);
                return ['success' => false, 'message' => "HTTP feil {$httpcode}"];
            }
        } catch (\Exception $e) {
            \Log::error("Exception sending SMS for order {$order->ordreid}: " . $e->getMessage());
            return ['success' => false, 'message' => 'Teknisk feil: ' . $e->getMessage()];
        }
    }


    /**
     * Delete old orders (called by cron).
     */
    public function deleteOldOrders()
    {
        $days = Setting::get('order_auto_delete_days', 14);
        $date = Carbon::now()->subDays($days);

        $deleted = Order::where('datetime', '<', $date)->delete();

        return response()->json([
            'success' => true,
            'deleted' => $deleted
        ]);
    }
}
