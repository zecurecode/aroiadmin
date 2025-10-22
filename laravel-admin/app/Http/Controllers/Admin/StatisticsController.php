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

        // If admin (siteid = 0), show site selector
        if ($userSiteId == 0 || $userSiteId === null) {
            $sites = Site::where('active', true)->orderBy('name')->get();
            return view('admin.statistics.select-site', compact('sites'));
        }

        // Get site information
        $site = Site::findBySiteId($userSiteId);

        if (!$site) {
            return back()->withErrors(['error' => 'Site not found for your account. Please contact administrator.']);
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

    /**
     * Show statistics for a specific site (for admin users)
     *
     * @param int $siteId
     * @return \Illuminate\View\View
     */
    public function showSite($siteId)
    {
        $user = Auth::user();

        if (!$user) {
            return redirect('/login')->withErrors(['error' => 'Authentication required']);
        }

        // Only allow admin users or users assigned to this site
        if ($user->siteid != 0 && $user->siteid != $siteId) {
            abort(403, 'Unauthorized access to this site statistics.');
        }

        // Get site information
        $site = Site::findBySiteId($siteId);

        if (!$site) {
            return back()->withErrors(['error' => 'Site not found.']);
        }

        try {
            // Initialize WooCommerce service with site-specific credentials
            $wooCommerce = new WooCommerceService($siteId);

            // Fetch comprehensive statistics
            $statistics = $wooCommerce->getSiteStatistics($siteId);

            Log::info('Statistics fetched for site', [
                'site_id' => $siteId,
                'site_name' => $site->name,
                'user_id' => $user->id,
                'year_revenue' => $statistics['year']['revenue'] ?? 0,
                'month_revenue' => $statistics['month']['revenue'] ?? 0,
                'pending_count' => $statistics['pending']['count'] ?? 0,
            ]);

            return view('admin.statistics.index', compact('statistics', 'site'));

        } catch (\Exception $e) {
            Log::error('Error fetching WooCommerce statistics', [
                'site_id' => $siteId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()->withErrors(['error' => 'Failed to fetch statistics from WooCommerce: ' . $e->getMessage()]);
        }
    }
}
