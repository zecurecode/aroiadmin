<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Site;
use App\Models\User;
use Illuminate\Http\Request;

class SiteController extends Controller
{
    /**
     * Display a listing of sites.
     */
    public function index()
    {
        $sites = Site::withCount('users', 'orders')->paginate(15);
        return view('admin.sites.index', compact('sites'));
    }

    /**
     * Show the form for creating a new site.
     */
    public function create()
    {
        return view('admin.sites.create');
    }

    /**
     * Store a newly created site in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'site_id' => 'required|integer|unique:sites',
            'url' => 'required|url|max:255',
            'consumer_key' => 'required|string|max:255',
            'consumer_secret' => 'required|string|max:255',
            'license' => 'required|integer|min:0',
            'active' => 'boolean',
        ]);

        $validated['active'] = $request->has('active');

        Site::create($validated);

        return redirect()->route('admin.sites.index')
            ->with('success', 'Site created successfully.');
    }

    /**
     * Display the specified site.
     */
    public function show(Site $site)
    {
        $site->load('users', 'orders');
        return view('admin.sites.show', compact('site'));
    }

    /**
     * Show the form for editing the specified site.
     */
    public function edit(Site $site)
    {
        return view('admin.sites.edit', compact('site'));
    }

    /**
     * Update the specified site in storage.
     */
    public function update(Request $request, Site $site)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'site_id' => 'required|integer|unique:sites,site_id,' . $site->id,
            'url' => 'required|url|max:255',
            'consumer_key' => 'required|string|max:255',
            'consumer_secret' => 'required|string|max:255',
            'license' => 'required|integer|min:0',
            'active' => 'boolean',
        ]);

        $validated['active'] = $request->has('active');

        $site->update($validated);

        // Update license for all users of this site
        User::where('siteid', $site->site_id)->update(['license' => $validated['license']]);

        return redirect()->route('admin.sites.index')
            ->with('success', 'Site updated successfully.');
    }

    /**
     * Remove the specified site from storage.
     */
    public function destroy(Site $site)
    {
        // Check if site has users
        if ($site->users()->count() > 0) {
            return redirect()->route('admin.sites.index')
                ->with('error', 'Cannot delete site with associated users.');
        }

        $site->delete();

        return redirect()->route('admin.sites.index')
            ->with('success', 'Site deleted successfully.');
    }

    /**
     * Show users for a specific site.
     */
    public function users(Site $site)
    {
        $users = $site->users()->paginate(15);
        return view('admin.sites.users', compact('site', 'users'));
    }

    /**
     * Assign user to site.
     */
    public function assignUser(Request $request, Site $site)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $user = User::find($validated['user_id']);
        $user->update([
            'siteid' => $site->site_id,
            'license' => $site->license,
        ]);

        return redirect()->route('admin.sites.users', $site)
            ->with('success', 'User assigned to site successfully.');
    }
}
