<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Forces the "Accept: application/json" header so a client that forgets it
 * still gets JSON (and JSON-rendered errors) back. Apply via the `force.json`
 * alias on any route/group that must always answer in JSON.
 */
class ForceJsonResponse
{
    public function handle(Request $request, Closure $next): Response
    {
        $request->headers->set('Accept', 'application/json');

        return $next($request);
    }
}
