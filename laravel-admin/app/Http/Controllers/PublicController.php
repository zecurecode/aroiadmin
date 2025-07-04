<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\Site;
use App\Models\AvdelingAlternative;
use App\Models\ApningstidAlternative;
use App\Models\SpecialHours;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PublicController extends Controller
{
    /**
     * Show all locations with opening hours and status
     */
    public function locations()
    {
        // Get all active locations with their opening hours
        $locations = Location::where('active', true)
            ->orderBy('name')
            ->get();

        // Get today's information
        $today = Carbon::now();
        $todayDayEnglish = $today->format('l'); // Monday, Tuesday, etc.
        $todayDate = $today->format('Y-m-d');
        
        // Prepare location data with opening hours and status
        $locationsData = [];

        foreach ($locations as $location) {
            // Get the corresponding avdeling and opening hours from _apningstid table
            $avdeling = AvdelingAlternative::where('Id', $location->site_id)->first();
            if (!$avdeling) {
                continue; // Skip if no avdeling found
            }

            $openingHours = ApningstidAlternative::where('AvdID', $avdeling->Id)->first();
            if (!$openingHours) {
                continue; // Skip if no opening hours found
            }

            // Check for special hours for today
            $specialHours = SpecialHours::where('location_id', $avdeling->Id)
                ->where('date', '<=', $todayDate)
                ->where(function($query) use ($todayDate) {
                    $query->whereNull('end_date')
                          ->orWhere('end_date', '>=', $todayDate);
                })
                ->first();

            $locationData = [
                'id' => $location->id,
                'site_id' => $location->site_id,
                'name' => $location->name,
                'phone' => $location->phone,
                'email' => $location->email,
                'address' => $location->address,
                'url' => $location->order_url ?: $this->getLocationUrl($location->site_id), // Use order_url from location if available
                'maps_url' => $this->getGoogleMapsUrl($location->address),
                'is_open' => false,
                'open_time' => null,
                'close_time' => null,
                'is_closed_today' => false,
                'special_notes' => null,
                'weekly_hours' => [],
            ];

            // If there are special hours for today, use them
            if ($specialHours) {
                if ($specialHours->is_closed) {
                    $locationData['is_closed_today'] = true;
                    $locationData['special_notes'] = $specialHours->reason;
                } else {
                    $locationData['open_time'] = $specialHours->open_time;
                    $locationData['close_time'] = $specialHours->close_time;
                    $locationData['special_notes'] = $specialHours->reason;
                    
                    // Check if currently open
                    if ($specialHours->open_time && $specialHours->close_time) {
                        $openTime = Carbon::createFromTimeString($specialHours->open_time);
                        $closeTime = Carbon::createFromTimeString($specialHours->close_time);
                        $locationData['is_open'] = $today->between($openTime, $closeTime);
                    }
                }
            } else {
                // Use regular hours from _apningstid table
                $dayHours = $openingHours->getHoursForDay(strtolower($todayDayEnglish));
                
                if ($dayHours && !$dayHours['closed'] && !$openingHours->isSeasonClosed()) {
                    $locationData['open_time'] = $dayHours['start'];
                    $locationData['close_time'] = $dayHours['stop'];
                    
                    // Check if currently open
                    if ($dayHours['start'] && $dayHours['stop']) {
                        $openTime = Carbon::createFromTimeString($dayHours['start']);
                        $closeTime = Carbon::createFromTimeString($dayHours['stop']);
                        $locationData['is_open'] = $today->between($openTime, $closeTime);
                    }
                } else {
                    $locationData['is_closed_today'] = true;
                    if ($openingHours->isSeasonClosed()) {
                        $locationData['special_notes'] = $openingHours->StengtMelding ?: 'Sesongstengt';
                    }
                }
            }

            // Get weekly schedule
            $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
            $dayTranslations = [
                'monday' => 'Mandag',
                'tuesday' => 'Tirsdag',
                'wednesday' => 'Onsdag',
                'thursday' => 'Torsdag',
                'friday' => 'Fredag',
                'saturday' => 'Lørdag',
                'sunday' => 'Søndag'
            ];

            foreach ($days as $day) {
                $dayCapitalized = ucfirst($day);
                $norwegianDay = $dayTranslations[$day];
                
                // Check for special hours for this day in the current week
                $dayDate = $today->copy()->next($dayCapitalized);
                if ($today->format('l') == $dayCapitalized) {
                    $dayDate = $today;
                }
                
                $daySpecialHours = SpecialHours::where('location_id', $avdeling->Id)
                    ->where('date', '<=', $dayDate->format('Y-m-d'))
                    ->where(function($query) use ($dayDate) {
                        $query->whereNull('end_date')
                              ->orWhere('end_date', '>=', $dayDate->format('Y-m-d'));
                    })
                    ->first();

                if ($daySpecialHours) {
                    $locationData['weekly_hours'][] = [
                        'day' => $dayCapitalized,
                        'open_time' => $daySpecialHours->is_closed ? null : $daySpecialHours->open_time,
                        'close_time' => $daySpecialHours->is_closed ? null : $daySpecialHours->close_time,
                        'is_today' => strtolower($todayDayEnglish) == $day,
                        'is_open' => !$daySpecialHours->is_closed
                    ];
                } else {
                    $dayHours = $openingHours->getHoursForDay($day);
                    $locationData['weekly_hours'][] = [
                        'day' => $dayCapitalized,
                        'open_time' => $dayHours['closed'] || $openingHours->isSeasonClosed() ? null : $dayHours['start'],
                        'close_time' => $dayHours['closed'] || $openingHours->isSeasonClosed() ? null : $dayHours['stop'],
                        'is_today' => strtolower($todayDayEnglish) == $day,
                        'is_open' => !$dayHours['closed'] && !$openingHours->isSeasonClosed()
                    ];
                }
            }

            $locationsData[] = $locationData;
        }

        return view('public.locations', [
            'locations' => $locationsData,
            'today' => $today->locale('nb')->isoFormat('dddd, D. MMMM YYYY'),
            'current_day' => $todayDayEnglish
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
