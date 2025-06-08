<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\Order;
use App\Models\Location;
use App\Models\OpeningHours;
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

        // Get today's opening hours
        $todayDay = Carbon::now()->format('l'); // Monday, Tuesday, etc.
        $openingHours = OpeningHours::where('day', $todayDay)->first();

        $isOpen = false;
        $openTime = null;
        $closeTime = null;
        $status = 0;

        if ($openingHours) {
            $openTime = $openingHours->getOpenTime($locationName);
            $closeTime = $openingHours->getCloseTime($locationName);
            $status = $openingHours->getStatus($locationName);

            // Only parse times if they're not empty
            if ($openTime && $closeTime) {
                $now = Carbon::now();
                try {
                    $openDateTime = Carbon::createFromFormat('H:i:s', $openTime);
                    $closeDateTime = Carbon::createFromFormat('H:i:s', $closeTime);
                    $isOpen = $now->between($openDateTime, $closeDateTime) && $status == 1;
                } catch (\Exception $e) {
                    // If time parsing fails, default to closed
                    $isOpen = false;
                }
            }
        }

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
        ];

        Log::info('Dashboard data prepared', [
            'username' => $username,
            'location_name' => $locationName,
            'today_orders' => $todayOrders,
            'pending_orders' => $pendingOrders,
            'unpaid_orders' => $unpaidOrders,
        ]);

        return view('dashboard', $dashboardData);
    }
}
