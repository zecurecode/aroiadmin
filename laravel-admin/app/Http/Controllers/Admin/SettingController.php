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
        $settings = Setting::orderBy('key')->get()->groupBy(function($setting) {
            // Group settings by category
            $key = $setting->key;
            if (str_starts_with($key, 'sms_')) return 'SMS Innstillinger';
            if (str_starts_with($key, 'pckasse_')) return 'PCKasse Innstillinger';
            if (str_starts_with($key, 'onesignal_')) return 'OneSignal Innstillinger';
            if (str_starts_with($key, 'order_') || str_starts_with($key, 'failed_')) return 'System Innstillinger';
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
     * Test SMS configuration.
     */
    public function testSms(Request $request)
    {
        $validated = $request->validate([
            'phone' => 'required|string|max:20',
        ]);

        $username = Setting::get('sms_api_username');
        $password = Setting::get('sms_api_password');
        $apiUrl = Setting::get('sms_api_url', 'https://api1.teletopiasms.no/gateway/v3/plain');
        $sender = Setting::get('sms_sender', 'AroiAsia');

        if (!$username || !$password) {
            return response()->json([
                'success' => false,
                'message' => 'SMS innstillinger ikke konfigurert.'
            ]);
        }

        $message = 'Test melding fra Aroi Admin System';
        $smsUrl = $apiUrl . '?' . http_build_query([
            'username' => $username,
            'password' => $password,
            'to' => $validated['phone'],
            'text' => $message,
            'from' => $sender,
        ]);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $smsUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $success = $httpcode == 200;

        return response()->json([
            'success' => $success,
            'message' => $success ? 'Test SMS sendt!' : 'Kunne ikke sende test SMS.',
            'response' => $output,
            'http_code' => $httpcode
        ]);
    }
}
