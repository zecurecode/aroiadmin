<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OpeningHoursSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $days = [
            'Monday',
            'Tuesday',
            'Wednesday',
            'Thursday',
            'Friday',
            'Saturday',
            'Sunday',
        ];

        foreach ($days as $day) {
            DB::table('apningstid')->insert([
                'userid' => 1, // Default user ID
                'day' => $day,

                // Namsos (site 7)
                'opennamsos' => '11:00:00',
                'closenamsos' => '21:00:00',
                'statusnamsos' => 1,
                'notesnamsos' => null,

                // Lade (site 4)
                'openlade' => '11:00:00',
                'closelade' => '21:00:00',
                'statuslade' => 1,
                'noteslade' => null,

                // Moan (site 6)
                'openmoan' => '11:00:00',
                'closemoan' => '21:00:00',
                'statusmoan' => 1,
                'notesmoan' => null,

                // Gramyra (site 5)
                'opengramyra' => '11:00:00',
                'closegramyra' => '21:00:00',
                'statusgramyra' => 1,
                'notesgramyra' => null,

                // Frosta (site 10)
                'openfrosta' => '11:00:00',
                'closefrosta' => '21:00:00',
                'statusfrosta' => 1,
                'notesfrosta' => null,

                // Hell (site 11)
                'openhell' => '11:00:00',
                'closehell' => '21:00:00',
                'statushell' => 1,
                'noteshell' => null,

                // Steinkjer (site 13)
                'opensteinkjer' => '11:00:00',
                'closesteinkjer' => '21:00:00',
                'statussteinkjer' => 1,
                'notessteinkjer' => null,
            ]);
        }
    }
}
