<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApningstidAlternative;
use App\Models\SpecialHours;
use App\Models\AvdelingAlternative;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class OpeningHoursController extends Controller
{
    /**
     * Display the opening hours management interface
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $locations = collect();

        // Get locations based on user permissions
        if ($user->is_admin) {
            $locations = AvdelingAlternative::with('openingHours')->get();
        } else {
            $locations = AvdelingAlternative::with('openingHours')
                ->where('Id', $user->siteid)
                ->get();
        }

        // Get current month or requested month
        $currentDate = $request->get('month', now()->format('Y-m'));
        $viewDate = Carbon::parse($currentDate . '-01');

        // Get special hours for the current view period
        $startDate = $viewDate->copy()->startOfMonth()->subWeek();
        $endDate = $viewDate->copy()->endOfMonth()->addWeek();

        $specialHours = SpecialHours::with(['location', 'creator'])
            ->inDateRange($startDate, $endDate)
            ->when(!$user->is_admin, function ($query) use ($user) {
                return $query->forLocation($user->siteid);
            })
            ->orderBy('date')
            ->get();

        return view('admin.opening-hours.index', compact(
            'locations',
            'specialHours',
            'viewDate',
            'currentDate'
        ));
    }

        /**
     * Get calendar data for AJAX requests
     */
    public function getCalendarData(Request $request)
    {
        try {
            $user = Auth::user();
            $locationId = $request->get('location_id');
            $month = $request->get('month', now()->format('Y-m'));

            Log::info('Calendar data request', [
                'user_id' => $user->id,
                'is_admin' => $user->is_admin,
                'user_siteid' => $user->siteid,
                'requested_location_id' => $locationId,
                'month' => $month
            ]);

            // For non-admin users, force their location
            if (!$user->is_admin) {
                $locationId = $user->siteid;
            }

            // Validate location access
            if (!$user->is_admin && $locationId != $user->siteid) {
                Log::warning('Unauthorized location access attempt', [
                    'user_id' => $user->id,
                    'user_siteid' => $user->siteid,
                    'requested_location_id' => $locationId
                ]);
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            $viewDate = Carbon::parse($month . '-01');
            $startDate = $viewDate->copy()->startOfMonth();
            $endDate = $viewDate->copy()->endOfMonth();

            // Get regular hours
            $location = AvdelingAlternative::with('openingHours')->find($locationId);
            Log::info('Location lookup result', [
                'location_id' => $locationId,
                'location_found' => $location !== null,
                'opening_hours_found' => $location && $location->openingHours !== null
            ]);

            if (!$location) {
                Log::error('Location not found', ['location_id' => $locationId]);
                return response()->json(['error' => 'Location not found'], 404);
            }

            if (!$location->openingHours) {
                Log::error('Opening hours not found for location', ['location_id' => $locationId]);
                return response()->json(['error' => 'Opening hours not found for this location'], 404);
            }

        // Get special hours for this period
        $specialHours = SpecialHours::forLocation($locationId)
            ->inDateRange($startDate, $endDate)
            ->get()
            ->keyBy('date');

        // Generate calendar data
        $calendarData = [];
        $current = $startDate->copy();

        while ($current <= $endDate) {
            $dateStr = $current->format('Y-m-d');
            $dayOfWeek = strtolower($current->format('l'));

            // Check for special hours first
            if ($specialHours->has($dateStr)) {
                $special = $specialHours[$dateStr];
                $calendarData[] = [
                    'date' => $dateStr,
                    'day' => $current->day,
                    'dayOfWeek' => $dayOfWeek,
                    'isSpecial' => true,
                    'isClosed' => $special->is_closed,
                    'hours' => $special->formatted_hours,
                    'reason' => $special->reason,
                    'type' => $special->type,
                    'specialId' => $special->id
                ];
            } else {
                // Use regular hours
                $regularHours = $location->openingHours->getHoursForDay($dayOfWeek);
                $calendarData[] = [
                    'date' => $dateStr,
                    'day' => $current->day,
                    'dayOfWeek' => $dayOfWeek,
                    'isSpecial' => false,
                    'isClosed' => $regularHours['closed'] == 1 || $location->openingHours->isSeasonClosed(),
                    'hours' => $regularHours['closed'] == 1 ? 'Stengt' :
                              ($regularHours['start'] && $regularHours['stop'] ?
                               $regularHours['start'] . ' - ' . $regularHours['stop'] : 'Stengt'),
                    'reason' => $location->openingHours->isSeasonClosed() ? 'Sesongstengt' : null,
                    'type' => 'regular'
                ];
            }

            $current->addDay();
        }

        return response()->json([
            'calendarData' => $calendarData,
            'location' => [
                'id' => $location->Id,
                'name' => $location->Navn,
                'regularHours' => $location->openingHours->getWeekHours()
            ]
        ]);

        } catch (\Exception $e) {
            Log::error('Error loading calendar data', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id(),
                'location_id' => $locationId ?? null
            ]);

            return response()->json([
                'error' => 'Failed to load calendar data'
            ], 500);
        }
    }

    /**
     * Get individual special hours data
     */
    public function getSpecialHours($id)
    {
        $user = Auth::user();
        $specialHours = SpecialHours::with('location')->findOrFail($id);

        // Check permissions
        if (!$user->is_admin && $specialHours->location_id != $user->siteid) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return response()->json($specialHours);
    }

    /**
     * Update regular opening hours
     */
    public function updateRegularHours(Request $request, $locationId)
    {
        $user = Auth::user();

        // Check permissions
        if (!$user->is_admin && $locationId != $user->siteid) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'hours' => 'required|array',
            'hours.*.start' => 'nullable|date_format:H:i',
            'hours.*.end' => 'nullable|date_format:H:i',
            'hours.*.closed' => 'boolean'
        ]);

        $location = ApningstidAlternative::where('AvdID', $locationId)->first();
        if (!$location) {
            return response()->json(['error' => 'Location not found'], 404);
        }

        $hours = $request->hours;
        $dayMapping = [
            'monday' => ['ManStart', 'ManStopp', 'ManStengt'],
            'tuesday' => ['TirStart', 'TirStopp', 'TirStengt'],
            'wednesday' => ['OnsStart', 'OnsStopp', 'OnsStengt'],
            'thursday' => ['TorStart', 'TorStopp', 'TorStengt'],
            'friday' => ['FreStart', 'FreStopp', 'FreStengt'],
            'saturday' => ['LorStart', 'LorStopp', 'LorStengt'],
            'sunday' => ['SonStart', 'SonStopp', 'SonStengt']
        ];

        foreach ($hours as $day => $dayHours) {
            if (isset($dayMapping[$day])) {
                $fields = $dayMapping[$day];
                $location->{$fields[0]} = $dayHours['closed'] ? null : $dayHours['start'];
                $location->{$fields[1]} = $dayHours['closed'] ? null : $dayHours['end'];
                $location->{$fields[2]} = $dayHours['closed'] ? 1 : 0;
            }
        }

        $location->save();

        Log::info('Regular opening hours updated', [
            'location_id' => $locationId,
            'updated_by' => $user->id,
            'hours' => $hours
        ]);

        return response()->json(['success' => true, 'message' => 'Åpningstider oppdatert']);
    }

    /**
     * Store special hours
     */
    public function storeSpecialHours(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'location_id' => 'required|integer',
            'date' => 'required|date|after_or_equal:today',
            'end_date' => 'nullable|date|after_or_equal:date',
            'open_time' => 'nullable|date_format:H:i',
            'close_time' => 'nullable|date_format:H:i|after:open_time',
            'is_closed' => 'boolean',
            'reason' => 'nullable|string|max:255',
            'type' => 'required|in:special,holiday,maintenance,event,closure',
            'recurring_yearly' => 'boolean',
            'notes' => 'nullable|string'
        ]);

        // Check permissions
        if (!$user->is_admin && $request->location_id != $user->siteid) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // If closed, clear times
        if ($request->is_closed) {
            $request->merge([
                'open_time' => null,
                'close_time' => null
            ]);
        }

        $specialHours = SpecialHours::create(array_merge(
            $request->all(),
            ['created_by' => $user->id]
        ));

        Log::info('Special hours created', [
            'special_hours_id' => $specialHours->id,
            'location_id' => $request->location_id,
            'date' => $request->date,
            'created_by' => $user->id
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Spesielle åpningstider lagret',
            'data' => $specialHours->load('location')
        ]);
    }

    /**
     * Update special hours
     */
    public function updateSpecialHours(Request $request, $id)
    {
        $user = Auth::user();
        $specialHours = SpecialHours::findOrFail($id);

        // Check permissions
        if (!$user->is_admin && $specialHours->location_id != $user->siteid) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:date',
            'open_time' => 'nullable|date_format:H:i',
            'close_time' => 'nullable|date_format:H:i|after:open_time',
            'is_closed' => 'boolean',
            'reason' => 'nullable|string|max:255',
            'type' => 'required|in:special,holiday,maintenance,event,closure',
            'recurring_yearly' => 'boolean',
            'notes' => 'nullable|string'
        ]);

        // If closed, clear times
        if ($request->is_closed) {
            $request->merge([
                'open_time' => null,
                'close_time' => null
            ]);
        }

        $specialHours->update($request->all());

        Log::info('Special hours updated', [
            'special_hours_id' => $specialHours->id,
            'updated_by' => $user->id
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Spesielle åpningstider oppdatert',
            'data' => $specialHours->load('location')
        ]);
    }

    /**
     * Delete special hours
     */
    public function destroySpecialHours($id)
    {
        $user = Auth::user();
        $specialHours = SpecialHours::findOrFail($id);

        // Check permissions
        if (!$user->is_admin && $specialHours->location_id != $user->siteid) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $specialHours->delete();

        Log::info('Special hours deleted', [
            'special_hours_id' => $id,
            'deleted_by' => $user->id
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Spesielle åpningstider slettet'
        ]);
    }
}
