<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sends a Content-Security-Policy with every HTML response.
 *
 * The point is to stop third-party code the site never asked for — ad networks,
 * trackers, an iframe injected through a compromised script — from loading at
 * all. The browser refuses any origin not named in config/csp.php.
 *
 * 'unsafe-inline' is present for scripts and styles, and that is a real
 * weakening: the codebase carries a lot of inline <script> blocks and style=""
 * attributes, and nonce-ing every one of them is a much larger change. What the
 * policy still buys is the part that matters here — an ad cannot be loaded from
 * somebody else's domain, and nothing can be framed except the branch maps.
 */
class ContentSecurityPolicy
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! config('csp.enabled', true) || ! $this->isHtml($response)) {
            return $response;
        }

        $header = config('csp.report_only', false)
            ? 'Content-Security-Policy-Report-Only'
            : 'Content-Security-Policy';

        // Never overwrite a policy something else already set.
        if (! $response->headers->has($header)) {
            $response->headers->set($header, $this->policy());
        }

        return $response;
    }

    /** The assembled policy, one directive per line for readability. */
    private function policy(): string
    {
        $cdn       = config('csp.origins.cdn', []);
        $fonts     = config('csp.origins.fonts', []);
        $analytics = config('csp.origins.analytics', []);
        $frames    = config('csp.origins.frames', []);

        $directives = [
            "default-src 'self'",

            // 'unsafe-inline' is required by the inline blocks throughout the
            // views; the origin list is what keeps foreign scripts out.
            'script-src ' . $this->join(["'self'", "'unsafe-inline'"], $cdn, $analytics),

            'style-src ' . $this->join(["'self'", "'unsafe-inline'"], $cdn, $fonts),

            'font-src ' . $this->join(["'self'", 'data:'], $cdn, $fonts),

            // data: for the inline SVG carets in CSS, blob: for the admin's
            // live upload previews (URL.createObjectURL).
            'img-src ' . $this->join(["'self'", 'data:', 'blob:'], $analytics),

            // Reels and any other clip are served from this host.
            "media-src 'self' blob:",

            'connect-src ' . $this->join(["'self'"], $analytics),

            // The branch maps, and nothing else. An ad network's iframe is
            // refused here.
            'frame-src ' . $this->join(["'self'"], $frames),

            // Flash-era plugin content: no reason to ever allow it.
            "object-src 'none'",

            // Stops an injected <base> retargeting every relative URL, and
            // stops forms being posted off-site.
            "base-uri 'self'",
            "form-action 'self'",

            // Only this site may frame these pages (clickjacking).
            "frame-ancestors 'self'",
        ];

        return implode('; ', $directives);
    }

    /** Flatten the given source lists into one space-separated directive value. */
    private function join(array ...$lists): string
    {
        return implode(' ', array_unique(array_merge(...$lists)));
    }

    /**
     * Only HTML gets a policy. Applying it to a streamed CSV or an .xlsx
     * download achieves nothing and only bloats the response.
     */
    private function isHtml(Response $response): bool
    {
        return str_contains((string) $response->headers->get('Content-Type'), 'text/html');
    }
}
