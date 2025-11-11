<?php

namespace App\Http\Controllers;

use App\Models\CateringOrder;
use App\Models\CateringSettings;
use App\Models\Location;
use App\Services\WooCommerceApiClient;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CateringController extends Controller
{
    protected $woocommerce;

    public function __construct()
    {
        $this->woocommerce = new WooCommerceApiClient;
    }

    /**
     * Show location selection page
     */
    public function index()
    {
        $locations = Location::active()
            ->with(['cateringSettings' => function ($query) {
                $query->where('catering_enabled', true);
            }])
            ->ordered()
            ->get()
            ->filter(function ($location) {
                return $location->cateringSettings && $location->cateringSettings->catering_enabled;
            });

        return view('catering.index', compact('locations'));
    }

    /**
     * Show product selection for specific location
     */
    public function selectProducts($locationId)
    {
        $location = Location::findOrFail($locationId);

        // Check if catering is enabled for this location
        $cateringSettings = CateringSettings::where('site_id', $location->site_id)->first();
        if (! $cateringSettings || ! $cateringSettings->catering_enabled) {
            return redirect()->route('catering.index')
                ->with('error', 'Catering er ikke tilgjengelig for denne lokasjonen.');
        }

        // Get products from WooCommerce API for this location
        $products = $this->woocommerce->getProductsForLocation($location->site_id);

        return view('catering.products', compact('location', 'products', 'cateringSettings'));
    }

    /**
     * Show order form with selected products
     */
    public function orderForm(Request $request, $locationId)
    {
        $location = Location::findOrFail($locationId);
        $cateringSettings = CateringSettings::where('site_id', $location->site_id)->firstOrFail();

        // Get selected products from session or request
        $selectedProducts = $request->session()->get('catering_products', []);

        if (empty($selectedProducts)) {
            return redirect()->route('catering.products', $locationId)
                ->with('error', 'Vennligst velg produkter først.');
        }

        // Calculate minimum date based on advance notice
        $minDate = Carbon::now()->addDays($cateringSettings->advance_notice_days)->format('Y-m-d');

        // Get blocked dates
        $blockedDates = $cateringSettings->blocked_dates ?? [];

        return view('catering.order-form', compact('location', 'cateringSettings', 'selectedProducts', 'minDate', 'blockedDates'));
    }

    /**
     * Store catering order
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'location_id' => 'required|exists:locations,id',
            'delivery_date' => 'required|date|after:today',
            'delivery_time' => 'required',
            'delivery_address' => 'required|string|max:500',
            'number_of_guests' => 'required|integer|min:1',
            'contact_name' => 'required|string|max:255',
            'contact_phone' => 'required|string|max:20',
            'contact_email' => 'required|email|max:255',
            'company_name' => 'required|string|max:255',
            'company_org_number' => 'required|string|max:50',
            'invoice_address' => 'required|string|max:500',
            'invoice_email' => 'required|email|max:255',
            'special_requirements' => 'nullable|string|max:1000',
            'catering_notes' => 'nullable|string|max:1000',
            'products' => 'required|array',
            'products.*.id' => 'required|integer',
            'products.*.quantity' => 'required|integer|min:1',
            'products.*.price' => 'required|numeric|min:0',
            'products.*.name' => 'required|string',
        ]);

        $location = Location::findOrFail($validated['location_id']);
        $cateringSettings = CateringSettings::where('site_id', $location->site_id)->firstOrFail();

        // Validate minimum guests
        if ($validated['number_of_guests'] < $cateringSettings->min_guests) {
            return back()->withErrors([
                'number_of_guests' => "Minimum antall gjester er {$cateringSettings->min_guests}.",
            ])->withInput();
        }

        // Validate advance notice
        $deliveryDate = Carbon::parse($validated['delivery_date']);
        $minDate = Carbon::now()->addDays($cateringSettings->advance_notice_days);
        if ($deliveryDate->lt($minDate)) {
            return back()->withErrors([
                'delivery_date' => "Bestilling må gjøres minst {$cateringSettings->advance_notice_days} dager i forveien.",
            ])->withInput();
        }

        // Check if date is blocked
        if (in_array($validated['delivery_date'], $cateringSettings->blocked_dates ?? [])) {
            return back()->withErrors([
                'delivery_date' => 'Denne datoen er ikke tilgjengelig for catering.',
            ])->withInput();
        }

        // Calculate total amount
        $totalAmount = 0;
        foreach ($validated['products'] as $product) {
            $totalAmount += $product['price'] * $product['quantity'];
        }

        // Validate minimum order amount
        if ($totalAmount < $cateringSettings->min_order_amount) {
            return back()->withErrors([
                'products' => "Minimumsbeløp for catering er {$cateringSettings->min_order_amount} kr.",
            ])->withInput();
        }

        DB::beginTransaction();
        try {
            // Create catering order
            $order = CateringOrder::create([
                'location_id' => $location->id,
                'site_id' => $location->site_id,
                'order_number' => 'CAT-'.time().'-'.rand(1000, 9999),
                'delivery_date' => $validated['delivery_date'],
                'delivery_time' => $validated['delivery_time'],
                'delivery_address' => $validated['delivery_address'],
                'number_of_guests' => $validated['number_of_guests'],
                'contact_name' => $validated['contact_name'],
                'contact_phone' => $validated['contact_phone'],
                'contact_email' => $validated['contact_email'],
                'company_name' => $validated['company_name'],
                'company_org_number' => $validated['company_org_number'],
                'invoice_address' => $validated['invoice_address'],
                'invoice_email' => $validated['invoice_email'],
                'special_requirements' => $validated['special_requirements'],
                'catering_notes' => $validated['catering_notes'],
                'products' => $validated['products'],
                'total_amount' => $totalAmount,
                'status' => 'pending',
                'catering_email' => $cateringSettings->catering_email,
            ]);

            // Send notifications
            $this->sendNotifications($order);

            DB::commit();

            // Clear session
            $request->session()->forget('catering_products');

            return redirect()->route('catering.confirmation', $order->id);

        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withErrors(['error' => 'Det oppstod en feil ved opprettelse av bestilling.'])
                ->withInput();
        }
    }

    /**
     * Show confirmation page
     */
    public function confirmation($orderId)
    {
        $order = CateringOrder::with('location')->findOrFail($orderId);

        return view('catering.confirmation', compact('order'));
    }

    /**
     * Check availability for a specific date
     */
    public function checkAvailability(Request $request)
    {
        $validated = $request->validate([
            'location_id' => 'required|exists:locations,id',
            'date' => 'required|date',
        ]);

        $location = Location::findOrFail($validated['location_id']);
        $cateringSettings = CateringSettings::where('site_id', $location->site_id)->first();

        if (! $cateringSettings || ! $cateringSettings->catering_enabled) {
            return response()->json(['available' => false, 'message' => 'Catering ikke tilgjengelig']);
        }

        // Check if date is blocked
        if (in_array($validated['date'], $cateringSettings->blocked_dates ?? [])) {
            return response()->json(['available' => false, 'message' => 'Dato ikke tilgjengelig']);
        }

        // Check advance notice
        $deliveryDate = Carbon::parse($validated['date']);
        $minDate = Carbon::now()->addDays($cateringSettings->advance_notice_days);
        if ($deliveryDate->lt($minDate)) {
            return response()->json([
                'available' => false,
                'message' => "Bestilling må gjøres minst {$cateringSettings->advance_notice_days} dager i forveien",
            ]);
        }

        return response()->json(['available' => true]);
    }

    /**
     * Store selected products in session
     */
    public function storeProducts(Request $request)
    {
        $validated = $request->validate([
            'location_id' => 'required|exists:locations,id',
            'products' => 'required|array|min:1',
            'products.*.id' => 'required|integer',
            'products.*.quantity' => 'required|integer|min:1',
            'products.*.name' => 'required|string',
            'products.*.price' => 'required|numeric|min:0',
        ]);

        $request->session()->put('catering_products', $validated['products']);

        return redirect()->route('catering.order-form', $validated['location_id']);
    }

    /**
     * Send notifications for new order
     */
    protected function sendNotifications($order)
    {
        // Send email to catering email
        if ($order->catering_email) {
            // Implement email sending logic here
            // You can use Laravel's Mail facade
        }

        // Send SMS to customer
        if ($order->contact_phone) {
            // Implement SMS sending logic here
            // Using existing Teletopia integration
        }
    }
}
