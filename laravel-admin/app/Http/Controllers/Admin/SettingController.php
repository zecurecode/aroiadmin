<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    /**
     * Display the settings page.
     */
    public function index()
    {
        $settings = Setting::orderBy('key')->get()->groupBy(function ($setting) {
            // Group settings by category
            $key = $setting->key;
            if (str_starts_with($key, 'sms_')) {
                return 'SMS Innstillinger';
            }
            if (str_starts_with($key, 'pckasse_')) {
                return 'PCKasse Innstillinger';
            }
            if (str_starts_with($key, 'onesignal_')) {
                return 'OneSignal Innstillinger';
            }
            if (str_starts_with($key, 'order_') || str_starts_with($key, 'failed_')) {
                return 'System Innstillinger';
            }

            return 'Generelle Innstillinger';
        });

        return view('admin.settings.index', compact('settings'));
    }

    /**
     * Update settings.
     */
    public function update(Request $request)
    {
        $settings = $request->input('settings', []);

        foreach ($settings as $key => $value) {
            Setting::where('key', $key)->update(['value' => $value]);
        }

        return redirect()->route('admin.settings.index')
            ->with('success', 'Settings updated successfully.');
    }

    /**
     * Create a new setting.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'key' => 'required|string|max:255|unique:settings',
            'value' => 'nullable|string',
            'description' => 'nullable|string|max:255',
            'type' => 'required|in:text,password,email,url,textarea,number',
        ]);

        Setting::create($validated);

        return redirect()->route('admin.settings.index')
            ->with('success', 'Setting created successfully.');
    }

    /**
     * Delete a setting.
     */
    public function destroy(Setting $setting)
    {
        $setting->delete();

        return redirect()->route('admin.settings.index')
            ->with('success', 'Setting deleted successfully.');
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
            return '+47'.substr($phone, 4);
        }

        // If starts with 47 (without +), add the +
        if (substr($phone, 0, 2) === '47' && strlen($phone) >= 10) {
            return '+'.$phone;
        }

        // If 8 digits (Norwegian mobile without country code), add +47
        if (strlen($phone) === 8 && ctype_digit($phone)) {
            return '+47'.$phone;
        }

        // Otherwise return as is (might be international number)
        return $phone;
    }

    /**
     * Test SMS configuration.
     */
    public function testSms(Request $request)
    {
        $validated = $request->validate([
            'phone' => 'required|string|max:20',
        ]);

        // Normalize phone number
        $phoneNumber = $this->normalizePhoneNumber($validated['phone']);

        $username = Setting::get('sms_api_username');
        $password = Setting::get('sms_api_password');
        $apiUrl = Setting::get('sms_api_url', 'https://api1.teletopiasms.no/gateway/v3/plain');
        $sender = Setting::get('sms_sender', 'AroiAsia');

        if (! $username || ! $password) {
            return response()->json([
                'success' => false,
                'message' => 'SMS innstillinger ikke konfigurert.',
            ]);
        }

        $message = 'Test melding fra Aroi Admin System';
        $smsUrl = $apiUrl.'?'.http_build_query([
            'username' => $username,
            'password' => $password,
            'recipient' => $phoneNumber,  // Teletopia uses 'recipient' not 'to'
            'text' => $message,
            'from' => $sender,
        ]);

        \Log::info('Sending test SMS', [
            'phone_original' => $validated['phone'],
            'phone_normalized' => $phoneNumber,
            'api_url' => $apiUrl,
        ]);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $smsUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $output = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        $success = $httpcode == 200;

        \Log::info('Test SMS result', [
            'success' => $success,
            'http_code' => $httpcode,
            'response' => $output,
            'curl_error' => $curlError,
        ]);

        return response()->json([
            'success' => $success,
            'message' => $success ? 'Test SMS sendt!' : "Kunne ikke sende test SMS. HTTP {$httpcode}",
            'phone_used' => $phoneNumber,
            'response' => $output,
            'http_code' => $httpcode,
        ]);
    }
}
