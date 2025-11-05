<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApningstidAlternative;
use App\Models\AvdelingAlternative;
use App\Models\SpecialHours;
use Carbon\Carbon;
use Illuminate\Http\Request;
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
        $selectedLocationId = null;

        // Get locations based on user permissions
        if ($user->is_admin) {
            $locations = AvdelingAlternative::with('openingHours')->get();
            // For admin, allow selecting any location or default to first
            $selectedLocationId = $request->get('location_id') ?? $locations->first()?->Id;
        } else {
            $locations = AvdelingAlternative::with('openingHours')
                ->where('Id', $user->siteid)
                ->get();
            $selectedLocationId = $user->siteid;
        }

        // Get current month or requested month
        $currentDate = $request->get('month', now()->format('Y-m'));
        $viewDate = Carbon::parse($currentDate.'-01');

        // Get special hours for the current view period
        $startDate = $viewDate->copy()->startOfMonth()->subWeek();
        $endDate = $viewDate->copy()->endOfMonth()->addWeek();

        $specialHours = SpecialHours::with(['location', 'creator'])
            ->inDateRange($startDate, $endDate)
            ->when(! $user->is_admin, function ($query) use ($user) {
                return $query->forLocation($user->siteid);
            })
            ->when($user->is_admin && $selectedLocationId, function ($query) use ($selectedLocationId) {
                return $query->forLocation($selectedLocationId);
            })
            ->orderBy('date')
            ->get();

        return view('admin.opening-hours.index', compact(
            'locations',
            'specialHours',
            'viewDate',
            'currentDate',
            'selectedLocationId'
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
                'month' => $month,
                'request_url' => $request->fullUrl(),
            ]);

            // Handle location selection based on user type
            if ($user->is_admin) {
                // Admin can see all locations
                if (! $locationId) {
                    // If no location specified, get the first available location
                    $firstLocation = AvdelingAlternative::with('openingHours')->first();
                    if (! $firstLocation) {
                        return response()->json(['error' => 'No locations found'], 404);
                    }
                    $locationId = $firstLocation->Id;
                    Log::info('Admin: Using first available location', ['location_id' => $locationId]);
                }
            } else {
                // For non-admin users, force their location
                $locationId = $user->siteid;
            }

            // Validate location access for non-admin users
            if (! $user->is_admin && $locationId != $user->siteid) {
                Log::warning('Unauthorized location access attempt', [
                    'user_id' => $user->id,
                    'user_siteid' => $user->siteid,
                    'requested_location_id' => $locationId,
                ]);

                return response()->json(['error' => 'Unauthorized'], 403);
            }

            $viewDate = Carbon::parse($month.'-01');
            $startDate = $viewDate->copy()->startOfMonth();
            $endDate = $viewDate->copy()->endOfMonth();

            // Get regular hours
            $location = AvdelingAlternative::with('openingHours')->find($locationId);
            Log::info('Location lookup result', [
                'location_id' => $locationId,
                'location_found' => $location !== null,
                'location_name' => $location ? $location->Navn : null,
                'opening_hours_found' => $location && $location->openingHours !== null,
                'opening_hours_avd_id' => $location && $location->openingHours ? $location->openingHours->AvdID : null,
            ]);

            if (! $location) {
                Log::error('Location not found', [
                    'location_id' => $locationId,
                    'all_location_ids' => AvdelingAlternative::pluck('Id')->toArray(),
                ]);

                return response()->json(['error' => 'Location not found'], 404);
            }

            if (! $location->openingHours) {
                Log::error('Opening hours not found for location', [
                    'location_id' => $locationId,
                    'location_name' => $location->Navn,
                    'checking_apningstid_table' => ApningstidAlternative::where('AvdID', $locationId)->exists(),
                ]);

                return response()->json(['error' => 'Opening hours not found for this location'], 404);
            }

            // Get special hours for this period
            $specialHours = SpecialHours::forLocation($locationId)
                ->inDateRange($startDate, $endDate)
                ->get()
                ->keyBy(function ($item) {
                    // Use formatted date string as key (YYYY-MM-DD)
                    return $item->date instanceof \Carbon\Carbon
                        ? $item->date->format('Y-m-d')
                        : $item->date;
                });

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
                        'specialId' => $special->id,
                    ];
                } else {
                    // Use regular hours
                    try {
                        $regularHours = $location->openingHours->getHoursForDay($dayOfWeek);
                        $isSeasonClosed = $location->openingHours->isSeasonClosed();

                        $calendarData[] = [
                            'date' => $dateStr,
                            'day' => $current->day,
                            'dayOfWeek' => $dayOfWeek,
                            'isSpecial' => false,
                            'isClosed' => $regularHours['closed'] == 1 || $isSeasonClosed,
                            'hours' => $regularHours['closed'] == 1 ? 'Stengt' :
                                      ($regularHours['start'] && $regularHours['stop'] ?
                                       $regularHours['start'].' - '.$regularHours['stop'] : 'Stengt'),
                            'reason' => $isSeasonClosed ? 'Sesongstengt' : null,
                            'type' => 'regular',
                        ];
                    } catch (\Exception $e) {
                        Log::error('Error getting hours for day', [
                            'location_id' => $locationId,
                            'day_of_week' => $dayOfWeek,
                            'date' => $dateStr,
                            'error' => $e->getMessage(),
                        ]);

                        // Fallback data
                        $calendarData[] = [
                            'date' => $dateStr,
                            'day' => $current->day,
                            'dayOfWeek' => $dayOfWeek,
                            'isSpecial' => false,
                            'isClosed' => true,
                            'hours' => 'Feil ved lasting',
                            'reason' => null,
                            'type' => 'regular',
                        ];
                    }
                }

                $current->addDay();
            }

            // Get week hours with error handling
            $weekHours = [];
            try {
                $weekHours = $location->openingHours->getWeekHours();
            } catch (\Exception $e) {
                Log::error('Error getting week hours', [
                    'location_id' => $locationId,
                    'error' => $e->getMessage(),
                ]);
                // Provide empty week hours as fallback
                $weekHours = [
                    'monday' => ['start' => null, 'stop' => null, 'closed' => 1],
                    'tuesday' => ['start' => null, 'stop' => null, 'closed' => 1],
                    'wednesday' => ['start' => null, 'stop' => null, 'closed' => 1],
                    'thursday' => ['start' => null, 'stop' => null, 'closed' => 1],
                    'friday' => ['start' => null, 'stop' => null, 'closed' => 1],
                    'saturday' => ['start' => null, 'stop' => null, 'closed' => 1],
                    'sunday' => ['start' => null, 'stop' => null, 'closed' => 1],
                ];
            }

            return response()->json([
                'calendarData' => $calendarData,
                'location' => [
                    'id' => $location->Id,
                    'name' => $location->Navn,
                    'regularHours' => $weekHours,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Error loading calendar data', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id(),
                'location_id' => $locationId ?? null,
            ]);

            return response()->json([
                'error' => 'Failed to load calendar data',
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
        if (! $user->is_admin && $specialHours->location_id != $user->siteid) {
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
        if (! $user->is_admin && $locationId != $user->siteid) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'hours' => 'required|array',
            'hours.monday.start' => 'nullable|date_format:H:i',
            'hours.monday.end' => 'nullable|date_format:H:i',
            'hours.monday.closed' => 'required|boolean',
            'hours.tuesday.start' => 'nullable|date_format:H:i',
            'hours.tuesday.end' => 'nullable|date_format:H:i',
            'hours.tuesday.closed' => 'required|boolean',
            'hours.wednesday.start' => 'nullable|date_format:H:i',
            'hours.wednesday.end' => 'nullable|date_format:H:i',
            'hours.wednesday.closed' => 'required|boolean',
            'hours.thursday.start' => 'nullable|date_format:H:i',
            'hours.thursday.end' => 'nullable|date_format:H:i',
            'hours.thursday.closed' => 'required|boolean',
            'hours.friday.start' => 'nullable|date_format:H:i',
            'hours.friday.end' => 'nullable|date_format:H:i',
            'hours.friday.closed' => 'required|boolean',
            'hours.saturday.start' => 'nullable|date_format:H:i',
            'hours.saturday.end' => 'nullable|date_format:H:i',
            'hours.saturday.closed' => 'required|boolean',
            'hours.sunday.start' => 'nullable|date_format:H:i',
            'hours.sunday.end' => 'nullable|date_format:H:i',
            'hours.sunday.closed' => 'required|boolean',
        ], [
            'hours.*.start.date_format' => 'Starttid må være i formatet TT:MM (f.eks. 09:00)',
            'hours.*.end.date_format' => 'Sluttid må være i formatet TT:MM (f.eks. 17:00)',
            'hours.*.closed.required' => 'Du må angi om dagen er stengt eller ikke',
            'hours.*.closed.boolean' => 'Stengt-feltet må være sant eller usant',
        ]);

        $location = ApningstidAlternative::where('AvdID', $locationId)->first();
        if (! $location) {
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
            'sunday' => ['SonStart', 'SonStopp', 'SonStengt'],
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
            'hours' => $hours,
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
            'date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:date',
            'open_time' => 'nullable|date_format:H:i',
            'close_time' => 'nullable|date_format:H:i|after:open_time',
            'is_closed' => 'boolean',
            'reason' => 'nullable|string|max:255',
            'type' => 'required|in:special,holiday,maintenance,event,closure',
            'recurring_yearly' => 'boolean',
            'notes' => 'nullable|string',
        ]);

        // Check permissions
        if (! $user->is_admin && $request->location_id != $user->siteid) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Prepare data
        $data = $request->all();

        // If closed, clear times
        if ($request->is_closed) {
            $data['open_time'] = null;
            $data['close_time'] = null;
        }

        // Add created_by for new records
        $data['created_by'] = $user->id;

        // Use updateOrCreate to avoid duplicate entry errors
        // If a special hours entry already exists for this location and date, update it
        // Otherwise, create a new one
        $specialHours = SpecialHours::updateOrCreate(
            [
                'location_id' => $request->location_id,
                'date' => $request->date,
            ],
            $data
        );

        Log::info('Special hours saved', [
            'special_hours_id' => $specialHours->id,
            'location_id' => $request->location_id,
            'date' => $request->date,
            'open_time' => $data['open_time'] ?? null,
            'close_time' => $data['close_time'] ?? null,
            'is_closed' => $data['is_closed'] ?? false,
            'was_updated' => $specialHours->wasRecentlyCreated ? 'no' : 'yes',
            'user_id' => $user->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Spesielle åpningstider lagret',
            'data' => $specialHours->load('location'),
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
        if (! $user->is_admin && $specialHours->location_id != $user->siteid) {
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
            'notes' => 'nullable|string',
        ]);

        // Prepare data
        $data = $request->all();

        // If closed, clear times
        if ($request->is_closed) {
            $data['open_time'] = null;
            $data['close_time'] = null;
        }

        $specialHours->update($data);

        Log::info('Special hours updated', [
            'special_hours_id' => $specialHours->id,
            'location_id' => $specialHours->location_id,
            'date' => $specialHours->date,
            'open_time' => $data['open_time'] ?? null,
            'close_time' => $data['close_time'] ?? null,
            'is_closed' => $data['is_closed'] ?? false,
            'updated_by' => $user->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Spesielle åpningstider oppdatert',
            'data' => $specialHours->load('location'),
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
        if (! $user->is_admin && $specialHours->location_id != $user->siteid) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $specialHours->delete();

        Log::info('Special hours deleted', [
            'special_hours_id' => $id,
            'deleted_by' => $user->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Spesielle åpningstider slettet',
        ]);
    }
}
