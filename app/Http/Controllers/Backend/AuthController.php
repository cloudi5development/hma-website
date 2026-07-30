<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\AdminAuth;
use App\Support\AdminRemember;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AuthController extends Controller
{
    /** Compared against when the email is unknown, to keep both branches' timing similar. */
    private const DUMMY_HASH = '$2y$12$usesomesillystringfoeueiwerweasdfghjklzxcvbnmqwertyuiopas';

    /**
     * Show the admin login screen. Already-authenticated admins skip it.
     */
    public function login(): View|RedirectResponse
    {
        if (session('admin_logged_in')) {
            return redirect()->route('backend.dashboard');
        }

        return view('backend.auth.login');
    }

    /**
     * Verify the submitted credentials against the users table and open an
     * admin session.
     *
     * Required-field errors surface as field validation; a wrong email/password
     * — or a deactivated account — surfaces as one banner, so we never reveal
     * which of the two was wrong, nor whether the address exists.
     */
    public function authenticate(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $data['username'])->first();

        $valid = Hash::check($data['password'], $user->password ?? self::DUMMY_HASH);

        if (! $user || ! $valid || ! $user->is_active) {
            return back()
                ->withInput($request->only('username'))
                ->with('login_error', 'Invalid username or password.');
        }

        $this->openSession($request, $user);

        // "Remember me" — a 30-day cookie that reopens the session after the
        // session cookie itself has expired. Unticked, the login lasts exactly as
        // long as the browser session, as before.
        if ($request->boolean('remember')) {
            AdminRemember::issue($user);
        } else {
            AdminRemember::forget($user);
        }

        return redirect()->intended(route('backend.dashboard'));
    }

    /**
     * Put an authenticated admin into the session. Shared by the login form and by
     * AdminAuthenticate when it restores a session from a remember cookie.
     */
    public static function openSession(Request $request, User $user): void
    {
        $user->forceFill(['last_login_at' => now()])->save();

        // Fresh session ID on privilege change — standard fixation defence.
        $request->session()->regenerate();
        $request->session()->put('admin_logged_in', true);
        $request->session()->put('admin_id', $user->id);
        $request->session()->put('admin_name', $user->name);
        $request->session()->put('admin_email', $user->email);
    }

    /**
     * End the admin session. Also rotates the remember token, so signing out here
     * invalidates any remember cookie this account has on other devices.
     */
    public function logout(Request $request): RedirectResponse
    {
        AdminRemember::forget(AdminAuth::user());

        $request->session()->forget(['admin_logged_in', 'admin_id', 'admin_name', 'admin_email']);
        $request->session()->regenerate();

        return redirect()->route('backend.auth.login');
    }
}
