<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

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