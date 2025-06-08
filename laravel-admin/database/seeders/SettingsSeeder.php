<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Setting;

class SettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // SMS API Settings (Teletopia)
        Setting::set('sms_api_url', 'https://api1.teletopiasms.no/gateway/v3/plain', 'SMS API URL', 'url');
        Setting::set('sms_api_username', 'b3166vr0f0l', 'SMS API Brukernavn', 'text');
        Setting::set('sms_api_password', '2tm2bxuIo2AixNELhXhwCdP8', 'SMS API Passord', 'password');
        Setting::set('sms_sender', 'AroiAsia', 'SMS Avsender', 'text');
        
        // PCKasse API Settings
        Setting::set('pckasse_api_url', 'https://db.pckasse.no/api/orders/store', 'PCKasse API URL', 'url');
        
        // OneSignal Settings (fra gamle systemet)
        Setting::set('onesignal_app_id', '12fb0be8-d0bd-4a07-b26d-df9ab8f17a55', 'OneSignal App ID', 'text');
        Setting::set('onesignal_rest_api_key', 'MDhmOWJiYTgtOTRmMy00Y2RjLWI1MGItNDFjNjE0OTJjODgx', 'OneSignal REST API Key', 'password');
        
        // System Settings
        Setting::set('order_auto_delete_days', '14', 'Dager før ordrer slettes automatisk', 'number');
        Setting::set('failed_order_alert_minutes', '5', 'Minutter før varsel om ubetalte ordrer', 'number');
        
        // Default SMS Messages per location
        Setting::set('sms_template_default', 'Hei! Din ordre er klar for henting. Mvh {location}', 'Standard SMS mal', 'textarea');
        
        echo "Settings seeded successfully!\n";
    }
}