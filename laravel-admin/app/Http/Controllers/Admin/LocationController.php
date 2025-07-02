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
        $locations = Location::orderBy('name')->get();
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
            'active' => 'boolean'
        ]);

        Location::create([
            'name' => $request->name,
            'site_id' => $request->site_id,
            'license' => $request->license,
            'phone' => $request->phone,
            'email' => $request->email,
            'address' => $request->address,
            'active' => $request->has('active')
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
            'active' => 'boolean'
        ]);

        $location->update([
            'name' => $request->name,
            'license' => $request->license,
            'phone' => $request->phone,
            'email' => $request->email,
            'address' => $request->address,
            'active' => $request->has('active')
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
        $location->delete();

        return redirect()->route('admin.locations.index')
            ->with('success', 'Lokasjon slettet.');
    }
}
