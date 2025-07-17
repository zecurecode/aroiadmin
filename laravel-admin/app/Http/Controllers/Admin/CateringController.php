<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\CateringSettings;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class CateringController extends Controller
{
    /**
     * Display a listing of catering orders
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = Order::where('is_catering', true);
        
        // Filter by location if not admin
        if ($user->username !== 'admin') {
            $query->where('site', $user->siteid);
        }
        
        // Filter by status
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('catering_status', $request->status);
        }
        
        // Filter by date
        if ($request->has('date')) {
            $query->whereDate('delivery_date', $request->date);
        }
        
        $orders = $query->orderBy('delivery_date', 'asc')
                       ->orderBy('delivery_time', 'asc')
                       ->paginate(20);
        
        $locations = Location::all();
        
        return view('admin.catering.index', compact('orders', 'locations'));
    }

    /**
     * Show catering order details
     */
    public function show($id)
    {
        $user = Auth::user();
        $order = Order::where('is_catering', true)->findOrFail($id);
        
        // Check permission
        if ($user->username !== 'admin' && $order->site != $user->siteid) {
            abort(403);
        }
        
        return view('admin.catering.show', compact('order'));
    }

    /**
     * Update catering order status
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending,confirmed,preparing,ready,delivered,cancelled'
        ]);
        
        $user = Auth::user();
        $order = Order::where('is_catering', true)->findOrFail($id);
        
        // Check permission
        if ($user->username !== 'admin' && $order->site != $user->siteid) {
            abort(403);
        }
        
        $order->updateCateringStatus($request->status);
        
        // Send notification if status is ready
        if ($request->status === 'ready') {
            $apiController = new \App\Http\Controllers\Api\ApiController();
            $message = "Din cateringbestilling er klar! Ordre #{$order->ordreid}";
            $apiController->sendSms($order->telefon, $message);
        }
        
        return redirect()->route('admin.catering.show', $id)
                       ->with('success', 'Status oppdatert');
    }

    /**
     * Show catering settings
     */
    public function settings()
    {
        $user = Auth::user();
        
        if ($user->username === 'admin') {
            $settings = CateringSettings::all();
        } else {
            $settings = CateringSettings::where('site_id', $user->siteid)->get();
        }
        
        $locations = Location::all();
        
        return view('admin.catering.settings', compact('settings', 'locations'));
    }

    /**
     * Update catering settings
     */
    public function updateSettings(Request $request, $siteId)
    {
        $request->validate([
            'catering_email' => 'nullable|email',
            'catering_enabled' => 'boolean',
            'min_guests' => 'required|integer|min:1',
            'advance_notice_days' => 'required|integer|min:0',
            'min_order_amount' => 'required|numeric|min:0',
            'catering_info' => 'nullable|string'
        ]);
        
        $user = Auth::user();
        
        // Check permission
        if ($user->username !== 'admin' && $siteId != $user->siteid) {
            abort(403);
        }
        
        $settings = CateringSettings::where('site_id', $siteId)->first();
        
        if (!$settings) {
            $settings = new CateringSettings();
            $settings->site_id = $siteId;
        }
        
        $settings->fill($request->all());
        $settings->save();
        
        return redirect()->route('admin.catering.settings')
                       ->with('success', 'Innstillinger oppdatert');
    }

    /**
     * Manage blocked dates
     */
    public function blockedDates($siteId)
    {
        $user = Auth::user();
        
        // Check permission
        if ($user->username !== 'admin' && $siteId != $user->siteid) {
            abort(403);
        }
        
        $settings = CateringSettings::where('site_id', $siteId)->firstOrFail();
        $location = Location::where('site_id', $siteId)->first();
        
        return view('admin.catering.blocked-dates', compact('settings', 'location'));
    }

    /**
     * Add blocked date
     */
    public function addBlockedDate(Request $request, $siteId)
    {
        $request->validate([
            'date' => 'required|date|after:today'
        ]);
        
        $user = Auth::user();
        
        // Check permission
        if ($user->username !== 'admin' && $siteId != $user->siteid) {
            abort(403);
        }
        
        $settings = CateringSettings::where('site_id', $siteId)->firstOrFail();
        $settings->addBlockedDate($request->date);
        
        return redirect()->route('admin.catering.blocked-dates', $siteId)
                       ->with('success', 'Dato blokkert');
    }

    /**
     * Remove blocked date
     */
    public function removeBlockedDate(Request $request, $siteId)
    {
        $request->validate([
            'date' => 'required|date'
        ]);
        
        $user = Auth::user();
        
        // Check permission
        if ($user->username !== 'admin' && $siteId != $user->siteid) {
            abort(403);
        }
        
        $settings = CateringSettings::where('site_id', $siteId)->firstOrFail();
        $settings->removeBlockedDate($request->date);
        
        return redirect()->route('admin.catering.blocked-dates', $siteId)
                       ->with('success', 'Dato fjernet');
    }

    /**
     * Export catering orders
     */
    public function export(Request $request)
    {
        $user = Auth::user();
        $query = Order::where('is_catering', true);
        
        // Filter by location if not admin
        if ($user->username !== 'admin') {
            $query->where('site', $user->siteid);
        }
        
        // Filter by date range
        if ($request->has('from_date')) {
            $query->whereDate('delivery_date', '>=', $request->from_date);
        }
        
        if ($request->has('to_date')) {
            $query->whereDate('delivery_date', '<=', $request->to_date);
        }
        
        $orders = $query->orderBy('delivery_date', 'asc')->get();
        
        $csvData = "Ordre ID,Kunde,Telefon,E-post,Leveringsdato,Leveringstid,Adresse,Antall gjester,Status,Total\n";
        
        foreach ($orders as $order) {
            $csvData .= "{$order->ordreid},{$order->fullName},{$order->telefon},{$order->epost},";
            $csvData .= "{$order->delivery_date},{$order->delivery_time},{$order->delivery_address},";
            $csvData .= "{$order->number_of_guests},{$order->catering_status},\n";
        }
        
        return response($csvData)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="catering-orders-' . date('Y-m-d') . '.csv"');
    }
}
