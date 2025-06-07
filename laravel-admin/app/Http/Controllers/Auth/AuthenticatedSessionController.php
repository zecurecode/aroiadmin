<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        // Get credentials
        $username = $request->username;
        $password = $request->password;

        // Try to find user by username
        $user = User::where('username', $username)->first();

        // If user exists, check password
        if ($user) {
            // Check if it's a hashed password first
            if (Hash::check($password, $user->password)) {
                Auth::login($user, $request->boolean('remember'));
                $request->session()->regenerate();
                return redirect()->intended(route('admin.dashboard', absolute: false));
            }
        }

        // Legacy authentication for existing system
        if ($password === 'AroMat1814') {
            $siteIds = [
                'steinkjer' => 17,
                'namsos' => 7,
                'lade' => 4,
                'moan' => 6,
                'gramyra' => 5,
                'frosta' => 10,
                'hell' => 11,
            ];

            if (isset($siteIds[$username])) {
                // Create or find user for legacy login
                $user = User::firstOrCreate(
                    ['username' => $username],
                    [
                        'name' => ucfirst($username), // Use capitalized username as display name
                        'siteid' => $siteIds[$username],
                        'password' => Hash::make('AroMat1814'), // Hash the legacy password
                        'license' => $this->getLicenseForSite($siteIds[$username]),
                    ]
                );

                Auth::login($user, $request->boolean('remember'));
                $request->session()->regenerate();
                return redirect()->intended(route('admin.dashboard', absolute: false));
            }
        }

        // If we get here, authentication failed
        $request->validate([
            'username' => ['required'],
            'password' => ['required'],
        ]);

        return back()->withErrors([
            'username' => 'The provided credentials do not match our records.',
        ])->onlyInput('username');
    }

    /**
     * Get license number for site ID.
     */
    private function getLicenseForSite($siteId)
    {
        $licenses = [
            7 => 6714,   // Namsos
            4 => 12381,  // Lade
            6 => 5203,   // Moan
            5 => 6715,   // Gramyra
            10 => 14780, // Frosta
            11 => 0,     // Hell
            17 => 0,     // Steinkjer
        ];

        return $licenses[$siteId] ?? 0;
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
