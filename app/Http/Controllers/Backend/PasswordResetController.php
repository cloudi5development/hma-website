<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\View\View;

/**
 * "Forgot password" for the admin panel.
 *
 * Built on Laravel's password broker rather than a hand-rolled token, so the
 * hashing, the 60-minute expiry and the per-address throttle in config/auth.php
 * all come for free. The e-mail goes out through whatever SMTP the admin
 * configured in Settings → Email (see MailConfigServiceProvider).
 *
 * Every credential passed to the broker also carries is_active, so a deactivated
 * account cannot be used to request or complete a reset.
 */
class PasswordResetController extends Controller
{
    /** The form that asks for an e-mail address. */
    public function request(): View
    {
        return view('backend.auth.forgot-password');
    }

    /** Mail a reset link. */
    public function email(Request $request): RedirectResponse
    {
        $request->validate(
            ['email' => ['required', 'email', 'max:180']],
            ['email.required' => 'Enter the email address of your account.']
        );

        $status = Password::sendResetLink([
            'email'     => $request->input('email'),
            'is_active' => true,
        ]);

        // Deliberately the same message either way: telling a visitor that an
        // address is not registered would let anyone enumerate admin accounts.
        if ($status === Password::RESET_LINK_SENT || $status === Password::INVALID_USER) {
            return back()->with('status_message', 'If that email belongs to an admin account, a reset link is on its way. Check the inbox and the spam folder.');
        }

        if ($status === Password::RESET_THROTTLED) {
            return back()->withInput()->with('login_error', 'A reset link was requested recently. Please wait a minute and try again.');
        }

        return back()->withInput()->with('login_error', 'Could not send the reset email. Check Settings → Email / SMTP.');
    }

    /** The form behind the link in the e-mail. */
    public function reset(Request $request, string $token): View
    {
        return view('backend.auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email', ''),
        ]);
    }

    /** Set the new password. */
    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'token'    => ['required', 'string'],
            'email'    => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
        ], [
            'password.confirmed' => 'The two passwords do not match.',
            'password.min'       => 'Use at least 8 characters.',
        ]);

        $status = Password::reset(
            [
                'email'                 => $request->input('email'),
                'password'              => $request->input('password'),
                'password_confirmation' => $request->input('password_confirmation'),
                'token'                 => $request->input('token'),
                'is_active'             => true,
            ],
            function (User $user, string $password) {
                // 'password' is a hashed cast on the model, so assign it raw.
                $user->forceFill([
                    'password'       => $password,
                    // Invalidate any "remember me" cookie issued before the reset.
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('backend.auth.login')
                ->with('status_message', 'Password updated. You can sign in with it now.');
        }

        return back()->withInput($request->only('email'))->with(
            'login_error',
            $status === Password::INVALID_TOKEN
                ? 'That reset link has expired or has already been used. Request a new one.'
                : 'Could not reset the password. Request a new link and try again.'
        );
    }
}
