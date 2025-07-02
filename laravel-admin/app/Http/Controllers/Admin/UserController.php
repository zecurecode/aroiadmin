<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Site;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Display a listing of users.
     */
    public function index()
    {
        $users = User::with('site')->paginate(15);
        return view('admin.users.index', compact('users'));
    }

    /**
     * Show the form for creating a new user.
     */
    public function create()
    {
        $sites = Site::where('active', true)->get();
        return view('admin.users.create', compact('sites'));
    }

    /**
     * Store a newly created user in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'username' => 'required|string|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
            'role' => 'required|in:user,admin',
            'siteid' => 'required_if:role,user|integer',
            'license' => 'nullable|integer',
        ]);

        // Set default values for admin
        if ($validated['role'] === 'admin') {
            $validated['siteid'] = 0;
            $validated['license'] = 9999;
        } elseif (empty($validated['license'])) {
            // Auto-set license based on siteid from database
            $site = Site::findBySiteId($validated['siteid']);
            $validated['license'] = $site ? $site->license : 0;
        }

        $validated['password'] = Hash::make($validated['password']);

        User::create($validated);

        return redirect()->route('admin.users.index')
            ->with('success', 'Bruker opprettet.');
    }

    /**
     * Display the specified user.
     */
    public function show(User $user)
    {
        $user->load('site');
        return view('admin.users.show', compact('user'));
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit(User $user)
    {
        $sites = Site::where('active', true)->get();
        return view('admin.users.edit', compact('user', 'sites'));
    }

    /**
     * Update the specified user in storage.
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'username' => ['required', 'string', 'max:255', Rule::unique('users')->ignore($user->id)],
            'role' => 'required|in:user,admin',
            'siteid' => 'required_if:role,user|integer',
            'license' => 'nullable|integer',
        ]);

        // Only update password if provided
        if ($request->filled('password')) {
            $request->validate(['password' => 'string|min:6|confirmed']);
            $validated['password'] = Hash::make($request->password);
        }

        // Set default values for admin
        if ($validated['role'] === 'admin') {
            $validated['siteid'] = 0;
            $validated['license'] = 9999;
        } elseif (empty($validated['license']) && $validated['role'] === 'user') {
            // Auto-set license based on siteid from database
            $site = Site::findBySiteId($validated['siteid']);
            $validated['license'] = $site ? $site->license : $user->license;
        }

        $user->update($validated);

        return redirect()->route('admin.users.index')
            ->with('success', 'Bruker oppdatert.');
    }

    /**
     * Remove the specified user from storage.
     */
    public function destroy(User $user)
    {
        // Prevent deleting current user
        if ($user->id === auth()->id()) {
            return redirect()->route('admin.users.index')
                ->with('error', 'Du kan ikke slette din egen bruker.');
        }

        // Prevent deleting the last admin
        $adminCount = User::where('role', 'admin')->count();
        if ($adminCount == 0) {
            // Count by username if role field not set
            $adminCount = User::where('username', 'admin')->count();
        }

        if (($user->role === 'admin' || $user->username === 'admin') && $adminCount <= 1) {
            return redirect()->route('admin.users.index')
                ->with('error', 'Kan ikke slette siste admin bruker.');
        }

        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', 'Bruker slettet.');
    }

    /**
     * Impersonate a user.
     */
    public function impersonate(User $user)
    {
        // Store original user id
        Session::put('impersonate.original_id', auth()->id());
        Session::put('impersonate.original_username', auth()->user()->username);

        // Login as the target user
        Auth::login($user);

        // Set session variables to match old PHP system
        Session::put('loggedin', true);
        Session::put('id', $user->id);
        Session::put('username', $user->username);
        Session::put('siteid', $user->siteid);
        Session::put('is_admin', $user->isAdmin());

        return redirect()->route('admin.dashboard')
            ->with('success', 'Du er nå logget inn som ' . $user->username);
    }

    /**
     * Stop impersonating.
     */
    public function stopImpersonate()
    {
        $originalId = Session::get('impersonate.original_id');

        // Security check: Only allow if currently impersonating
        if (!$originalId) {
            return redirect()->route('admin.dashboard')
                ->with('error', 'Du impersonerer ikke en bruker for øyeblikket.');
        }

        $originalUser = User::find($originalId);
        if ($originalUser) {
            Auth::login($originalUser);

            // Restore session variables
            Session::put('loggedin', true);
            Session::put('id', $originalUser->id);
            Session::put('username', $originalUser->username);
            Session::put('siteid', $originalUser->siteid);
            Session::put('is_admin', $originalUser->isAdmin());
        }

        // Clean up impersonation session data
        Session::forget('impersonate.original_id');
        Session::forget('impersonate.original_username');

        return redirect()->route('admin.dashboard')
            ->with('success', 'Tilbake til din egen bruker.');
    }
}
