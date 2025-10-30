<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Location;
use App\Models\Setting;
use Illuminate\Support\Facades\Log;

class SmsService
{
    /**
     * Send "order received" SMS to customer.
     *
     * @param Order $order
     * @return bool Success status
     */
    public function sendOrderReceivedSms(Order $order)
    {
        // Check if SMS already sent
        if ($order->sms) {
            Log::info("Skipping SMS for order {$order->ordreid} - already sent");
            return false;
        }

        // Check if order is paid
        if (!$order->paid) {
            Log::info("Skipping SMS for unpaid order {$order->ordreid}");
            return false;
        }

        // Check if phone number exists
        if (empty($order->telefon)) {
            Log::warning("No phone number for order {$order->ordreid}");
            return false;
        }

        // Get location name
        $locationName = Location::getNameBySiteId($order->site);

        // Build "order received" message
        $message = "Hei {$order->fornavn}! Vi har mottatt din ordre #{$order->ordreid}. "
                 . "Vi vil gjøre bestillingen klar så fort vi kan. "
                 . "Du får en ny melding når maten er klar til henting. "
                 . "Mvh {$locationName}";

        return $this->sendSms($order, $message);
    }

    /**
     * Send SMS to customer.
     *
     * @param Order $order
     * @param string $message
     * @return bool Success status
     */
    public function sendSms(Order $order, $message)
    {
        // Normalize phone number to +47 format
        $phoneNormalized = $this->normalizePhoneNumber($order->telefon);

        // Get SMS credentials from settings
        $username = Setting::get('sms_api_username', 'b3166vr0f0l');
        $password = Setting::get('sms_api_password', '2tm2bxuIo2AixNELhXhwCdP8');
        $apiUrl = Setting::get('sms_api_url', 'https://api1.teletopiasms.no/gateway/v3/plain');
        $sender = Setting::get('sms_sender', 'AroiAsia');

        // Build SMS URL using same method as working SMS (GET request)
        $smsUrl = $apiUrl . "?" . http_build_query([
            'username' => $username,
            'password' => $password,
            'recipient' => $phoneNormalized,
            'text' => $message,
            'from' => $sender
        ]);

        Log::info("Sending SMS for order {$order->ordreid}", [
            'phone_original' => $order->telefon,
            'phone_normalized' => $phoneNormalized,
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

            if ($curlError) {
                Log::error("CURL error sending SMS for order {$order->ordreid}: " . $curlError);
                return false;
            }

            if ($httpcode == 200) {
                $order->update(['sms' => true]);
                Log::info("SMS sent successfully for order {$order->ordreid}", [
                    'http_code' => $httpcode,
                    'response' => $output
                ]);
                return true;
            } else {
                Log::error("Failed to send SMS for order {$order->ordreid}", [
                    'http_code' => $httpcode,
                    'response' => $output,
                    'curl_error' => $curlError
                ]);
                return false;
            }
        } catch (\Exception $e) {
            Log::error("Exception sending SMS for order {$order->ordreid}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Normalize Norwegian phone number to +47 format.
     *
     * @param string $phone
     * @return string
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
}
