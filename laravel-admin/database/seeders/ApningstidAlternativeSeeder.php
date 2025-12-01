<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ApningstidAlternativeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Mapping of locations from old table to new structure
        $locationMapping = [
            'namsos' => ['AvdID' => 7, 'Navn' => 'Namsos', 'Telefon' => '74217171'],
            'lade' => ['AvdID' => 4, 'Navn' => 'Lade', 'Telefon' => '73123456'],
            'moan' => ['AvdID' => 6, 'Navn' => 'Moan', 'Telefon' => '73654321'],
            'gramyra' => ['AvdID' => 5, 'Navn' => 'Gramyra', 'Telefon' => '73987654'],
            'frosta' => ['AvdID' => 10, 'Navn' => 'Frosta', 'Telefon' => '74456789'],
            'hell' => ['AvdID' => 11, 'Navn' => 'Hell', 'Telefon' => '73147258'],
            'steinkjer' => ['AvdID' => 13, 'Navn' => 'Steinkjer', 'Telefon' => '74369852'],
        ];

        // Day mapping from old table structure to new (Norwegian day names)
        $dayMapping = [
            'Mandag' => 'Man',
            'Tirsdag' => 'Tir',
            'Onsdag' => 'Ons',
            'Torsdag' => 'Tor',
            'Fredag' => 'Fre',
            'Lørdag' => 'Lor',
            'Søndag' => 'Son',
        ];

        echo "Reading existing opening hours data...\n";

        // Get existing data from old table
        $oldOpeningHours = DB::table('apningstid')->get();

        if ($oldOpeningHours->isEmpty()) {
            echo "No data found in apningstid table. Seeding with default data...\n";
            $this->seedDefaultData($locationMapping);

            return;
        }

        echo 'Found '.$oldOpeningHours->count()." days of opening hours data\n";

        // Clear existing data in new table
        DB::table('_apningstid')->truncate();

        // Process each location
        foreach ($locationMapping as $locationKey => $locationInfo) {
            echo "Processing location: {$locationInfo['Navn']}\n";

            $locationData = [
                'AvdID' => $locationInfo['AvdID'],
                'Navn' => $locationInfo['Navn'],
                'Telefon' => $locationInfo['Telefon'],
                'StengtMelding' => '',
                'SesongStengt' => 0,
                'url' => '',
            ];

            // Process each day
            foreach ($oldOpeningHours as $dayData) {
                $dayName = $dayData->day;

                if (! isset($dayMapping[$dayName])) {
                    continue;
                }

                $newDayPrefix = $dayMapping[$dayName];

                // Get opening hours for this location and day
                $openField = 'open'.$locationKey;
                $closeField = 'close'.$locationKey;
                $statusField = 'status'.$locationKey;

                $openTime = $dayData->$openField ?? null;
                $closeTime = $dayData->$closeField ?? null;
                $status = $dayData->$statusField ?? 0;

                // Set the fields for this day (normalize time format and handle status)
                $normalizedOpenTime = $this->normalizeTime($openTime);
                $normalizedCloseTime = $this->normalizeTime($closeTime);

                $locationData[$newDayPrefix.'Start'] = $normalizedOpenTime;
                $locationData[$newDayPrefix.'Stopp'] = $normalizedCloseTime;

                // Set closed status: 1 if status is 0 OR if times indicate closed (00:00:00)
                $isClosed = ($status == 0) || ($normalizedOpenTime === '00:00:00') || ($normalizedCloseTime === '00:00:00');
                $locationData[$newDayPrefix.'Stengt'] = $isClosed ? 1 : 0;
            }

            // Insert this location's data
            try {
                DB::table('_apningstid')->insert($locationData);
                echo "  ✓ Inserted data for {$locationInfo['Navn']}\n";
            } catch (\Exception $e) {
                echo "  ✗ Error inserting data for {$locationInfo['Navn']}: ".$e->getMessage()."\n";
            }
        }

        echo "Migration completed!\n";
    }

    /**
     * Seed with default data if no existing data found
     */
    private function seedDefaultData($locationMapping)
    {
        // Default opening hours: 11:00 - 21:00, open Monday to Sunday
        $defaultHours = [
            'ManStart' => '11:00:00', 'ManStopp' => '21:00:00', 'ManStengt' => 0,
            'TirStart' => '11:00:00', 'TirStopp' => '21:00:00', 'TirStengt' => 0,
            'OnsStart' => '11:00:00', 'OnsStopp' => '21:00:00', 'OnsStengt' => 0,
            'TorStart' => '11:00:00', 'TorStopp' => '21:00:00', 'TorStengt' => 0,
            'FreStart' => '11:00:00', 'FreStopp' => '21:00:00', 'FreStengt' => 0,
            'LorStart' => '14:00:00', 'LorStopp' => '20:00:00', 'LorStengt' => 0,
            'SonStart' => '14:00:00', 'SonStopp' => '20:00:00', 'SonStengt' => 0,
        ];

        foreach ($locationMapping as $locationInfo) {
            $locationData = array_merge([
                'AvdID' => $locationInfo['AvdID'],
                'Navn' => $locationInfo['Navn'],
                'Telefon' => $locationInfo['Telefon'],
                'StengtMelding' => '',
                'SesongStengt' => 0,
                'url' => '',
            ], $defaultHours);

            DB::table('_apningstid')->insert($locationData);
            echo "  ✓ Created default data for {$locationInfo['Navn']}\n";
        }
    }

    /**
     * Normalize time format from old table (handles both ":" and "." separators)
     */
    private function normalizeTime($time)
    {
        if (empty($time)) {
            return null;
        }

        // Replace dots with colons
        $normalized = str_replace('.', ':', $time);

        // Handle cases like "11:00" -> "11:00:00"
        if (preg_match('/^\d{1,2}:\d{2}$/', $normalized)) {
            $normalized .= ':00';
        }

        // Handle cases like "00:01" or "00:02" (likely means closed)
        if ($normalized === '00:01:00' || $normalized === '00:02:00') {
            return '00:00:00'; // Return 00:00:00 for closed days instead of null
        }

        return $normalized;
    }
}
