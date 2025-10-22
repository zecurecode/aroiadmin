<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\WooCommerceService;
use App\Models\Site;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class StatisticsController extends Controller
{
    /**
     * Display WooCommerce statistics for the logged-in user's site
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $user = Auth::user();

        if (!$user) {
            return redirect('/login')->withErrors(['error' => 'Authentication required']);
        }

        $userSiteId = $user->siteid;

        // Get site information
        $site = Site::findBySiteId($userSiteId);

        if (!$site) {
            return back()->withErrors(['error' => 'Site not found for your account']);
        }

        try {
            // Initialize WooCommerce service with site-specific credentials
            $wooCommerce = new WooCommerceService($userSiteId);

            // Fetch comprehensive statistics
            $statistics = $wooCommerce->getSiteStatistics($userSiteId);

            Log::info('Statistics fetched for site', [
                'site_id' => $userSiteId,
                'site_name' => $site->name,
                'year_revenue' => $statistics['year']['revenue'] ?? 0,
                'month_revenue' => $statistics['month']['revenue'] ?? 0,
                'pending_count' => $statistics['pending']['count'] ?? 0,
            ]);

            return view('admin.statistics.index', compact('statistics', 'site'));

        } catch (\Exception $e) {
            Log::error('Error fetching WooCommerce statistics', [
                'site_id' => $userSiteId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()->withErrors(['error' => 'Failed to fetch statistics from WooCommerce: ' . $e->getMessage()]);
        }
    }
}
