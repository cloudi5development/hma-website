<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuthController extends Controller
{
    /**
     * TEMPORARY credentials. These live here only until the real authentication
     * module (users table + hashed passwords + roles) is built in Phase 0/1.
     */
    private const TEMP_USERNAME = 'admin@gmail.com';
    private const TEMP_PASSWORD = '12345678';

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
     * Verify the submitted credentials and open an admin session.
     *
     * Required-field errors surface as Bootstrap field validation; a wrong
     * username/password surfaces as a single "invalid credentials" banner, so we
     * never reveal which of the two was wrong.
     */
    public function authenticate(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $ok = hash_equals(self::TEMP_USERNAME, $data['username'])
            && hash_equals(self::TEMP_PASSWORD, $data['password']);

        if (! $ok) {
            return back()
                ->withInput($request->only('username'))
                ->with('login_error', 'Invalid username or password.');
        }

        // Fresh session ID on privilege change — standard fixation defence.
        $request->session()->regenerate();
        $request->session()->put('admin_logged_in', true);
        $request->session()->put('admin_email', $data['username']);

        return redirect()->intended(route('backend.dashboard'));
    }

    /**
     * End the admin session.
     */
    public function logout(Request $request): RedirectResponse
    {
        $request->session()->forget(['admin_logged_in', 'admin_email']);
        $request->session()->regenerate();

        return redirect()->route('backend.auth.login');
    }
}
