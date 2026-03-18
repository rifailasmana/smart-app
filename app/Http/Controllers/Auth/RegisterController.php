<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Warung;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class RegisterController extends Controller
{
    /**
     * Show register form
     */
    public function show()
    {
        return view('auth.register');
    }

    /**
     * Handle registration
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'warung_name' => 'required|string|max:255|unique:warungs,name',
            'warung_code' => 'required|string|max:10|unique:warungs,code',
            'owner_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|confirmed|min:8',
            'subscription_tier' => 'required|in:starter,professional,enterprise',
        ]);

        // Create warung
        $warung = Warung::create([
            'name' => $validated['warung_name'],
            'code' => strtoupper($validated['warung_code']),
            'subscription_tier' => $validated['subscription_tier'],
            'monthly_price' => match($validated['subscription_tier']) {
                'starter' => 150000,
                'professional' => 250000,
                'enterprise' => 0,
            },
        ]);

        // Create owner user
        $user = User::create([
            'name' => $validated['owner_name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'owner',
            'warung_id' => $warung->id,
        ]);

        // Login user
        Auth::login($user);

        return redirect()->route('dashboard')->with('success', 'Selamat datang! Restoran Anda telah terdaftar.');
    }
}
