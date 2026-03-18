<?php

namespace App\Http\Controllers;

class LandingController extends Controller
{
    public function index()
    {
        return view('landing.index');
    }

    public function pricing()
    {
        return view('landing.pricing');
    }
}
