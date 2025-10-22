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

        // TODAY'S STATS
        $todayStats = $wooCommerce->getCompletedOrdersStats($userSiteId, $today);
        $todayOrders = $todayStats['count'];
        $todayRevenue = $todayStats['revenue'];

        // THIS WEEK'S STATS
        $weekStats = $wooCommerce->getCompletedOrdersStats($userSiteId, $startOfWeek);
        $weekOrders = $weekStats['count'];
        $weekRevenue = $weekStats['revenue'];

        // THIS MONTH'S STATS
        $monthStats = $wooCommerce->getCompletedOrdersStats($userSiteId, $startOfMonth);
        $monthOrders = $monthStats['count'];
        $monthRevenue = $monthStats['revenue'];

        // PREVIOUS MONTH'S STATS
        $previousMonthStats = $wooCommerce->getCompletedOrdersStats($userSiteId, $startOfPreviousMonth, $endOfPreviousMonth);
        $previousMonthOrders = $previousMonthStats['count'];
        $previousMonthRevenue = $previousMonthStats['revenue'];

        // YEAR-TO-DATE STATS
        $yearStats = $wooCommerce->getCompletedOrdersStats($userSiteId, $startOfYear);
        $yearOrders = $yearStats['count'];
        $yearRevenue = $yearStats['revenue'];

        // COMPARISON WITH PREVIOUS MONTH
        $ordersChange = $previousMonthOrders > 0
            ? (($monthOrders - $previousMonthOrders) / $previousMonthOrders) * 100
            : 0;

        $revenueChange = $previousMonthRevenue > 0
            ? (($monthRevenue - $previousMonthRevenue) / $previousMonthRevenue) * 100
            : 0;

        // PENDING ORDERS (WARNING)
        $pendingOrdersWC = $wooCommerce->getOrdersByStatus('pending', $userSiteId);
        $pendingOrders = $pendingOrdersWC ? count($pendingOrdersWC) : 0;

        // UNPAID ORDERS (use local database)
        $unpaidOrders = Order::where('site', $userSiteId)->where('paid', 0)->count();

        // RECENT ORDERS (last 10 completed from WooCommerce)
        $recentOrdersWC = $wooCommerce->getOrdersByStatus('completed', $userSiteId, [
            'per_page' => 10,
            'orderby' => 'date',
            'order' => 'desc'
        ]);

        // Convert WC orders to simplified format for display
        $recentOrders = [];
        if ($recentOrdersWC) {
            foreach ($recentOrdersWC as $wcOrder) {
                $recentOrders[] = (object) [
                    'ordreid' => $wcOrder['id'],
                    'fornavn' => $wcOrder['billing']['first_name'] ?? '',
                    'etternavn' => $wcOrder['billing']['last_name'] ?? '',
                    'telefon' => $wcOrder['billing']['phone'] ?? '',
                    'datetime' => Carbon::parse($wcOrder['date_created']),
                    'total_amount' => (float) $wcOrder['total'],
                    'ordrestatus' => 2 // Completed
                ];
            }
        }

        // MONTHLY TREND DATA (Last 12 months)
        $monthlyTrend = [];
        for ($i = 11; $i >= 0; $i--) {
            $monthStart = Carbon::now()->subMonths($i)->startOfMonth();
            $monthEnd = Carbon::now()->subMonths($i)->endOfMonth();

            $stats = $wooCommerce->getCompletedOrdersStats(
                $userSiteId,
                $monthStart->format('Y-m-d'),
                $monthEnd->format('Y-m-d')
            );

            $monthlyTrend[] = [
                'month' => $monthStart->format('M'),
                'year' => $monthStart->format('Y'),
                'orders' => $stats['count'],
                'revenue' => $stats['revenue']
            ];
        }

        // DAILY TREND DATA (Last 30 days)
        $dailyTrend = [];
        for ($i = 29; $i >= 0; $i--) {
            $day = Carbon::today()->subDays($i);

            $stats = $wooCommerce->getCompletedOrdersStats(
                $userSiteId,
                $day->format('Y-m-d'),
                $day->format('Y-m-d')
            );

            $dailyTrend[] = [
                'date' => $day->format('d.m'),
                'orders' => $stats['count'],
                'revenue' => $stats['revenue']
            ];
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
}
