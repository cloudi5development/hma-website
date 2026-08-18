<?php

namespace Tests\Feature\Frontend;

use App\Models\Reel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/**
 * Reels added by pasting an Instagram link rather than uploading a clip.
 *
 * The two kinds sit side by side in the same slider and behave differently:
 * an upload is served from this host and autoplays muted, while an Instagram
 * link is framed and is click-to-play, which is Instagram's rule and not
 * something the site can override. These assert that split holds — and that the
 * link a visitor's browser is asked to frame can only ever be instagram.com.
 */
class ReelEmbedTest extends TestCase
{
    use RefreshDatabase;

    private function reel(array $overrides = []): Reel
    {
        return Reel::create($overrides + [
            'title'      => 'Reel ' . (Reel::count() + 1),
            'sort_order' => Reel::count(),
            'is_active'  => true,
        ]);
    }

    private function signedInAsAdmin(): self
    {
        $admin = User::where('is_super_admin', true)->firstOrFail();

        return $this->withSession([
            'admin_logged_in' => true,
            'admin_id'        => $admin->id,
            'admin_name'      => $admin->name,
            'admin_email'     => $admin->email,
        ]);
    }

    // ------------------------------------------------------- link recognition

    public function test_every_instagram_url_shape_yields_the_same_embed(): void
    {
        $code = 'C8xYzAbCdEf';

        foreach ([
            "https://www.instagram.com/reel/{$code}/",
            "https://www.instagram.com/reel/{$code}",
            "https://instagram.com/reels/{$code}/",
            "https://www.instagram.com/p/{$code}/",
            "https://www.instagram.com/tv/{$code}/",
            "https://www.instagram.com/reel/{$code}/?igsh=abc123&utm_source=ig_web",
        ] as $url) {
            $reel = new Reel(['instagram_url' => $url]);

            $this->assertSame($code, $reel->instagramCode(), "failed on {$url}");
            $this->assertSame(
                "https://www.instagram.com/reel/{$code}/embed/",
                $reel->embed_url,
                "failed on {$url}",
            );
        }
    }

    /**
     * Only instagram.com may be framed. Without the host check, whatever an
     * admin pasted would be handed straight to an iframe src.
     */
    public function test_a_link_to_any_other_host_is_never_embedded(): void
    {
        foreach ([
            'https://evil.example.com/reel/abc/',
            'https://instagram.com.evil.example/reel/abc/',
            'https://notinstagram.com/reel/abc/',
            'javascript:alert(1)',
            'https://www.instagram.com/hireminds_academy/',   // a profile, not a reel
            'https://www.youtube.com/watch?v=abc',
        ] as $url) {
            $reel = new Reel(['instagram_url' => $url]);

            $this->assertNull($reel->instagramCode(), "should not have parsed {$url}");
            $this->assertNull($reel->embed_url, "should not have embedded {$url}");
        }
    }

    public function test_an_upload_always_wins_over_a_link(): void
    {
        $reel = $this->reel([
            'video'         => 'assets/videos/reel.mp4',
            'instagram_url' => 'https://www.instagram.com/reel/C8xYzAbCdEf/',
        ]);

        // The link is still useful — it becomes the card's badge — but the
        // uploaded clip is what plays, because it can autoplay and it is ours.
        $this->assertFalse($reel->usesEmbed());
        $this->assertNotNull($reel->video_url);
    }

    public function test_a_link_only_reel_uses_the_embed(): void
    {
        $reel = $this->reel(['instagram_url' => 'https://www.instagram.com/reel/C8xYzAbCdEf/']);

        $this->assertTrue($reel->usesEmbed());
        $this->assertNull($reel->video_url);
    }

    // ------------------------------------------------------------- the slider

    public function test_a_link_only_reel_renders_as_a_lazy_iframe(): void
    {
        $this->reel(['instagram_url' => 'https://www.instagram.com/reel/C8xYzAbCdEf/']);

        $html = $this->get(route('frontend.index'))->assertOk()->getContent();

        $this->assertStringContainsString('hm-reel--embed', $html);
        $this->assertStringContainsString(
            'data-src="https://www.instagram.com/reel/C8xYzAbCdEf/embed/"',
            $html,
        );

        // Withheld until the section scrolls into view, like the uploaded clips.
        $this->assertStringNotContainsString('<iframe class="hm-reel__embed" src=', $html);
    }

    /**
     * The card crops Instagram's header and footer away, and their "View more on
     * Instagram" link goes with the footer — so the badge has to put a way
     * through to the post back on the card.
     */
    public function test_an_embedded_card_still_links_out_to_instagram(): void
    {
        $this->reel(['instagram_url' => 'https://www.instagram.com/reel/C8xYzAbCdEf/']);

        $html = $this->get(route('frontend.index'))->assertOk()->getContent();

        $this->assertMatchesRegularExpression(
            '/hm-reel--embed.*?hm-reel__badge[^>]*href="https:\/\/www\.instagram\.com\/reel\/C8xYzAbCdEf\/"/s',
            $html,
        );
    }

    public function test_uploaded_and_linked_reels_share_the_slider(): void
    {
        $this->reel(['video' => 'assets/videos/one.mp4']);
        $this->reel(['instagram_url' => 'https://www.instagram.com/reel/C8xYzAbCdEf/']);

        $html = $this->get(route('frontend.index'))->assertOk()->getContent();

        $this->assertStringContainsString('hm-reel__video', $html);
        $this->assertStringContainsString('hm-reel__embed', $html);
    }

    /** An unusable link must not leave an empty card on the site. */
    public function test_a_reel_with_neither_source_is_not_rendered_as_an_embed(): void
    {
        $this->reel(['instagram_url' => 'https://www.instagram.com/hireminds_academy/']);

        $html = $this->get(route('frontend.index'))->assertOk()->getContent();

        $this->assertStringNotContainsString('hm-reel--embed', $html);
    }

    public function test_the_policy_allows_instagram_to_be_framed(): void
    {
        $policy = $this->get(route('frontend.index'))
            ->assertOk()
            ->headers->get('Content-Security-Policy');

        preg_match('/frame-src ([^;]+)/', $policy, $m);

        $this->assertStringContainsString('https://www.instagram.com', $m[1]);

        // ...and only framing was opened up. Instagram's scripts and media are
        // still refused; everything the embed needs loads inside its iframe.
        preg_match('/script-src ([^;]+)/', $policy, $s);
        $this->assertStringNotContainsString('instagram', $s[1]);
    }

    // --------------------------------------------------------------- the form

    public function test_a_reel_can_be_created_from_a_link_alone(): void
    {
        $this->signedInAsAdmin()
            ->post(route('backend.reels.store'), [
                'title'         => 'Added by link',
                'instagram_url' => 'https://www.instagram.com/reel/C8xYzAbCdEf/',
                'is_active'     => 1,
                'sort_order'    => 0,
            ])
            ->assertRedirect(route('backend.reels.index'))
            ->assertSessionHasNoErrors();

        $reel = Reel::where('title', 'Added by link')->firstOrFail();

        $this->assertNull($reel->video);
        $this->assertTrue($reel->usesEmbed());
    }

    public function test_a_reel_still_accepts_an_upload_with_no_link(): void
    {
        $this->signedInAsAdmin()
            ->post(route('backend.reels.store'), [
                'title'      => 'Added by upload',
                'video'      => UploadedFile::fake()->create('clip.mp4', 128, 'video/mp4'),
                'is_active'  => 1,
                'sort_order' => 0,
            ])
            ->assertRedirect(route('backend.reels.index'))
            ->assertSessionHasNoErrors();

        $this->assertNotNull(Reel::where('title', 'Added by upload')->firstOrFail()->video);
    }

    public function test_a_reel_with_neither_a_file_nor_a_link_is_refused(): void
    {
        $this->signedInAsAdmin()
            ->post(route('backend.reels.store'), ['title' => 'Nothing to play', 'is_active' => 1])
            ->assertSessionHasErrors('video');

        $this->assertSame(0, Reel::count());
    }

    public function test_a_link_only_reel_must_carry_an_embeddable_link(): void
    {
        $this->signedInAsAdmin()
            ->post(route('backend.reels.store'), [
                'title'         => 'Profile, not a reel',
                'instagram_url' => 'https://www.instagram.com/hireminds_academy/',
                'is_active'     => 1,
            ])
            ->assertSessionHasErrors('instagram_url');

        $this->assertSame(0, Reel::count());
    }

    /** Editing a reel that already has a file must not demand one again. */
    public function test_an_existing_upload_satisfies_the_source_rule_on_update(): void
    {
        $reel = $this->reel(['video' => 'assets/videos/reel.mp4']);

        $this->signedInAsAdmin()
            ->put(route('backend.reels.update', $reel), [
                'title'      => 'Renamed, same file',
                'is_active'  => 1,
                'sort_order' => 0,
            ])
            ->assertRedirect(route('backend.reels.index'))
            ->assertSessionHasNoErrors();

        $this->assertSame('Renamed, same file', $reel->fresh()->title);
        $this->assertSame('assets/videos/reel.mp4', $reel->fresh()->video);
    }
}
