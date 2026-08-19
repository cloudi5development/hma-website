<?php

namespace Tests\Feature\Backend;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Settings → Social Media offers exactly the platforms in
 * `Setting::SOCIAL_PLATFORMS`, and the footer icons come from the same list.
 *
 * X/Twitter was dropped from that list on 2026-08-19. Because one constant
 * drives the admin form, the validation and the footer, a platform can be
 * removed in one place — and this is what proves the three stayed in step.
 */
class SocialLinkSettingsTest extends TestCase
{
    use RefreshDatabase;

    private function signedIn(): self
    {
        $admin = User::where('is_super_admin', true)->firstOrFail();

        return $this->withSession([
            'admin_logged_in' => true,
            'admin_id'        => $admin->id,
            'admin_name'      => $admin->name,
            'admin_email'     => $admin->email,
        ]);
    }

    public function test_x_is_not_offered_as_a_platform(): void
    {
        $this->assertArrayNotHasKey('social_x', Setting::SOCIAL_PLATFORMS);
    }

    public function test_the_form_renders_a_field_for_every_platform_and_no_others(): void
    {
        $html = $this->signedIn()
            ->get(route('backend.settings.social'))
            ->assertOk()
            ->getContent();

        foreach (array_keys(Setting::SOCIAL_PLATFORMS) as $key) {
            $this->assertStringContainsString('name="' . $key . '"', $html);
        }

        $this->assertStringNotContainsString('name="social_x"', $html);
    }

    /**
     * A leftover `social_x` row from before the platform was dropped must stay
     * inert — not resurface as a footer icon.
     */
    public function test_a_leftover_x_value_is_never_rendered(): void
    {
        Setting::putMany(['social_x' => 'https://x.com/hiremindsacademy']);

        $this->assertNotContains(
            'social_x',
            array_column(Setting::socialLinks(), 'key'),
        );

        $this->get(route('frontend.index'))
            ->assertOk()
            ->assertDontSee('x.com/hiremindsacademy', false);
    }

    public function test_the_remaining_platforms_still_save(): void
    {
        $this->signedIn()
            ->put(route('backend.settings.social.update'), [
                'social_facebook'  => 'https://facebook.com/hiremindsacademy',
                'social_instagram' => 'https://instagram.com/hireminds_academy',
                'social_whatsapp'  => '917824094044',
            ])
            ->assertSessionHasNoErrors();

        $keys = array_column(Setting::socialLinks(), 'key');

        $this->assertContains('social_facebook', $keys);
        $this->assertContains('social_whatsapp', $keys);
    }
}
