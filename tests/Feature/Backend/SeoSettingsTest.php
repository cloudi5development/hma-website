<?php

namespace Tests\Feature\Backend;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

class SeoSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_seo_defaults_can_be_updated_and_rendered(): void
    {
        $response = $this->withSession(['admin_logged_in' => true])
            ->put(route('backend.settings.seo.update'), [
                'seo_meta_title' => 'New SEO title',
                'seo_meta_description' => 'New SEO description',
                'seo_meta_keywords' => 'academy, training',
                'seo_meta_robots' => 'noindex, nofollow',
                'seo_google_site_verification' => 'google-verification-code',
                'seo_bing_site_verification' => 'bing-verification-code',
                'seo_google_analytics_id' => 'G-TEST123',
                'seo_google_tag_manager_id' => 'GTM-TEST123',
            ]);

        $response->assertRedirect();

        $this->assertSame('New SEO title', Setting::get('seo_meta_title'));
        $this->assertSame('New SEO description', Setting::get('seo_meta_description'));
        $this->assertSame('academy, training', Setting::get('seo_meta_keywords'));
        $this->assertSame('noindex, nofollow', Setting::get('seo_meta_robots'));
        $this->assertSame('google-verification-code', Setting::get('seo_google_site_verification'));
        $this->assertSame('bing-verification-code', Setting::get('seo_bing_site_verification'));
        $this->assertSame('G-TEST123', Setting::get('seo_google_analytics_id'));
        $this->assertSame('GTM-TEST123', Setting::get('seo_google_tag_manager_id'));

        $html = View::make('frontend.layouts.meta-tags')->render();

        $this->assertStringContainsString('name="google-site-verification"', $html);
        $this->assertStringContainsString('name="msvalidate.01"', $html);
        $this->assertStringContainsString('https://www.googletagmanager.com/gtag/js', $html);
        $this->assertStringContainsString('G-TEST123', $html);
        $this->assertStringContainsString('GTM-TEST123', $html);
    }
}
