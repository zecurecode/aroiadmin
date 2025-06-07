<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
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
            return; // SMS already sent or order not found
        }

        $message = "Takk for din ordre. Vi vil gjøre din bestilling klar så fort vi kan. Vi sender deg en ny SMS når maten er klar til henting. Ditt referansenummer er {$orderNum}";

        $smsUrl = "https://api1.teletopiasms.no/gateway/v3/plain?" . http_build_query([
            'username' => 'b3166vr0f0l',
            'password' => '2tm2bxuIo2AixNELhXhwCdP8',
            'recipient' => $telefon,
            'text' => $message
        ]);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $smsUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpcode == 200) {
            $order->update(['sms' => true]);
        }
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
        $orders = Order::where('curl', 0)->get();

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
        ]);

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
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Order created successfully',
            'order_id' => $order->id
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

        // Trigger order processing
        $this->processOrders();

        return response()->json([
            'success' => true,
            'message' => 'Order marked as paid'
        ]);
    }
}
