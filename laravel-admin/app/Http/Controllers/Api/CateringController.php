<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Location;
use App\Models\CateringSettings;
use App\Models\Mail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class CateringController extends Controller
{
    /**
     * Get catering settings for a location
     */
    public function getSettings($siteId)
    {
        try {
            $settings = CateringSettings::where('site_id', $siteId)->first();
            
            if (!$settings) {
                $settings = CateringSettings::create([
                    'site_id' => $siteId,
                    'catering_enabled' => true,
                    'min_guests' => 10,
                    'advance_notice_days' => 2,
                    'min_order_amount' => 1500.00
                ]);
            }

            $location = Location::where('site_id', $siteId)->first();
            
            return response()->json([
                'success' => true,
                'data' => [
                    'settings' => $settings,
                    'location' => $location ? [
                        'id' => $location->id,
                        'name' => $location->name,
                        'site_id' => $location->site_id,
                        'woocommerce_key' => $location->woocommerce_key,
                        'woocommerce_secret' => $location->woocommerce_secret,
                        'woocommerce_url' => $location->woocommerce_url
                    ] : null
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching catering settings: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch catering settings'
            ], 500);
        }
    }

    /**
     * Check if a date is available for catering
     */
    public function checkAvailability(Request $request, $siteId)
    {
        $request->validate([
            'date' => 'required|date|after:today'
        ]);

        try {
            $settings = CateringSettings::where('site_id', $siteId)->first();
            
            if (!$settings || !$settings->catering_enabled) {
                return response()->json([
                    'success' => false,
                    'available' => false,
                    'message' => 'Catering is not available for this location'
                ]);
            }

            $requestedDate = Carbon::parse($request->date);
            $today = Carbon::today();
            $daysDifference = $today->diffInDays($requestedDate);

            if ($daysDifference < $settings->advance_notice_days) {
                return response()->json([
                    'success' => false,
                    'available' => false,
                    'message' => "Catering orders must be placed at least {$settings->advance_notice_days} days in advance"
                ]);
            }

            if ($settings->isDateBlocked($request->date)) {
                return response()->json([
                    'success' => false,
                    'available' => false,
                    'message' => 'This date is not available for catering'
                ]);
            }

            return response()->json([
                'success' => true,
                'available' => true,
                'message' => 'Date is available for catering'
            ]);
        } catch (\Exception $e) {
            Log::error('Error checking catering availability: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to check availability'
            ], 500);
        }
    }

    /**
     * Get blocked dates for a location
     */
    public function getBlockedDates($siteId)
    {
        try {
            $settings = CateringSettings::where('site_id', $siteId)->first();
            
            return response()->json([
                'success' => true,
                'blocked_dates' => $settings ? $settings->blocked_dates : []
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching blocked dates: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch blocked dates'
            ], 500);
        }
    }

    /**
     * Create a new catering order
     */
    public function createOrder(Request $request)
    {
        $request->validate([
            'site' => 'required|integer',
            'fornavn' => 'required|string',
            'etternavn' => 'required|string',
            'telefon' => 'required|string',
            'epost' => 'required|email',
            'delivery_date' => 'required|date|after:today',
            'delivery_time' => 'required|string',
            'delivery_address' => 'required|string',
            'number_of_guests' => 'required|integer|min:1',
            'ordreid' => 'required|integer',
            'paymentmethod' => 'required|string',
            'special_requirements' => 'nullable|string',
            'catering_notes' => 'nullable|string',
            'total_amount' => 'required|numeric'
        ]);

        try {
            $settings = CateringSettings::where('site_id', $request->site)->first();
            
            if (!$settings || !$settings->catering_enabled) {
                return response()->json([
                    'success' => false,
                    'message' => 'Catering is not available for this location'
                ], 400);
            }

            if ($request->number_of_guests < $settings->min_guests) {
                return response()->json([
                    'success' => false,
                    'message' => "Minimum {$settings->min_guests} guests required for catering"
                ], 400);
            }

            if ($request->total_amount < $settings->min_order_amount) {
                return response()->json([
                    'success' => false,
                    'message' => "Minimum order amount is {$settings->min_order_amount} NOK"
                ], 400);
            }

            $order = Order::create([
                'fornavn' => $request->fornavn,
                'etternavn' => $request->etternavn,
                'telefon' => $request->telefon,
                'epost' => $request->epost,
                'ordreid' => $request->ordreid,
                'site' => $request->site,
                'datetime' => now(),
                'paymentmethod' => $request->paymentmethod,
                'ordrestatus' => '1',
                'paid' => false,
                'is_catering' => true,
                'delivery_date' => $request->delivery_date,
                'delivery_time' => $request->delivery_time,
                'delivery_address' => $request->delivery_address,
                'number_of_guests' => $request->number_of_guests,
                'special_requirements' => $request->special_requirements,
                'catering_notes' => $request->catering_notes,
                'catering_status' => 'pending',
                'catering_email' => $settings->catering_email,
                'total_amount' => $request->total_amount
            ]);

            $this->sendCateringNotifications($order, $settings);

            return response()->json([
                'success' => true,
                'message' => 'Catering order created successfully',
                'order_id' => $order->id
            ]);
        } catch (\Exception $e) {
            Log::error('Error creating catering order: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create catering order'
            ], 500);
        }
    }

    /**
     * Mark catering order as paid
     */
    public function markPaid(Request $request)
    {
        $request->validate([
            'order_id' => 'required|integer'
        ]);

        try {
            $order = Order::where('ordreid', $request->order_id)
                         ->where('is_catering', true)
                         ->first();

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Catering order not found'
                ], 404);
            }

            $order->update([
                'paid' => true,
                'catering_status' => 'confirmed'
            ]);

            $this->sendPaymentConfirmation($order);

            return response()->json([
                'success' => true,
                'message' => 'Catering order marked as paid'
            ]);
        } catch (\Exception $e) {
            Log::error('Error marking catering order as paid: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update order'
            ], 500);
        }
    }

    /**
     * Send notifications for new catering order
     */
    private function sendCateringNotifications($order, $settings)
    {
        try {
            $customerMessage = "Takk for din cateringbestilling! \n";
            $customerMessage .= "Ordre #{$order->ordreid} \n";
            $customerMessage .= "Levering: {$order->delivery_date} kl. {$order->delivery_time} \n";
            $customerMessage .= "Antall gjester: {$order->number_of_guests} \n";
            $customerMessage .= "Du vil motta en bekreftelse når bestillingen er godkjent.";

            $this->sendSMS($order->telefon, $customerMessage);

            if ($order->epost) {
                $this->sendEmail(
                    $order->epost,
                    'Cateringbestilling mottatt - Aroi',
                    $customerMessage
                );
            }

            if ($settings->catering_email) {
                $adminMessage = "Ny cateringbestilling mottatt! \n";
                $adminMessage .= "Ordre #{$order->ordreid} \n";
                $adminMessage .= "Kunde: {$order->fornavn} {$order->etternavn} \n";
                $adminMessage .= "Telefon: {$order->telefon} \n";
                $adminMessage .= "Levering: {$order->delivery_date} kl. {$order->delivery_time} \n";
                $adminMessage .= "Adresse: {$order->delivery_address} \n";
                $adminMessage .= "Antall gjester: {$order->number_of_guests} \n";
                
                if ($order->special_requirements) {
                    $adminMessage .= "Spesielle krav: {$order->special_requirements} \n";
                }

                $this->sendEmail(
                    $settings->catering_email,
                    'Ny cateringbestilling - Aroi',
                    $adminMessage
                );
            }
        } catch (\Exception $e) {
            Log::error('Error sending catering notifications: ' . $e->getMessage());
        }
    }

    /**
     * Send payment confirmation
     */
    private function sendPaymentConfirmation($order)
    {
        try {
            $message = "Din cateringbestilling er bekreftet! \n";
            $message .= "Ordre #{$order->ordreid} \n";
            $message .= "Vi gleder oss til å levere til dere {$order->delivery_date} kl. {$order->delivery_time}";

            $this->sendSMS($order->telefon, $message);

            if ($order->epost) {
                $this->sendEmail(
                    $order->epost,
                    'Cateringbestilling bekreftet - Aroi',
                    $message
                );
            }
        } catch (\Exception $e) {
            Log::error('Error sending payment confirmation: ' . $e->getMessage());
        }
    }

    /**
     * Send SMS using existing functionality
     */
    private function sendSMS($phone, $message)
    {
        $apiController = new ApiController();
        return $apiController->sendSms($phone, $message);
    }

    /**
     * Send email
     */
    private function sendEmail($to, $subject, $body)
    {
        try {
            \Mail::raw($body, function ($message) use ($to, $subject) {
                $message->to($to)
                       ->subject($subject)
                       ->from('post@hungryeyes.no', 'Aroi Food Truck');
            });
            return true;
        } catch (\Exception $e) {
            Log::error('Error sending email: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Update catering status
     */
    public function updateStatus(Request $request, $orderId)
    {
        $request->validate([
            'status' => 'required|in:pending,confirmed,preparing,ready,delivered,cancelled'
        ]);

        try {
            $order = Order::find($orderId);
            
            if (!$order || !$order->is_catering) {
                return response()->json([
                    'success' => false,
                    'message' => 'Catering order not found'
                ], 404);
            }

            $order->updateCateringStatus($request->status);

            if ($request->status === 'ready') {
                $message = "Din cateringbestilling er klar! \n";
                $message .= "Ordre #{$order->ordreid}";
                $this->sendSMS($order->telefon, $message);
            }

            return response()->json([
                'success' => true,
                'message' => 'Status updated successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating catering status: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update status'
            ], 500);
        }
    }
}
