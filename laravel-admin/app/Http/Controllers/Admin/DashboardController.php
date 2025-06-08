<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Location;
use App\Models\OpeningHours;
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

        // Get statistics for user's location (or all locations for admin)
        if ($user->isAdmin()) {
            // Admin sees all orders
            $todayOrders = Order::whereDate('datetime', Carbon::today())->count();
            $pendingOrders = Order::where('ordrestatus', 0)->count();
            $unpaidOrders = Order::where('paid', 0)->count();
            $recentOrders = Order::orderBy('datetime', 'desc')->limit(10)->get();
        } else {
            // Regular users see only their location's orders
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
        }

        // Get today's opening hours (only for non-admin users)
        $locationName = null;
        $isOpen = false;
        $openTime = null;
        $closeTime = null;
        $status = 0;

        if (!$user->isAdmin() && $userSiteId > 0) {
            $locationName = Location::getNameBySiteId($userSiteId);
            $todayDay = Carbon::now()->format('l'); // Monday, Tuesday, etc.
            $openingHours = OpeningHours::where('day', $todayDay)->first();

            if ($openingHours) {
                $openTime = $openingHours->getOpenTime($locationName);
                $closeTime = $openingHours->getCloseTime($locationName);
                $status = $openingHours->getStatus($locationName);

                // Only parse times if they're not empty
                if ($openTime && $closeTime) {
                    $now = Carbon::now();
                    $openDateTime = Carbon::createFromFormat('H:i:s', $openTime);
                    $closeDateTime = Carbon::createFromFormat('H:i:s', $closeTime);

                    $isOpen = $now->between($openDateTime, $closeDateTime) && $status == 1;
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
            'todayOrders',
            'pendingOrders',
            'unpaidOrders',
            'recentOrders',
            'locationName',
            'isOpen',
            'openTime',
            'closeTime',
            'status',
            'adminStats'
        ));
    }

    /**
     * Toggle location status.
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

        $locationName = Location::getNameBySiteId($user->siteid);
        $todayDay = Carbon::now()->format('l');

        $openingHours = OpeningHours::where('day', $todayDay)->first();

        if ($openingHours) {
            $currentStatus = $openingHours->getStatus($locationName);
            $newStatus = $currentStatus ? 0 : 1;
            $openingHours->setStatus($locationName, $newStatus);

            return response()->json([
                'success' => true,
                'status' => $newStatus,
                'message' => $newStatus ? 'Location opened' : 'Location closed'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Could not update status'
        ], 400);
    }
}
