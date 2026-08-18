<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The Content-Security-Policy that keeps third-party ads and trackers off the
 * site — the browser refuses any origin the policy does not name.
 */
class ContentSecurityPolicyTest extends TestCase
{
    use RefreshDatabase;

    private function policy(string $url = '/'): string
    {
        return (string) $this->get($url)->assertOk()->headers->get('Content-Security-Policy');
    }

    /** @return array<int, string> the sources allowed for one directive */
    private function sources(string $directive, string $url = '/'): array
    {
        foreach (explode('; ', $this->policy($url)) as $part) {
            if (str_starts_with($part, $directive . ' ')) {
                return explode(' ', substr($part, strlen($directive) + 1));
            }
        }

        return [];
    }

    public function test_html_responses_carry_the_policy(): void
    {
        $this->assertNotSame('', $this->policy());
        $this->assertNotSame('', $this->policy(route('frontend.events')));
    }

    public function test_ad_networks_are_not_allowed_anywhere_in_the_policy(): void
    {
        $policy = $this->policy();

        // The whole point: an ad cannot be fetched, framed or beaconed.
        foreach (['googlesyndication', 'doubleclick', 'adservice', 'adnxs', 'taboola', 'outbrain'] as $network) {
            $this->assertStringNotContainsString($network, $policy, "{$network} should not be an allowed origin.");
        }
    }

    public function test_only_the_named_third_parties_may_be_framed(): void
    {
        $frames = $this->sources('frame-src');

        $this->assertContains("'self'", $frames);
        $this->assertContains('https://www.google.com', $frames);      // the contact page map
        $this->assertContains('https://www.instagram.com', $frames);   // reels added as a link

        // Nothing beyond the maps and Instagram. This list is the thing standing
        // between the site and an injected iframe, so it is asserted whole
        // rather than only checked for the entries we expect to be there.
        $this->assertEqualsCanonicalizing([
            "'self'",
            'https://www.google.com',
            'https://maps.google.com',
            'https://www.instagram.com',
            'https://instagram.com',
        ], $frames);

        // A wildcard here would let any ad network drop an iframe in.
        $this->assertNotContains('*', $frames);
        $this->assertNotContains('https:', $frames);
    }

    public function test_the_dangerous_directives_are_locked_down(): void
    {
        $policy = $this->policy();

        $this->assertStringContainsString("default-src 'self'", $policy);
        $this->assertStringContainsString("object-src 'none'", $policy);
        $this->assertStringContainsString("base-uri 'self'", $policy);
        $this->assertStringContainsString("form-action 'self'", $policy);
        $this->assertStringContainsString("frame-ancestors 'self'", $policy);
    }

    public function test_the_cdns_the_site_actually_uses_are_allowed(): void
    {
        // Miss one of these and the page silently loses Bootstrap, Swiper,
        // Font Awesome or its webfonts.
        $scripts = $this->sources('script-src');
        $this->assertContains('https://cdn.jsdelivr.net', $scripts);   // Bootstrap + Swiper

        $styles = $this->sources('style-src');
        $this->assertContains('https://cdnjs.cloudflare.com', $styles); // Font Awesome
        $this->assertContains('https://fonts.googleapis.com', $styles);

        $fonts = $this->sources('font-src');
        $this->assertContains('https://fonts.gstatic.com', $fonts);
    }

    public function test_self_hosted_video_and_upload_previews_still_work(): void
    {
        // The reels are served from this host; the admin's live image previews
        // are blob: URLs from URL.createObjectURL.
        $this->assertStringContainsString("media-src 'self' blob:", $this->policy());
        $this->assertContains('blob:', $this->sources('img-src'));
        $this->assertContains('data:', $this->sources('img-src'));
    }

    public function test_it_can_be_switched_to_report_only(): void
    {
        // After adding a new third party, report-only shows what it needs
        // without breaking the page for visitors.
        config(['csp.report_only' => true]);

        $response = $this->get('/')->assertOk();

        $this->assertNotNull($response->headers->get('Content-Security-Policy-Report-Only'));
        $this->assertNull($response->headers->get('Content-Security-Policy'));
    }

    public function test_it_can_be_switched_off_entirely(): void
    {
        config(['csp.enabled' => false]);

        $this->assertNull($this->get('/')->assertOk()->headers->get('Content-Security-Policy'));
    }

    public function test_non_html_downloads_are_left_alone(): void
    {
        $admin = \App\Models\User::where('is_super_admin', true)->firstOrFail();

        $response = $this->withSession(['admin_logged_in' => true, 'admin_id' => $admin->id])
            ->get(route('backend.course-enquiries.export'));

        // A streamed CSV gains nothing from a policy header.
        $this->assertNull($response->headers->get('Content-Security-Policy'));
    }
}
