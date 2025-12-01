<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class MarketingController extends Controller
{
    /**
     * Display the marketing page.
     */
    public function index()
    {
        return view('admin.marketing');
    }
}
