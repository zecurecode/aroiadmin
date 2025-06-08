<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Illuminate\Support\Facades\Log;
use App\Models\User;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        Log::info('Login page accessed', [
            'session_id' => session()->getId(),
            'auth_check' => Auth::check()
        ]);
        
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $sessionId = session()->getId();
        
        Log::info('=== CONTROLLER TEST 2025-06-08-00:40 MANUAL LOGIN ATTEMPT ===', [
            'username' => $request->username,
            'session_id_before' => $sessionId
        ]);

        // Find user by username
        $user = User::where('username', $request->username)->first();
        
        if (!$user || !password_verify($request->password, $user->password)) {
            return back()->withErrors([
                'username' => 'The provided credentials do not match our records.',
            ])->onlyInput('username');
        }

        Log::info('Credentials verified, manually setting session', [
            'user_id' => $user->id,
            'username' => $user->username,
            'is_admin' => $user->isAdmin()
        ]);

        // MANUALLY set authentication in session WITHOUT using Auth::login()
        $authKey = Auth::guard('web')->getName();  // Gets correct key: login_web_59ba36addc2b2f9401580f014c7f58ea4e30989d
        session()->put($authKey, $user->getAuthIdentifier());
        
        // Force session save
        session()->save();
        
        // Update sessions table manually
        \DB::table('sessions')
            ->where('id', $sessionId)
            ->update([
                'user_id' => $user->id,
                'last_activity' => time()
            ]);

        // Set the user in Auth facade manually and reload
        Auth::guard('web')->setUser($user);
        
        // Force Auth to recognize the user
        Auth::shouldUse('web');
        
        $sessionIdAfter = session()->getId();

        Log::info('Manual authentication completed', [
            'auth_check' => Auth::check(),
            'auth_user_exists' => Auth::user() !== null,
            'auth_id' => Auth::user() ? Auth::user()->username : 'null',
            'auth_user_id' => Auth::user() ? Auth::user()->id : 'null',
            'session_id_before' => $sessionId,
            'session_id_after' => $sessionIdAfter,
            'session_changed' => $sessionId !== $sessionIdAfter,
            'session_auth_key' => $authKey,
            'session_has_auth_key' => session()->has($authKey),
            'session_auth_value' => session()->get($authKey)
        ]);

        // Redirect based on user role
        if ($user->isAdmin()) {
            Log::info('Redirecting admin to admin dashboard');
            return redirect()->intended('/admin/dashboard');
        } else {
            Log::info('Redirecting user to user dashboard');
            return redirect()->intended('/dashboard');
        }
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