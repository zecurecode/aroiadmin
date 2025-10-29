<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Location;
use App\Models\Site;
use App\Models\OpeningHours;
use App\Models\ApningstidAlternative;
use App\Models\AvdelingAlternative;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WordPressController extends Controller
{
    /**
     * Get delivery time for a specific location
     */
    public function getDeliveryTime($siteId)
    {
        $location = Location::where('site_id', $siteId)->first();

        if (!$location) {
            return response()->json(['error' => 'Location not found'], 404);
        }

        // Get delivery time from locations table (delivery_time_minutes column)
        $deliveryTime = $location->delivery_time_minutes ?? 30;

        return response()->json([
            'site_id' => $siteId,
            'delivery_time' => $deliveryTime,
            'location_name' => $location->name
        ]);
    }

    /**
     * Get opening hours for a specific location and day
     */
    public function getOpeningHours($siteId)
    {
        $location = Location::where('site_id', $siteId)->first();
        
        if (!$location) {
            return response()->json(['error' => 'Location not found'], 404);
        }

        $locationName = strtolower($location->name);
        $currentDay = Carbon::now()->locale('nb')->dayName;
        
        // Get opening hours from apningstid table
        $openingHours = OpeningHours::where('day', $currentDay)->first();
        
        if (!$openingHours) {
            return response()->json(['error' => 'Opening hours not found'], 404);
        }

        $openTime = $openingHours->getOpenTime($locationName);
        $closeTime = $openingHours->getCloseTime($locationName);
        $status = $openingHours->getStatus($locationName);
        $notes = $openingHours->getNotes($locationName);

        // Check if currently open
        $isOpen = false;
        if ($openTime && $closeTime && $status == 1) {
            $now = Carbon::now();
            $openTimeCarbon = Carbon::createFromTimeString($openTime);
            $closeTimeCarbon = Carbon::createFromTimeString($closeTime);
            $isOpen = $now->between($openTimeCarbon, $closeTimeCarbon);
        }

        return response()->json([
            'site_id' => $siteId,
            'location_name' => $location->name,
            'day' => $currentDay,
            'open_time' => $openTime,
            'close_time' => $closeTime,
            'status' => $status,
            'is_open' => $isOpen,
            'notes' => $notes
        ]);
    }

    /**
     * Get all opening hours for a location
     */
    public function getAllOpeningHours($siteId)
    {
        $location = Location::where('site_id', $siteId)->first();
        
        if (!$location) {
            return response()->json(['error' => 'Location not found'], 404);
        }

        $locationName = strtolower($location->name);
        
        // Get all opening hours from apningstid table
        $allHours = OpeningHours::all();
        
        $schedule = [];
        foreach ($allHours as $dayHours) {
            $schedule[] = [
                'day' => $dayHours->day,
                'open_time' => $dayHours->getOpenTime($locationName),
                'close_time' => $dayHours->getCloseTime($locationName),
                'status' => $dayHours->getStatus($locationName),
                'notes' => $dayHours->getNotes($locationName)
            ];
        }

        return response()->json([
            'site_id' => $siteId,
            'location_name' => $location->name,
            'schedule' => $schedule
        ]);
    }

    /**
     * Check if location is open now
     */
    public function isOpenNow($siteId)
    {
        $location = Location::where('site_id', $siteId)->first();
        
        if (!$location) {
            return response()->json(['error' => 'Location not found'], 404);
        }

        $locationName = strtolower($location->name);
        $currentDay = Carbon::now()->locale('nb')->dayName;
        
        // Get opening hours from apningstid table
        $openingHours = OpeningHours::where('day', $currentDay)->first();
        
        if (!$openingHours) {
            return response()->json([
                'site_id' => $siteId,
                'is_open' => false,
                'message' => 'No opening hours found for today'
            ]);
        }

        $openTime = $openingHours->getOpenTime($locationName);
        $closeTime = $openingHours->getCloseTime($locationName);
        $status = $openingHours->getStatus($locationName);

        // Check if currently open
        $isOpen = false;
        $message = 'Vognen er stengt';
        
        if ($status == 0) {
            $message = 'Vognen er stengt i dag';
        } elseif ($openTime && $closeTime) {
            $now = Carbon::now();
            $openTimeCarbon = Carbon::createFromTimeString($openTime);
            $closeTimeCarbon = Carbon::createFromTimeString($closeTime);
            
            if ($now->lt($openTimeCarbon)) {
                $message = "Åpner klokken {$openTime}";
            } elseif ($now->gt($closeTimeCarbon)) {
                $message = 'Vognen er stengt for dagen';
            } elseif ($now->between($openTimeCarbon, $closeTimeCarbon)) {
                $isOpen = true;
                $message = "Åpen til {$closeTime}";
            }
        }

        return response()->json([
            'site_id' => $siteId,
            'location_name' => $location->name,
            'is_open' => $isOpen,
            'message' => $message,
            'open_time' => $openTime,
            'close_time' => $closeTime,
            'status' => $status
        ]);
    }

    /**
     * Get location details by site ID
     */
    public function getLocation($siteId)
    {
        $location = Location::where('site_id', $siteId)->first();
        $site = Site::where('site_id', $siteId)->first();
        
        if (!$location) {
            return response()->json(['error' => 'Location not found'], 404);
        }

        return response()->json([
            'site_id' => $siteId,
            'name' => $location->name,
            'license' => $location->license ?? $site->license ?? null,
            'phone' => $location->phone,
            'email' => $location->email,
            'address' => $location->address,
            'url' => $location->order_url ?? $site->url ?? null,
            'active' => $location->active
        ]);
    }

    /**
     * Update opening status for a location
     */
    public function updateStatus(Request $request, $siteId)
    {
        $request->validate([
            'status' => 'required|integer|in:0,1'
        ]);

        $location = Location::where('site_id', $siteId)->first();
        
        if (!$location) {
            return response()->json(['error' => 'Location not found'], 404);
        }

        $locationName = strtolower($location->name);
        $currentDay = Carbon::now()->locale('nb')->dayName;
        
        // Update status in apningstid table
        $openingHours = OpeningHours::where('day', $currentDay)->first();
        
        if (!$openingHours) {
            return response()->json(['error' => 'Opening hours not found'], 404);
        }

        $openingHours->setStatus($locationName, $request->status);

        return response()->json([
            'site_id' => $siteId,
            'location_name' => $location->name,
            'status' => $request->status,
            'message' => 'Status updated successfully'
        ]);
    }

    /**
     * Helper function to get user ID by site ID
     */
    private function getUserIdBySiteId($siteId)
    {
        $mapping = [
            7 => 10,   // Namsos
            4 => 11,   // Lade
            6 => 12,   // Moan
            5 => 13,   // Gramyra
            10 => 14,  // Frosta
            11 => 16,  // Hell
            12 => 17   // Steinkjer
        ];

        return $mapping[$siteId] ?? null;
    }
}