<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Location;
use App\Models\Setting;
use App\Models\Mail;
use App\Models\OpeningHours;
use App\Models\Overstyr;
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
        $locationName = Location::getNameBySiteId($siteid);

        // Get today's orders
        $today = Carbon::today();

        // New orders (status 0)
        $newOrders = Order::where('site', $siteid)
            ->where('ordrestatus', 0)
            ->whereDate('datetime', $today)
            ->orderBy('datetime', 'desc')
            ->get();

        // Ready orders (status 1)
        $readyOrders = Order::where('site', $siteid)
            ->where('ordrestatus', 1)
            ->whereDate('datetime', $today)
            ->orderBy('datetime', 'desc')
            ->get();

        // Completed orders (status 2)
        $completedOrders = Order::where('site', $siteid)
            ->where('ordrestatus', 2)
            ->whereDate('datetime', $today)
            ->orderBy('datetime', 'desc')
            ->get();

        // Check if location is open
        $overstyr = Overstyr::where('vognid', $user->id)->first();
        $isOpen = $overstyr ? $overstyr->status == 1 : false;

        return view('admin.orders.user-dashboard', compact(
            'newOrders',
            'readyOrders',
            'completedOrders',
            'locationName',
            'isOpen'
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

        if ($order->sms) {
            return response()->json([
                'success' => false,
                'message' => 'SMS already sent for this order'
            ], 400);
        }

        // Get SMS message for this location
        $mail = Mail::where('site', $order->site)->first();
        $message = $mail ? $mail->melding : "Hei! Din ordre er klar for henting. Mvh {$locationName}";
        $message = str_replace('{order_id}', $order->ordreid, $message);

        // Get SMS credentials from settings
        $username = Setting::get('sms_api_username', 'b3166vr0f0l');
        $password = Setting::get('sms_api_password', '2tm2bxuIo2AixNELhXhwCdP8');
        $apiUrl = Setting::get('sms_api_url', 'https://api1.teletopiasms.no/gateway/v3/plain');
        $sender = Setting::get('sms_sender', 'AroiAsia');

        $smsUrl = $apiUrl . "?" . http_build_query([
            'username' => $username,
            'password' => $password,
            'to' => $order->telefon,
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
     * Update order status.
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

        $order->update([
            'ordrestatus' => $request->status
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Status oppdatert'
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
