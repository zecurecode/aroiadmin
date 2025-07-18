<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\CateringSettings;
use App\Models\Location;

class CateringSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $locations = Location::all();

        foreach ($locations as $location) {
            CateringSettings::updateOrCreate(
                ['site_id' => $location->site_id],
                [
                    'catering_email' => 'catering@' . strtolower($location->name) . '.aroiasia.no',
                    'catering_enabled' => true,
                    'min_guests' => 10,
                    'advance_notice_days' => 2,
                    'min_order_amount' => 1500,
                    'catering_info' => 'Vi tilbyr catering for alle typer arrangementer. Kontakt oss for spesialtilpasninger.',
                    'blocked_dates' => [],
                    'delivery_times' => [
                        '10:00', '11:00', '12:00', '13:00', 
                        '14:00', '15:00', '16:00', '17:00', 
                        '18:00', '19:00', '20:00'
                    ],
                    'delivery_areas' => [
                        $location->name . ' og omegn',
                        'Inntil 30 minutter kjøring fra ' . $location->name
                    ]
                ]
            );
        }
    }
}