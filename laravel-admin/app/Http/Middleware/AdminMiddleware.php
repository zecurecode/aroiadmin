<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
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
            'laravel_is_admin' => auth()->user() ? auth()->user()->isAdmin() : null,
        ];

        Log::info('AdminMiddleware::handle', [
            'request_url' => $request->url(),
            'session_data' => $sessionData,
        ]);

        // Check our custom session first (like the old PHP system)
        $customSessionValid = session()->get('loggedin') === true &&
                              session()->get('id') &&
                              session()->get('username');

        // Check Laravel auth as backup
        $laravelAuthValid = auth()->check();

        Log::info('AdminMiddleware authentication checks', [
            'custom_session_valid' => $customSessionValid,
            'laravel_auth_valid' => $laravelAuthValid,
            'session_is_admin' => session()->get('is_admin'),
            'laravel_is_admin' => auth()->user() ? auth()->user()->isAdmin() : false,
        ]);

        if (! $customSessionValid && ! $laravelAuthValid) {
            Log::warning('AdminMiddleware: User not authenticated, redirecting to login');

            return redirect()->route('login');
        }

        // Check admin privileges
        $isAdmin = false;

        if ($customSessionValid) {
            $isAdmin = session()->get('is_admin') === true;
            Log::info('AdminMiddleware: Using custom session admin check', ['is_admin' => $isAdmin]);
        } elseif ($laravelAuthValid) {
            $isAdmin = auth()->user()->isAdmin();
            Log::info('AdminMiddleware: Using Laravel auth admin check', ['is_admin' => $isAdmin]);
        }

        if (! $isAdmin) {
            Log::warning('AdminMiddleware: User lacks admin privileges', [
                'username' => session()->get('username') ?: (auth()->user() ? auth()->user()->username : 'unknown'),
                'is_admin' => $isAdmin,
            ]);
            abort(403, 'Access denied. Admin privileges required.');
        }

        Log::info('AdminMiddleware: Admin access granted', [
            'username' => session()->get('username') ?: (auth()->user() ? auth()->user()->username : 'unknown'),
        ]);

        return $next($request);
    }
}
