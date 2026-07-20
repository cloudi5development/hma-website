<?php

namespace App\Http\Middleware;

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
        if (! $request->session()->get('admin_logged_in')) {
            return redirect()->route('backend.auth.login');
        }

        return $next($request);
    }
}
