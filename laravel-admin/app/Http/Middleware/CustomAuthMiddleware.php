<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

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
            'session_data' => $sessionData
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
                'attempted_url' => $request->url()
            ]);
            return redirect()->route('login');
        }

        Log::info('CustomAuthMiddleware: User authenticated', [
            'username' => session()->get('username') ?: (auth()->user() ? auth()->user()->username : 'unknown'),
            'user_id' => session()->get('id') ?: (auth()->user() ? auth()->user()->id : 'unknown'),
            'auth_method' => $customSessionValid ? 'custom_session' : 'laravel_auth'
        ]);

        return $next($request);
    }
}
