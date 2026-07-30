<?php

namespace App\Http\Middleware;

use App\Http\Controllers\Backend\AuthController;
use App\Support\AdminAuth;
use App\Support\AdminRemember;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guards the admin area. Until the real authentication module lands, "logged in"
 * is a session flag set by Backend\AuthController::authenticate(). Any request
 * without it is bounced to the login screen.
 */
class AdminAuthenticate
{
    public function handle(Request $request, Closure $next): Response
    {
        // Start every request with a clean resolve of the signed-in admin, so a
        // permission change takes effect on their next page load.
        AdminAuth::forget();

        if (! $request->session()->get('admin_logged_in')) {
            // No session — but a valid "remember me" cookie reopens one, which is
            // the whole point of the checkbox on the login form.
            if ($user = AdminRemember::resolve($request)) {
                AuthController::openSession($request, $user);
                AdminRemember::issue($user);   // roll the token forward on each use
                AdminAuth::forget();

                return $next($request);
            }

            return redirect()->route('backend.auth.login');
        }

        return $next($request);
    }
}
