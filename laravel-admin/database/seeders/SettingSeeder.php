<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Setting;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            [
                'key' => 'teletopia_username',
                'value' => 'b3166vr0f0l',
                'description' => 'Teletopia SMS API Username',
                'type' => 'text',
            ],
            [
                'key' => 'teletopia_password',
                'value' => '2tm2bxuIo2AixNELhXhwCdP8',
                'description' => 'Teletopia SMS API Password',
                'type' => 'password',
            ],
            [
                'key' => 'teletopia_api_url',
                'value' => 'https://api1.teletopiasms.no/gateway/v3/plain',
                'description' => 'Teletopia SMS API URL',
                'type' => 'url',
            ],
            [
                'key' => 'pckasse_base_url',
                'value' => 'https://min.pckasse.no/QueueGetOrders.aspx',
                'description' => 'PCKasse API Base URL',
                'type' => 'url',
            ],
            [
                'key' => 'database_host',
                'value' => '141.94.143.8:3306',
                'description' => 'Database Host',
                'type' => 'text',
            ],
            [
                'key' => 'database_username',
                'value' => 'adminaroi',
                'description' => 'Database Username',
                'type' => 'text',
            ],
            [
                'key' => 'database_password',
                'value' => 'b^754Xws',
                'description' => 'Database Password',
                'type' => 'password',
            ],
            [
                'key' => 'database_name',
                'value' => 'admin_aroi',
                'description' => 'Database Name',
                'type' => 'text',
            ],
            [
                'key' => 'sms_default_message',
                'value' => 'Takk for din ordre. Vi vil gjøre din bestilling klar så fort vi kan. Vi sender deg en ny SMS når maten er klar til henting. Ditt referansenummer er {order_id}',
                'description' => 'Default SMS message template (use {order_id} for order number)',
                'type' => 'textarea',
            ],
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
    }
}
