<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class CustomAuthMiddleware
{
    /**
     * Handle an incoming request.
     * Check both custom session data (like old PHP system) and Laravel auth
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $sessionData = [
            'loggedin' => session()->get('loggedin'),
            'id' => session()->get('id'),
            'username' => session()->get('username'),
            'siteid' => session()->get('siteid'),
            'is_admin' => session()->get('is_admin'),
            'laravel_auth_check' => auth()->check(),
            'laravel_user_id' => auth()->user() ? auth()->user()->id : null,
            'laravel_username' => auth()->user() ? auth()->user()->username : null,
        ];

        Log::info('CustomAuthMiddleware::handle', [
            'request_url' => $request->url(),
            'session_data' => $sessionData,
            'session_id' => session()->getId(),
            'is_ajax' => $request->ajax(),
            'has_session_cookie' => $request->hasCookie(config('session.cookie'))
        ]);

        // Check our custom session first (like the old PHP system)
        $customSessionValid = session()->get('loggedin') === true &&
                              session()->get('id') &&
                              session()->get('username');

        // Check Laravel auth as backup
        $laravelAuthValid = auth()->check();

        Log::info('CustomAuthMiddleware authentication checks', [
            'custom_session_valid' => $customSessionValid,
            'laravel_auth_valid' => $laravelAuthValid
        ]);

        if (!$customSessionValid && !$laravelAuthValid) {
            Log::warning('CustomAuthMiddleware: User not authenticated, redirecting to login', [
                'attempted_url' => $request->url(),
                'session_id' => session()->getId(),
                'is_ajax' => $request->ajax()
            ]);

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['error' => 'Unauthenticated.'], 401);
            }

            return redirect()->route('login');
        }

        // If custom session is valid but Laravel auth is not, set up Laravel auth WITHOUT regenerating session
        if ($customSessionValid && !$laravelAuthValid) {
            $username = session()->get('username');
            $customUserId = session()->get('id');

            Log::info('CustomAuthMiddleware: Setting up Laravel auth from custom session', [
                'username' => $username,
                'custom_user_id' => $customUserId
            ]);

            // Find or create the Laravel User model
            $user = User::where('username', $username)->first();

            if (!$user) {
                Log::info('User not found in Laravel, creating from old PHP auth', ['username' => $username]);
                $user = User::createFromOldPhpAuth($username);
            }

            // CRITICAL FIX: Use setUser() instead of login() to avoid session regeneration
            // This prevents the session ID from changing on every request
            Auth::guard('web')->setUser($user);

            Log::info('CustomAuthMiddleware: Laravel auth established', [
                'laravel_user_id' => $user->id,
                'username' => $user->username,
                'siteid' => $user->siteid,
                'is_admin' => $user->isAdmin()
            ]);
        }

        Log::info('CustomAuthMiddleware: User authenticated', [
            'username' => session()->get('username') ?: (auth()->user() ? auth()->user()->username : 'unknown'),
            'user_id' => session()->get('id') ?: (auth()->user() ? auth()->user()->id : 'unknown'),
            'auth_method' => $customSessionValid ? 'custom_session' : 'laravel_auth',
            'laravel_auth_now_valid' => auth()->check()
        ]);

        // CRITICAL FIX: Remove manual session()->save() - Laravel handles this automatically
        // Manual save can interfere with Laravel's session lifecycle and cause race conditions

        return $next($request);
    }
}
