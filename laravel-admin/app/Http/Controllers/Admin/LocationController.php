<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Location;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $locations = Location::ordered()->get();
        return view('admin.locations.index', compact('locations'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.locations.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'site_id' => 'required|integer|unique:locations,site_id',
            'license' => 'required|integer',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:500',
            'order_url' => 'nullable|url|max:500',
            'group_name' => 'nullable|string|max:100',
            'display_order' => 'nullable|integer|min:0',
            'active' => 'nullable|in:on,1,true,0,false'
        ]);

        Location::create([
            'name' => $request->name,
            'site_id' => $request->site_id,
            'license' => $request->license,
            'phone' => $request->phone,
            'email' => $request->email,
            'address' => $request->address,
            'order_url' => $request->order_url,
            'group_name' => $request->group_name,
            'display_order' => $request->display_order ?? 0,
            'active' => $request->active == '1' || $request->active === true
        ]);

        return redirect()->route('admin.locations.index')
            ->with('success', 'Lokasjon opprettet.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $location = Location::findOrFail($id);
        return view('admin.locations.show', compact('location'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $location = Location::findOrFail($id);
        return view('admin.locations.edit', compact('location'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $location = Location::findOrFail($id);
        
        $request->validate([
            'name' => 'required|string|max:255',
            'license' => 'required|integer',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:500',
            'order_url' => 'nullable|url|max:500',
            'group_name' => 'nullable|string|max:100',
            'display_order' => 'nullable|integer|min:0',
            'active' => 'nullable|in:on,1,true,0,false'
        ]);

        $location->update([
            'name' => $request->name,
            'license' => $request->license,
            'phone' => $request->phone,
            'email' => $request->email,
            'address' => $request->address,
            'order_url' => $request->order_url,
            'group_name' => $request->group_name,
            'display_order' => $request->display_order ?? 0,
            'active' => $request->active == '1' || $request->active === true
        ]);

        return redirect()->route('admin.locations.index')
            ->with('success', 'Lokasjon oppdatert.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $location = Location::findOrFail($id);
        
        // Log the deletion attempt
        \Log::info('Attempting to delete location', [
            'location_id' => $location->id,
            'location_name' => $location->name,
            'site_id' => $location->site_id,
            'user_count' => $location->users()->count(),
            'order_count' => $location->orders()->count(),
            'request_method' => request()->method(),
            'is_delete_method' => request()->isMethod('DELETE')
        ]);
        
        // Check if there are associated users
        $userCount = $location->users()->count();
        if ($userCount > 0) {
            \Log::warning('Cannot delete location with users', [
                'location_id' => $location->id,
                'user_count' => $userCount
            ]);
            return redirect()->route('admin.locations.index')
                ->with('error', "Kan ikke slette lokasjon '{$location->name}' som har {$userCount} tilknyttede brukere. Vennligst fjern eller flytt brukerne først.");
        }
        
        // Check if there are associated orders
        $orderCount = $location->orders()->count();
        if ($orderCount > 0) {
            \Log::warning('Cannot delete location with orders', [
                'location_id' => $location->id,
                'order_count' => $orderCount
            ]);
            return redirect()->route('admin.locations.index')
                ->with('error', "Kan ikke slette lokasjon '{$location->name}' som har {$orderCount} eksisterende ordrer.");
        }
        
        $locationName = $location->name;
        $location->delete();
        
        \Log::info('Location deleted successfully', [
            'location_id' => $id,
            'location_name' => $locationName
        ]);

        return redirect()->route('admin.locations.index')
            ->with('success', "Lokasjon '{$locationName}' ble slettet.");
    }
}
