<?php

namespace App\Http\Middleware;

use App\Support\ContentExpiry;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Runs the day's expiry sweep on the first web request of the day, before any
 * page queries events or schedules. A failure is reported, never shown: an
 * expired event lingering until the next request beats a broken page.
 */
class ExpirePastContent
{
    public function handle(Request $request, Closure $next): Response
    {
        try {
            ContentExpiry::runOncePerDay();
        } catch (\Throwable $e) {
            report($e);
        }

        return $next($request);
    }
}
