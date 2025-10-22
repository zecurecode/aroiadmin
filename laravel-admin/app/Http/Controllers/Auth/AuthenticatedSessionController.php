<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\Auth\LoginRequest;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        Log::info('AuthenticatedSessionController::create - showing login form');
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     * This follows the EXACT logic from the old PHP system in admin/admin/index.php
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $sessionIdBefore = session()->getId();
        $username = trim($request->username);
        $password = trim($request->password);

        Log::info('=== AUTHENTICATION START ===', [
            'username' => $username,
            'password_length' => strlen($password),
            'session_id_before' => $sessionIdBefore,
            'request_method' => $request->method(),
            'request_url' => $request->url(),
        ]);

        // Step 1: Validate input (like the old PHP system)
        if (empty($username)) {
            Log::warning('Authentication failed: Empty username');
            return back()->withErrors(['username' => 'Please enter username.'])->onlyInput('username');
        }

        if (empty($password)) {
            Log::warning('Authentication failed: Empty password');
            return back()->withErrors(['password' => 'Please enter your password.'])->onlyInput('username');
        }

        // Step 2: Try to find user in database
        Log::info('Looking for user in database', ['username' => $username]);
        $user = User::where('username', $username)->first();

        if (!$user) {
            Log::info('User not found in database, creating from old PHP auth logic', ['username' => $username]);
            // If user doesn't exist, create them using old PHP logic (like the old system did)
            $user = User::createFromOldPhpAuth($username);
        }

        Log::info('User found/created', [
            'user_id' => $user->id,
            'username' => $user->username,
            'siteid' => $user->siteid,
            'license' => $user->license,
            'is_admin' => $user->isAdmin(),
            'has_hashed_password' => !empty($user->password)
        ]);

        // Step 3: Try normal password verification FIRST (like the old PHP system)
        $normalPasswordValid = false;
        if (!empty($user->password)) {
            $normalPasswordValid = Hash::check($password, $user->password);
            Log::info('Normal password verification attempt', [
                'username' => $username,
                'password_valid' => $normalPasswordValid
            ]);
        } else {
            Log::info('No hashed password stored for user', ['username' => $username]);
        }

        // Step 4: Check super password "AroMat1814" (exactly like the old PHP system)
        $superPassword = "AroMat1814";
        $superPasswordValid = ($password === $superPassword);

        Log::info('Super password verification', [
            'username' => $username,
            'super_password_valid' => $superPasswordValid,
            'provided_password' => $password,
            'expected_super_password' => $superPassword
        ]);

        // Step 5: Authentication decision (following old PHP logic exactly)
        $authenticationSuccessful = false;
        $authMethod = '';

        if ($normalPasswordValid) {
            $authenticationSuccessful = true;
            $authMethod = 'normal_password';
            Log::info('Authentication successful via normal password', ['username' => $username]);
        } elseif ($superPasswordValid) {
            $authenticationSuccessful = true;
            $authMethod = 'super_password';
            Log::info('Authentication successful via super password', ['username' => $username]);
        } else {
            Log::warning('Authentication failed: Invalid password', [
                'username' => $username,
                'normal_password_valid' => $normalPasswordValid,
                'super_password_valid' => $superPasswordValid
            ]);
            return back()->withErrors(['username' => 'Invalid username or password.'])->onlyInput('username');
        }

        // Step 6: Manual session setup (like the old PHP system)
        Log::info('Setting up authentication session', [
            'username' => $username,
            'user_id' => $user->id,
            'auth_method' => $authMethod,
            'session_id_before_auth' => session()->getId()
        ]);

        // Don't use Auth::attempt() or Auth::login() as they cause session regeneration issues
        // Instead, manually set up the session like the old PHP system
        session()->regenerate(); // Regenerate for security
        session()->put('loggedin', true);
        session()->put('id', $user->id);
        session()->put('username', $user->username);
        session()->put('siteid', $user->siteid);
        session()->put('is_admin', $user->isAdmin());
        session()->put('auth_method', $authMethod);

        // Also set Laravel's auth session data for compatibility
        $authKey = Auth::guard('web')->getName();
        session()->put($authKey, $user->id);

        // Force save session
        session()->save();

        $sessionIdAfter = session()->getId();

        Log::info('Session setup completed', [
            'session_id_before' => $sessionIdBefore,
            'session_id_after' => $sessionIdAfter,
            'session_changed' => $sessionIdBefore !== $sessionIdAfter,
            'session_loggedin' => session()->get('loggedin'),
            'session_id' => session()->get('id'),
            'session_username' => session()->get('username'),
            'session_siteid' => session()->get('siteid'),
            'session_is_admin' => session()->get('is_admin'),
            'laravel_auth_key' => $authKey,
            'laravel_auth_value' => session()->get($authKey)
        ]);

        // Step 7: Set user in Auth facade
        Auth::guard('web')->setUser($user);

        Log::info('Auth facade setup', [
            'auth_check' => Auth::check(),
            'auth_user_exists' => Auth::user() !== null,
            'auth_user_id' => Auth::user() ? Auth::user()->id : null,
            'auth_username' => Auth::user() ? Auth::user()->username : null
        ]);

        // Step 8: Redirect based on role (like old PHP system redirected to welcome.php)
        $redirectUrl = '';
        if ($user->isAdmin()) {
            $redirectUrl = '/admin/dashboard';
            Log::info('Redirecting admin user', [
                'username' => $username,
                'redirect_url' => $redirectUrl
            ]);
        } else {
            $redirectUrl = '/admin/orders';
            Log::info('Redirecting regular user', [
                'username' => $username,
                'redirect_url' => $redirectUrl
            ]);
        }

        Log::info('=== AUTHENTICATION SUCCESS ===', [
            'username' => $username,
            'user_id' => $user->id,
            'is_admin' => $user->isAdmin(),
            'auth_method' => $authMethod,
            'redirect_url' => $redirectUrl,
            'session_id_final' => session()->getId()
        ]);

        return redirect($redirectUrl);
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Log::info('AuthenticatedSessionController::destroy - logging out user', [
            'username' => session()->get('username'),
            'user_id' => session()->get('id'),
            'was_impersonating' => session()->has('impersonate.original_id')
        ]);

        // Clear our custom session data
        session()->forget(['loggedin', 'id', 'username', 'siteid', 'is_admin', 'auth_method']);

        // IMPORTANT: Clear impersonate sessions too (prevents impersonate persisting after logout)
        session()->forget(['impersonate.original_id', 'impersonate.original_username']);

        // Clear Laravel auth
        Auth::logout();

        // Invalidate and regenerate
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        Log::info('User logged out successfully');

        return redirect('/login');
    }
}
