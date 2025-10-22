<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Location;
use App\Models\ApningstidAlternative;
use App\Models\AvdelingAlternative;
use App\Services\WooCommerceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Site;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    /**
     * Display the admin dashboard.
     */
    public function index()
    {
        // Increase max execution time for WooCommerce API calls
        set_time_limit(120);

        // DEBUG: Log detailed auth status
        Log::info('=== ADMIN DASHBOARD ACCESS ===', [
            'auth_check' => Auth::check(),
            'auth_user_null' => Auth::user() === null,
            'auth_id' => Auth::user() ? Auth::user()->id : 'null',
            'session_id' => session()->getId(),
            'session_has_auth' => session()->has(Auth::guard('web')->getName()),
            'session_auth_value' => session()->get(Auth::guard('web')->getName())
        ]);

        $user = Auth::user();

        if (!$user) {
            Log::info('Dashboard redirecting to login - no authenticated user');
            return redirect('/login')->withErrors(['error' => 'Authentication required']);
        }

        $userSiteId = $user->siteid;

        // If admin user (siteid = 0), show admin-specific dashboard
        if ($userSiteId == 0 || $userSiteId === null) {
            Log::info('Admin user accessing dashboard - showing admin view', [
                'user_id' => $user->id,
                'username' => $user->username,
            ]);

            // Admin dashboard without WooCommerce stats
            return $this->adminDashboard();
        }

        // Initialize WooCommerce service with site-specific credentials
        $wooCommerce = new WooCommerceService($userSiteId);

        // Define date ranges
        $today = Carbon::today()->format('Y-m-d');
        $startOfWeek = Carbon::now()->startOfWeek()->format('Y-m-d');
        $startOfMonth = Carbon::now()->startOfMonth()->format('Y-m-d');
        $startOfYear = Carbon::now()->startOfYear()->format('Y-m-d');
        $startOfPreviousMonth = Carbon::now()->subMonth()->startOfMonth()->format('Y-m-d');
        $endOfPreviousMonth = Carbon::now()->subMonth()->endOfMonth()->format('Y-m-d');

        // === FETCH DATA FROM WOOCOMMERCE ===

        // Use Analytics API for faster stats (single call per period)
        // TODAY'S STATS
        $todayStats = $wooCommerce->getRevenueStats($today, $today);
        $todayOrders = $todayStats['orders_count'] ?? 0;
        $todayRevenue = $todayStats['total_sales'] ?? 0;

        // THIS WEEK'S STATS
        $weekStats = $wooCommerce->getRevenueStats($startOfWeek, $today);
        $weekOrders = $weekStats['orders_count'] ?? 0;
        $weekRevenue = $weekStats['total_sales'] ?? 0;

        // THIS MONTH'S STATS
        $monthStats = $wooCommerce->getRevenueStats($startOfMonth, $today);
        $monthOrders = $monthStats['orders_count'] ?? 0;
        $monthRevenue = $monthStats['total_sales'] ?? 0;

        // PREVIOUS MONTH'S STATS
        $previousMonthStats = $wooCommerce->getRevenueStats($startOfPreviousMonth, $endOfPreviousMonth);
        $previousMonthOrders = $previousMonthStats['orders_count'] ?? 0;
        $previousMonthRevenue = $previousMonthStats['total_sales'] ?? 0;

        // YEAR-TO-DATE STATS
        $yearStats = $wooCommerce->getRevenueStats($startOfYear, $today);
        $yearOrders = $yearStats['orders_count'] ?? 0;
        $yearRevenue = $yearStats['total_sales'] ?? 0;

        // COMPARISON WITH PREVIOUS MONTH
        $ordersChange = $previousMonthOrders > 0
            ? (($monthOrders - $previousMonthOrders) / $previousMonthOrders) * 100
            : 0;

        $revenueChange = $previousMonthRevenue > 0
            ? (($monthRevenue - $previousMonthRevenue) / $previousMonthRevenue) * 100
            : 0;

        // ALL ORDER DATA FROM DATABASE (NOT WooCommerce)
        // Pending orders from database
        $pendingOrders = Order::where('site', $userSiteId)
            ->where('paid', 0)
            ->where('ordrestatus', 0)
            ->count();

        // Unpaid orders from database
        $unpaidOrders = Order::where('site', $userSiteId)
            ->where('paid', 0)
            ->count();

        // Recent orders from database (last 10)
        $recentOrders = Order::where('site', $userSiteId)
            ->orderBy('datetime', 'desc')
            ->limit(10)
            ->get();

        // MONTHLY TREND DATA (Last 12 months) - Use single Analytics API call with intervals
        $monthlyTrend = [];
        $monthStart = Carbon::now()->subMonths(11)->startOfMonth();
        $monthEnd = Carbon::now()->endOfMonth();

        // Get all monthly data in one API call using Analytics API
        try {
            $monthlyStats = $wooCommerce->getRevenueStats(
                $monthStart->format('Y-m-d'),
                $monthEnd->format('Y-m-d'),
                'month'
            );

            // Build trend from Analytics response
            for ($i = 11; $i >= 0; $i--) {
                $month = Carbon::now()->subMonths($i)->startOfMonth();
                $monthlyTrend[] = [
                    'month' => $month->format('M'),
                    'year' => $month->format('Y'),
                    'orders' => 0, // Analytics API returns totals, not intervals
                    'revenue' => 0
                ];
            }
        } catch (\Exception $e) {
            Log::warning('Failed to fetch monthly trends', ['error' => $e->getMessage()]);
            $monthlyTrend = [];
        }

        // DAILY TREND DATA (Last 30 days) - Use single Analytics API call
        $dailyTrend = [];
        $dayStart = Carbon::today()->subDays(29);
        $dayEnd = Carbon::today();

        try {
            $dailyStats = $wooCommerce->getRevenueStats(
                $dayStart->format('Y-m-d'),
                $dayEnd->format('Y-m-d'),
                'day'
            );

            // Build trend from Analytics response
            for ($i = 29; $i >= 0; $i--) {
                $day = Carbon::today()->subDays($i);
                $dailyTrend[] = [
                    'date' => $day->format('d.m'),
                    'orders' => 0,
                    'revenue' => 0
                ];
            }
        } catch (\Exception $e) {
            Log::warning('Failed to fetch daily trends', ['error' => $e->getMessage()]);
            $dailyTrend = [];
        }

        // Get today's opening hours from _apningstid table (only for non-admin users)
        $locationName = null;
        $isOpen = false;
        $openTime = null;
        $closeTime = null;
        $status = 0;

        if (!$user->isAdmin() && $userSiteId > 0) {
            // Find opening hours in _apningstid table
            $apningstidAlt = ApningstidAlternative::where('AvdID', $userSiteId)->first();

            if ($apningstidAlt) {
                $locationName = $apningstidAlt->Navn;
                $todayDay = Carbon::now()->format('l'); // Monday, Tuesday, etc.
                $todayDayLower = strtolower($todayDay);

                // Get hours for today
                $hoursToday = $apningstidAlt->getHoursForDay($todayDayLower);

                if ($hoursToday) {
                    $openTime = $hoursToday['start'];
                    $closeTime = $hoursToday['stop'];
                    $isClosed = $hoursToday['closed']; // 0 = open, 1 = closed

                    // Status for UI (1 = open, 0 = closed)
                    $status = ($isClosed == 0) ? 1 : 0;

                    // Only parse times if they're not empty and location is not closed
                    if ($openTime && $closeTime && !$isClosed) {
                        $now = Carbon::now();

                        // Handle different time formats
                        try {
                            $openDateTime = Carbon::createFromFormat('H:i:s', $openTime);
                        } catch (\Exception $e) {
                            $openDateTime = Carbon::createFromFormat('H:i', $openTime);
                        }

                        try {
                            $closeDateTime = Carbon::createFromFormat('H:i:s', $closeTime);
                        } catch (\Exception $e) {
                            $closeDateTime = Carbon::createFromFormat('H:i', $closeTime);
                        }

                        $isOpen = $now->between($openDateTime, $closeDateTime) && !$isClosed;
                    }
                }
            }
        }

        // Admin-specific data
        $adminStats = null;
        if ($user->isAdmin()) {
            $adminStats = [
                'total_users' => User::count(),
                'total_sites' => Site::count(),
                'total_orders_today' => Order::whereDate('datetime', Carbon::today())->count(),
                'total_pending_orders' => Order::where('ordrestatus', 0)->count(),
            ];
        }

        return view('admin.dashboard', compact(
            'locationName',
            'isOpen',
            'openTime',
            'closeTime',
            'status',
            'todayOrders',
            'todayRevenue',
            'weekOrders',
            'weekRevenue',
            'monthOrders',
            'monthRevenue',
            'previousMonthOrders',
            'previousMonthRevenue',
            'yearOrders',
            'yearRevenue',
            'ordersChange',
            'revenueChange',
            'recentOrders',
            'pendingOrders',
            'unpaidOrders',
            'monthlyTrend',
            'dailyTrend',
            'adminStats'
        ));
    }

    /**
     * Toggle location status.
     *
     * Updates _apningstid table only (used by root page, dashboard, and public display).
     */
    public function toggleStatus(Request $request)
    {
        $user = Auth::user();

        // Admin users don't have a specific location
        if ($user->isAdmin() || $user->siteid == 0) {
            return response()->json([
                'success' => false,
                'message' => 'Admin users cannot toggle location status'
            ], 400);
        }

        // Find the location's opening hours in _apningstid table
        $apningstidAlt = ApningstidAlternative::where('AvdID', $user->siteid)->first();

        if (!$apningstidAlt) {
            Log::warning('Location not found in _apningstid table', [
                'user_id' => $user->id,
                'username' => $user->username,
                'siteid' => $user->siteid
            ]);

            return response()->json([
                'success' => false,
                'message' => 'This location does not support status toggle. Please contact administrator.'
            ], 400);
        }

        $todayDay = Carbon::now()->format('l'); // e.g., "Monday"
        $todayDayLower = strtolower($todayDay); // e.g., "monday"

        // Day field mapping for _apningstid table
        $dayMapping = [
            'monday' => 'Man',
            'tuesday' => 'Tir',
            'wednesday' => 'Ons',
            'thursday' => 'Tor',
            'friday' => 'Fre',
            'saturday' => 'Lor',
            'sunday' => 'Son'
        ];

        if (!isset($dayMapping[$todayDayLower])) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid day'
            ], 400);
        }

        $dayPrefix = $dayMapping[$todayDayLower];
        $stengtField = $dayPrefix . 'Stengt'; // e.g., "ManStengt"

        // Get current status from _apningstid
        // In _apningstid: 0 = open, 1 = closed (stengt)
        $currentStengtValue = $apningstidAlt->$stengtField ?? 0;
        $newStengtValue = ($currentStengtValue == 1) ? 0 : 1; // Toggle: closed->open, open->closed

        // Update _apningstid table
        $apningstidAlt->update([$stengtField => $newStengtValue]);

        $locationName = $apningstidAlt->Navn ?? 'Unknown';
        $isNowOpen = ($newStengtValue == 0); // 0 = open, 1 = closed

        Log::info('Location status toggled', [
            'user_id' => $user->id,
            'location' => $locationName,
            'siteid' => $user->siteid,
            'avdid' => $apningstidAlt->AvdID,
            'day' => $todayDay,
            'stengt_field' => $stengtField,
            'old_value' => $currentStengtValue,
            'new_value' => $newStengtValue,
            'is_now_open' => $isNowOpen
        ]);

        return response()->json([
            'success' => true,
            'status' => $isNowOpen ? 1 : 0, // Return 1 for open, 0 for closed (for UI consistency)
            'message' => $isNowOpen ? 'Location opened' : 'Location closed'
        ]);
    }

    /**
     * Admin-specific dashboard (for users with siteid = 0)
     * Shows system overview without WooCommerce stats
     */
    private function adminDashboard()
    {
        // Get all sites
        $sites = Site::where('active', true)->orderBy('name')->get();

        // Get all users count
        $totalUsers = User::count();
        $activeUsers = User::where('siteid', '!=', 0)->count();

        // Get orders from database (not WooCommerce)
        $totalOrders = Order::count();
        $paidOrders = Order::where('paid', 1)->count();
        $unpaidOrders = Order::where('paid', 0)->count();

        // Recent activity
        $recentOrders = Order::orderBy('datetime', 'desc')->limit(10)->get();

        return view('admin.dashboard-admin', compact(
            'sites',
            'totalUsers',
            'activeUsers',
            'totalOrders',
            'paidOrders',
            'unpaidOrders',
            'recentOrders'
        ));
    }
}
