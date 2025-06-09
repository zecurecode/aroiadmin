<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\Order;
use App\Models\Location;
use App\Models\OpeningHours;
use App\Models\ApningstidAlternative;
use App\Models\User;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Display the user dashboard for regular users.
     */
    public function index()
    {
        // Get session data (like old PHP system)
        $sessionData = [
            'loggedin' => session()->get('loggedin'),
            'id' => session()->get('id'),
            'username' => session()->get('username'),
            'siteid' => session()->get('siteid'),
            'is_admin' => session()->get('is_admin'),
            'auth_method' => session()->get('auth_method'),
        ];

        // Get Laravel auth data
        $laravelAuthData = [
            'auth_check' => Auth::check(),
            'auth_user_null' => Auth::user() === null,
            'auth_id' => Auth::user() ? Auth::user()->id : null,
            'auth_username' => Auth::user() ? Auth::user()->username : null,
            'auth_siteid' => Auth::user() ? Auth::user()->siteid : null,
            'auth_is_admin' => Auth::user() ? Auth::user()->isAdmin() : null,
        ];

        Log::info('=== USER DASHBOARD ACCESS ===', [
            'request_url' => request()->url(),
            'session_data' => $sessionData,
            'laravel_auth_data' => $laravelAuthData,
            'session_id' => session()->getId(),
        ]);

        // Get user info prioritizing our custom session (like old PHP system)
        $user = null;
        $userId = null;
        $username = null;
        $userSiteId = null;
        $isAdmin = false;

        if ($sessionData['loggedin'] === true && $sessionData['id'] && $sessionData['username']) {
            // Use custom session data (like old PHP system)
            $userId = $sessionData['id'];
            $username = $sessionData['username'];
            $userSiteId = $sessionData['siteid'];
            $isAdmin = $sessionData['is_admin'] === true;

            // Try to get user model for additional data
            $user = User::find($userId);

            Log::info('Using custom session data for dashboard', [
                'user_id' => $userId,
                'username' => $username,
                'siteid' => $userSiteId,
                'is_admin' => $isAdmin,
                'user_model_found' => $user !== null
            ]);
        } elseif (Auth::check()) {
            // Fallback to Laravel auth
            $user = Auth::user();
            $userId = $user->id;
            $username = $user->username;
            $userSiteId = $user->siteid;
            $isAdmin = $user->isAdmin();

            Log::info('Using Laravel auth data for dashboard', [
                'user_id' => $userId,
                'username' => $username,
                'siteid' => $userSiteId,
                'is_admin' => $isAdmin
            ]);
        } else {
            Log::warning('No valid authentication found in dashboard controller');
            return redirect('/login')->withErrors(['error' => 'Authentication required']);
        }

        // If admin user somehow gets here, redirect them to admin dashboard
        if ($isAdmin) {
            Log::info('Admin user accessing regular dashboard, redirecting to admin dashboard', [
                'username' => $username
            ]);
            return redirect('/admin/dashboard');
        }

        Log::info('Loading dashboard for regular user', [
            'username' => $username,
            'user_id' => $userId,
            'siteid' => $userSiteId
        ]);

        // Get statistics for user's location
        $todayOrders = Order::where('site', $userSiteId)
            ->whereDate('datetime', Carbon::today())
            ->count();

        $pendingOrders = Order::where('site', $userSiteId)
            ->where('ordrestatus', 0)
            ->count();

        $unpaidOrders = Order::where('site', $userSiteId)
            ->where('paid', 0)
            ->count();

        $recentOrders = Order::where('site', $userSiteId)
            ->orderBy('datetime', 'desc')
            ->limit(10)
            ->get();

        // Get location information
        $locationName = $user ? $user->getLocationName() : "Site $userSiteId";

                // Get today's opening hours using new dynamic table
        $todayDay = Carbon::now()->format('l'); // Monday, Tuesday, etc.
        $openingHours = ApningstidAlternative::where('AvdID', $userSiteId)->first();

        $isOpen = false;
        $openTime = null;
        $closeTime = null;
        $status = 0;

        if ($openingHours) {
            Log::info('Opening hours debug', [
                'day' => $todayDay,
                'location_name' => $locationName,
                'site_id' => $userSiteId,
                'opening_hours_found' => $openingHours !== null
            ]);

            // Get today's hours using the new model structure
            $todayHours = $openingHours->getHoursForDay($todayDay);

            if ($todayHours) {
                $openTime = $todayHours['start'];
                $closeTime = $todayHours['stop'];
                $isClosed = $todayHours['closed'];

                Log::info('Opening hours data', [
                    'open_time' => $openTime,
                    'close_time' => $closeTime,
                    'is_closed' => $isClosed,
                    'season_closed' => $openingHours->isSeasonClosed()
                ]);

                // Check if open: must have valid times, not be marked as closed, and not be season closed
                if ($openTime && $closeTime && !$isClosed && !$openingHours->isSeasonClosed()) {
                    $now = Carbon::now();
                    try {
                        // Handle time formats with or without seconds
                        $openTime = strlen($openTime) === 5 ? $openTime . ':00' : $openTime;
                        $closeTime = strlen($closeTime) === 5 ? $closeTime . ':00' : $closeTime;

                        $openDateTime = Carbon::createFromFormat('H:i:s', $openTime);
                        $closeDateTime = Carbon::createFromFormat('H:i:s', $closeTime);

                        // Don't consider 00:00:00 as valid opening time
                        if ($openTime !== '00:00:00' && $closeTime !== '00:00:00') {
                            $isOpen = $now->between($openDateTime, $closeDateTime);
                        }
                    } catch (\Exception $e) {
                        Log::warning('Time parsing failed', ['error' => $e->getMessage()]);
                        $isOpen = false;
                    }
                }
            }
        }

        // Calculate estimated orders based on historical data
        $estimatedOrders = $this->calculateEstimatedOrders($userSiteId);

        // Get hourly order patterns
        $hourlyPattern = $this->getHourlyOrderPattern($userSiteId);

        // Get user/van location info
        $locationInfo = $this->getLocationInfo($user);

        // Get weather data
        $weatherData = $this->getWeatherData($locationInfo['lat'] ?? 63.4305, $locationInfo['lon'] ?? 10.3951);

        $dashboardData = [
            'username' => $username,
            'locationName' => $locationName,
            'todayOrders' => $todayOrders,
            'pendingOrders' => $pendingOrders,
            'unpaidOrders' => $unpaidOrders,
            'recentOrders' => $recentOrders,
            'isOpen' => $isOpen,
            'openTime' => $openTime,
            'closeTime' => $closeTime,
            'status' => $status,
            'userSiteId' => $userSiteId,
            'estimatedOrders' => $estimatedOrders,
            'hourlyPattern' => $hourlyPattern,
            'locationInfo' => $locationInfo,
            'weatherData' => $weatherData,
        ];

        Log::info('Dashboard data prepared', [
            'username' => $username,
            'location_name' => $locationName,
            'today_orders' => $todayOrders,
            'pending_orders' => $pendingOrders,
            'unpaid_orders' => $unpaidOrders,
        ]);

        return view('user-dashboard', $dashboardData);
    }

    /**
     * Calculate estimated orders based on historical data for the same day
     */
    private function calculateEstimatedOrders($siteId)
    {
        $dayOfWeek = Carbon::today()->dayOfWeek;

        // Get orders for the same day of week from last 8 weeks
        $historicalOrders = Order::where('site', $siteId)
            ->whereDate('datetime', '>=', Carbon::today()->subWeeks(8))
            ->whereDate('datetime', '<', Carbon::today())
            ->get()
            ->filter(function ($order) use ($dayOfWeek) {
                return Carbon::parse($order->datetime)->dayOfWeek === $dayOfWeek;
            });

        if ($historicalOrders->count() === 0) {
            return [
                'estimated' => 0,
                'confidence' => 'low',
                'trend' => 'stable'
            ];
        }

        // Group by date and count orders per day
        $dailyCounts = $historicalOrders->groupBy(function ($order) {
            return Carbon::parse($order->datetime)->format('Y-m-d');
        })->map->count();

        $average = $dailyCounts->avg();
        $recent4Weeks = $dailyCounts->take(-4)->avg();
        $older4Weeks = $dailyCounts->take(4)->avg();

        // Calculate trend
        $trend = 'stable';
        if ($recent4Weeks > $older4Weeks * 1.1) {
            $trend = 'increasing';
        } elseif ($recent4Weeks < $older4Weeks * 0.9) {
            $trend = 'decreasing';
        }

        // Calculate confidence based on consistency
        $standardDeviation = sqrt($dailyCounts->map(function ($count) use ($average) {
            return pow($count - $average, 2);
        })->avg());

        $confidence = 'high';
        if ($standardDeviation > $average * 0.5) {
            $confidence = 'medium';
        }
        if ($standardDeviation > $average * 0.8) {
            $confidence = 'low';
        }

        return [
            'estimated' => round($average),
            'confidence' => $confidence,
            'trend' => $trend,
            'recent_avg' => round($recent4Weeks),
            'historical_data' => $dailyCounts->values()->toArray()
        ];
    }

    /**
     * Get hourly order patterns for the day
     */
    private function getHourlyOrderPattern($siteId)
    {
        $dayOfWeek = Carbon::today()->dayOfWeek;

        // Get orders for the same day of week from last 4 weeks
        $orders = Order::where('site', $siteId)
            ->whereDate('datetime', '>=', Carbon::today()->subWeeks(4))
            ->whereDate('datetime', '<', Carbon::today())
            ->get()
            ->filter(function ($order) use ($dayOfWeek) {
                return Carbon::parse($order->datetime)->dayOfWeek === $dayOfWeek;
            });

        // Initialize hourly data as array instead of collection
        $hourlyData = array_fill(0, 24, 0);

        // Count orders by hour
        $orders->each(function ($order) use (&$hourlyData) {
            $hour = Carbon::parse($order->datetime)->hour;
            $hourlyData[$hour]++;
        });

        // Calculate average per hour
        $weeks = max(1, $orders->groupBy(function ($order) {
            return Carbon::parse($order->datetime)->format('Y-W');
        })->count());

        $hourlyAverage = array_map(function ($count) use ($weeks) {
            return round($count / $weeks, 1);
        }, $hourlyData);

        return [
            'labels' => range(0, 23),
            'data' => array_values($hourlyAverage),
            'total_weeks' => $weeks
        ];
    }

    /**
     * Get location information for the user/van
     */
    private function getLocationInfo($user)
    {
        // Default to Namsos coordinates if no specific location
        $defaultLocation = [
            'address' => 'Namsos, Norge',
            'lat' => 64.4669,
            'lon' => 11.4948
        ];

        if (!$user) {
            return $defaultLocation;
        }

        // Try to get location from user or site
        if ($user->siteid) {
            // You can extend this to get actual coordinates from sites table
            $locationMap = [
                7 => ['address' => 'Namsos, Norge', 'lat' => 64.4669, 'lon' => 11.4948],
                // Add more sites as needed
            ];

            return $locationMap[$user->siteid] ?? $defaultLocation;
        }

        return $defaultLocation;
    }

    /**
     * Get weather data from yr.no API
     */
    private function getWeatherData($lat, $lon)
    {
        try {
            $userAgent = 'AroiAdminDashboard/1.0 github.com/aroiadmin';
            $url = "https://api.met.no/weatherapi/locationforecast/2.0/compact?lat={$lat}&lon={$lon}";

            $context = stream_context_create([
                'http' => [
                    'header' => "User-Agent: {$userAgent}\r\n",
                    'timeout' => 10
                ]
            ]);

            $response = file_get_contents($url, false, $context);

            if ($response === false) {
                return $this->getDefaultWeatherData();
            }

            $data = json_decode($response, true);

            if (!$data || !isset($data['properties']['timeseries'])) {
                return $this->getDefaultWeatherData();
            }

            // Extract current and next few hours
            $timeseries = array_slice($data['properties']['timeseries'], 0, 6);
            $forecast = [];

            foreach ($timeseries as $entry) {
                $time = Carbon::parse($entry['time']);
                $details = $entry['data']['instant']['details'];

                $forecast[] = [
                    'time' => $time->format('H:i'),
                    'temperature' => round($details['air_temperature']),
                    'icon' => $this->getWeatherIcon($entry['data']['next_1_hours']['summary']['symbol_code'] ?? 'clearsky_day'),
                    'description' => $this->getWeatherDescription($entry['data']['next_1_hours']['summary']['symbol_code'] ?? 'clearsky_day')
                ];
            }

            return [
                'current' => $forecast[0] ?? null,
                'forecast' => array_slice($forecast, 1),
                'location' => "Lat: {$lat}, Lon: {$lon}"
            ];

        } catch (\Exception $e) {
            Log::warning('Weather API failed', ['error' => $e->getMessage()]);
            return $this->getDefaultWeatherData();
        }
    }

    private function getDefaultWeatherData()
    {
        return [
            'current' => [
                'time' => Carbon::now()->format('H:i'),
                'temperature' => '--',
                'icon' => '☀️',
                'description' => 'Værdata ikke tilgjengelig'
            ],
            'forecast' => [],
            'location' => 'Ukjent lokasjon'
        ];
    }

    private function getWeatherIcon($symbolCode)
    {
        $iconMap = [
            'clearsky_day' => '☀️',
            'clearsky_night' => '🌙',
            'partlycloudy_day' => '⛅',
            'partlycloudy_night' => '☁️',
            'cloudy' => '☁️',
            'rain' => '🌧️',
            'rainshowers_day' => '🌦️',
            'snow' => '❄️',
            'fog' => '🌫️'
        ];

        return $iconMap[$symbolCode] ?? '🌤️';
    }

    private function getWeatherDescription($symbolCode)
    {
        $descMap = [
            'clearsky_day' => 'Klart vær',
            'clearsky_night' => 'Klar natt',
            'partlycloudy_day' => 'Delvis skyet',
            'partlycloudy_night' => 'Delvis skyet',
            'cloudy' => 'Overskyet',
            'rain' => 'Regn',
            'rainshowers_day' => 'Regnbyger',
            'snow' => 'Snø',
            'fog' => 'Tåke'
        ];

        return $descMap[$symbolCode] ?? 'Varierende vær';
    }

    /**
     * Update delivery time for the user's session.
     */
    public function updateDeliveryTime(Request $request)
    {
        $request->validate([
            'delivery_time' => 'required|integer|min:15|max:90'
        ]);

        $deliveryTime = $request->input('delivery_time');

        // Store in session for now - could be expanded to database later
        session(['delivery_time' => $deliveryTime]);

        Log::info('Delivery time updated', [
            'user_id' => session('id'),
            'username' => session('username'),
            'delivery_time' => $deliveryTime
        ]);

        return response()->json([
            'success' => true,
            'delivery_time' => $deliveryTime,
            'message' => 'Leveringstid oppdatert'
        ]);
    }
}
