<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\Site;
use App\Models\OpeningHours;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PublicController extends Controller
{
    /**
     * Show all locations with opening hours and status
     */
        public function locations()
    {
        // Get all active locations
        $locations = Location::where('active', true)->orderBy('name')->get();

        // Get today's day name and current time
        $todayDayEnglish = Carbon::now()->format('l'); // Monday, Tuesday, etc.
        $currentTime = Carbon::now();
        
        // Map English days to Norwegian
        $dayMapping = [
            'Monday' => 'Mandag',
            'Tuesday' => 'Tirsdag',
            'Wednesday' => 'Onsdag',
            'Thursday' => 'Torsdag',
            'Friday' => 'Fredag',
            'Saturday' => 'Lørdag',
            'Sunday' => 'Søndag'
        ];
        
        $todayDay = $dayMapping[$todayDayEnglish] ?? $todayDayEnglish;

        // Get opening hours for today
        $openingHours = OpeningHours::where('day', $todayDay)->first();

        // Get all days opening hours for displaying full schedule
        $allOpeningHours = OpeningHours::orderByRaw("FIELD(day, 'Mandag', 'Tirsdag', 'Onsdag', 'Torsdag', 'Fredag', 'Lørdag', 'Søndag')")->get();

        // Prepare location data with opening hours and status
        $locationsData = [];

        foreach ($locations as $location) {
            $locationName = strtolower($location->name);

            $locationData = [
                'id' => $location->id,
                'site_id' => $location->site_id,
                'name' => $location->name,
                'phone' => $location->phone,
                'email' => $location->email,
                'address' => $location->address,
                'url' => $this->getLocationUrl($location->site_id),
                'maps_url' => $this->getGoogleMapsUrl($location->address),
                'is_open' => false,
                'open_time' => null,
                'close_time' => null,
                'status' => 0,
                'is_closed_today' => false,
                'special_notes' => null,
                'weekly_hours' => [],
            ];

            // Get today's opening hours
            if ($openingHours) {
                $openTime = $openingHours->getOpenTime($locationName);
                $closeTime = $openingHours->getCloseTime($locationName);
                $status = $openingHours->getStatus($locationName);
                $notes = $openingHours->getNotes($locationName);

                $locationData['open_time'] = $openTime;
                $locationData['close_time'] = $closeTime;
                $locationData['status'] = $status;
                $locationData['special_notes'] = $notes;

                // Check if currently open (changed logic - if there are opening hours, consider it open today)
                if ($openTime && $closeTime) {
                    // Remove any whitespace and handle both H:i and H:i:s formats
                    $openTime = trim($openTime);
                    $closeTime = trim($closeTime);
                    
                    // Don't use Carbon for simple time comparison
                    $openTimeFormatted = strlen($openTime) == 5 ? $openTime . ':00' : $openTime;
                    $closeTimeFormatted = strlen($closeTime) == 5 ? $closeTime . ':00' : $closeTime;
                    
                    // Check if location is open AND status is 1 (open)
                    $currentTimeStr = $currentTime->format('H:i:s');
                    $isWithinHours = ($currentTimeStr >= $openTimeFormatted && $currentTimeStr <= $closeTimeFormatted);
                    $locationData['is_open'] = $isWithinHours && $status == 1;
                    $locationData['is_closed_today'] = false; // Has opening hours today
                } else {
                    $locationData['is_closed_today'] = true; // No opening hours today
                }
            }

            // Get weekly schedule
            foreach ($allOpeningHours as $dayHours) {
                $dayOpenTime = $dayHours->getOpenTime($locationName);
                $dayCloseTime = $dayHours->getCloseTime($locationName);
                $dayStatus = $dayHours->getStatus($locationName);
                $dayNotes = $dayHours->getNotes($locationName);

                $locationData['weekly_hours'][] = [
                    'day' => $dayHours->day,
                    'open_time' => $dayOpenTime,
                    'close_time' => $dayCloseTime,
                    'status' => $dayStatus,
                    'notes' => $dayNotes,
                    'is_today' => $dayHours->day === $todayDay,
                    'is_open' => ($dayOpenTime && $dayCloseTime && $dayStatus == 1)
                ];
            }

            $locationsData[] = $locationData;
        }

        return view('public.locations', [
            'locations' => $locationsData,
            'today' => Carbon::now()->format('l, d. F Y'),
            'current_day' => $todayDay . ' (' . $todayDayEnglish . ')'
        ]);
    }

    /**
     * Get the URL for a location based on site_id
     */
    private function getLocationUrl($siteId)
    {
        $site = Site::where('site_id', $siteId)->first();
        return $site ? $site->url : '#';
    }

    /**
     * Generate Google Maps URL for directions
     */
    private function getGoogleMapsUrl($address)
    {
        if (!$address) {
            return '#';
        }

        $encodedAddress = urlencode($address);
        return "https://www.google.com/maps/dir/?api=1&destination={$encodedAddress}";
    }
}
