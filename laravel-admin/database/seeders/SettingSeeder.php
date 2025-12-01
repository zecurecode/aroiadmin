<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            // SMS API Settings (used in OrderController)
            [
                'key' => 'sms_api_username',
                'value' => 'b3166vr0f0l',
                'description' => 'SMS API Username (Teletopia)',
                'type' => 'text',
            ],
            [
                'key' => 'sms_api_password',
                'value' => '2tm2bxuIo2AixNELhXhwCdP8',
                'description' => 'SMS API Password (Teletopia)',
                'type' => 'password',
            ],
            [
                'key' => 'sms_api_url',
                'value' => 'https://api1.teletopiasms.no/gateway/v3/plain',
                'description' => 'SMS API URL (Teletopia)',
                'type' => 'url',
            ],
            [
                'key' => 'sms_sender',
                'value' => 'AroiAsia',
                'description' => 'SMS Sender Name',
                'type' => 'text',
            ],

            // PCKasse API Settings
            [
                'key' => 'pckasse_base_url',
                'value' => 'https://min.pckasse.no/QueueGetOrders.aspx',
                'description' => 'PCKasse API Base URL',
                'type' => 'url',
            ],

            // OneSignal Settings (from old system)
            [
                'key' => 'onesignal_app_id',
                'value' => '12fb0be8-d0bd-4a07-b26d-df9ab8f17a55',
                'description' => 'OneSignal App ID',
                'type' => 'text',
            ],
            [
                'key' => 'onesignal_rest_api_key',
                'value' => 'MDhmOWJiYTgtOTRmMy00Y2RjLWI1MGItNDFjNjE0OTJjODgx',
                'description' => 'OneSignal REST API Key',
                'type' => 'password',
            ],

            // System Settings
            [
                'key' => 'order_auto_delete_days',
                'value' => '14',
                'description' => 'Days before orders are automatically deleted',
                'type' => 'number',
            ],
            [
                'key' => 'failed_order_alert_minutes',
                'value' => '5',
                'description' => 'Minutes before unpaid order alert',
                'type' => 'number',
            ],

            // Default SMS Template
            [
                'key' => 'sms_template_default',
                'value' => 'Hei! Din ordre er klar for henting. Mvh {location}',
                'description' => 'Default SMS template (use {order_id} for order number, {location} for location name)',
                'type' => 'textarea',
            ],

            // Admin notification settings
            [
                'key' => 'admin_notification_phone',
                'value' => '4790039911,4796017450',
                'description' => 'Admin notification phone numbers (comma separated)',
                'type' => 'text',
            ],
        ];

        foreach ($settings as $settingData) {
            Setting::updateOrCreate(
                ['key' => $settingData['key']],
                $settingData
            );
        }

        echo "Settings seeded successfully!\n";
    }
}
