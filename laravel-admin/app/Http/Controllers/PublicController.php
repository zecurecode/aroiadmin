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
    public function locations(Request $request)
    {
        // Get all active locations with their opening hours
        $query = Location::where('active', true);

        // Apply sorting
        if ($request->has('sort')) {
            switch ($request->sort) {
                case 'name':
                    $query->orderBy('name');
                    break;
                case 'group':
                    $query->orderBy('group_name')->orderBy('display_order')->orderBy('name');
                    break;
                default:
                    $query->ordered(); // Use display_order and name
            }
        } else {
            $query->ordered(); // Default to custom ordering
        }

        $locations = $query->get();

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
                // Fallback: still show the location even if avdeling mapping is missing
                $locationsData[] = [
                    'id' => $location->id,
                    'site_id' => $location->site_id,
                    'name' => $location->name,
                    'group_name' => $location->group_name,
                    'display_order' => $location->display_order,
                    'phone' => $location->phone,
                    'email' => $location->email,
                    'address' => $location->address,
                    'url' => $location->order_url ?: $this->getLocationUrl($location->site_id),
                    'maps_url' => $this->getGoogleMapsUrl($location->address),
                    'is_open' => false,
                    'open_time' => null,
                    'close_time' => null,
                    'is_closed_today' => true,
                    'past_closing_time' => false,
                    'next_opening_time' => null,
                    'next_opening_day' => null,
                    'special_notes' => null,
                    'weekly_hours' => [],
                ];
                continue;
            }

            $openingHours = ApningstidAlternative::where('AvdID', $avdeling->Id)->first();
            if (!$openingHours) {
                // Fallback: show the location but mark as closed (no hours available)
                $locationsData[] = [
                    'id' => $location->id,
                    'site_id' => $location->site_id,
                    'name' => $location->name,
                    'group_name' => $location->group_name,
                    'display_order' => $location->display_order,
                    'phone' => $location->phone,
                    'email' => $location->email,
                    'address' => $location->address,
                    'url' => $location->order_url ?: $this->getLocationUrl($location->site_id),
                    'maps_url' => $this->getGoogleMapsUrl($location->address),
                    'is_open' => false,
                    'open_time' => null,
                    'close_time' => null,
                    'is_closed_today' => true,
                    'past_closing_time' => false,
                    'next_opening_time' => null,
                    'next_opening_day' => null,
                    'special_notes' => null,
                    'weekly_hours' => [],
                ];
                continue;
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
                'group_name' => $location->group_name,
                'display_order' => $location->display_order,
                'phone' => $location->phone,
                'email' => $location->email,
                'address' => $location->address,
                'url' => $location->order_url ?: $this->getLocationUrl($location->site_id), // Use order_url from location if available
                'maps_url' => $this->getGoogleMapsUrl($location->address),
                'is_open' => false,
                'open_time' => null,
                'close_time' => null,
                'is_closed_today' => false,
                'past_closing_time' => false,
                'next_opening_time' => null,
                'next_opening_day' => null,
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

                        // Check if past closing time for today
                        if ($today->gt($closeTime)) {
                            $locationData['past_closing_time'] = true;
                        }
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

                        // Check if past closing time for today
                        if ($today->gt($closeTime)) {
                            $locationData['past_closing_time'] = true;
                        }
                    }
                } else {
                    $locationData['is_closed_today'] = true;
                    if ($openingHours->isSeasonClosed()) {
                        $locationData['special_notes'] = $openingHours->StengtMelding ?: 'Sesongstengt';
                    }
                }
            }

            // Calculate next opening time if location is closed or past closing time
            if (!$locationData['is_open']) {
                $nextOpening = $this->getNextOpeningTime($openingHours, $today, $locationData['past_closing_time']);
                $locationData['next_opening_time'] = $nextOpening['time'];
                $locationData['next_opening_day'] = $nextOpening['day'];
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

        // Group locations by group_name if requested
        $groupedLocations = [];
        if ($request->has('group_by') && $request->group_by === 'region') {
            foreach ($locationsData as $location) {
                $groupName = $location['group_name'] ?: 'Andre';
                if (!isset($groupedLocations[$groupName])) {
                    $groupedLocations[$groupName] = [];
                }
                $groupedLocations[$groupName][] = $location;
            }
            ksort($groupedLocations);
        } else {
            $groupedLocations['all'] = $locationsData;
        }

        return view('public.locations', [
            'locations' => $locationsData,
            'groupedLocations' => $groupedLocations,
            'today' => $today->locale('nb')->isoFormat('dddd, D. MMMM YYYY'),
            'current_day' => $todayDayEnglish,
            'currentSort' => $request->get('sort', 'default'),
            'currentGroupBy' => $request->get('group_by', 'none'),
            'availableGroups' => Location::getGroups()
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

    /**
     * Calculate the next opening time for a location
     */
    private function getNextOpeningTime($openingHours, $currentTime, $pastClosingToday = false)
    {
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        $dayTranslations = [
            'monday' => 'mandag',
            'tuesday' => 'tirsdag',
            'wednesday' => 'onsdag',
            'thursday' => 'torsdag',
            'friday' => 'fredag',
            'saturday' => 'lørdag',
            'sunday' => 'søndag'
        ];

        // Start checking from tomorrow if past closing time today, otherwise start from today
        $checkDate = $pastClosingToday ? $currentTime->copy()->addDay() : $currentTime->copy();

        // Check up to 7 days ahead
        for ($i = 0; $i < 7; $i++) {
            $dayName = strtolower($checkDate->format('l')); // monday, tuesday, etc.
            $dayHours = $openingHours->getHoursForDay($dayName);

            // Skip if location is season closed
            if ($openingHours->isSeasonClosed()) {
                break;
            }

            // Check if location is open on this day
            if ($dayHours && !$dayHours['closed'] && $dayHours['start']) {
                $openTime = $dayHours['start'];

                // If it's today and we haven't passed opening time yet, return today's opening
                if ($i === 0 && !$pastClosingToday) {
                    $openDateTime = Carbon::createFromTimeString($openTime);
                    if ($currentTime->lt($openDateTime)) {
                        return [
                            'time' => $openTime,
                            'day' => 'i dag'
                        ];
                    }
                }

                // If it's tomorrow or later, return this opening time
                if ($i > 0 || $pastClosingToday) {
                    $dayText = $i === 1 || ($i === 0 && $pastClosingToday) ? 'i morgen' : $dayTranslations[$dayName];
                    return [
                        'time' => $openTime,
                        'day' => $dayText
                    ];
                }
            }

            $checkDate->addDay();
        }

        // If no opening found in the next 7 days
        return [
            'time' => null,
            'day' => null
        ];
    }
}
