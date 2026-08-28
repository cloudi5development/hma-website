<?php

namespace Tests\Feature\Frontend;

use App\Models\Reel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/**
 * Reels added by pasting a YouTube link.
 *
 * The third source, alongside an uploaded clip and an Instagram link. Like the
 * Instagram embed it is click-to-play inside YouTube's own iframe; unlike it,
 * there is no chrome to crop away, so the card frames it uncropped.
 *
 * The link a visitor's browser is asked to frame is rebuilt from the video id
 * alone, so whatever an admin pastes can never reach an iframe src as-is — the
 * same rule the Instagram embed follows.
 */
class ReelYouTubeTest extends TestCase
{
    use RefreshDatabase;

    private const ID = 'dQw4w9WgXcQ';

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

    public function test_every_youtube_url_shape_yields_the_same_embed(): void
    {
        $id = self::ID;

        foreach ([
            "https://www.youtube.com/watch?v={$id}",
            "https://youtube.com/watch?v={$id}",
            "https://m.youtube.com/watch?v={$id}",
            "https://www.youtube.com/watch?v={$id}&t=42s",
            "https://www.youtube.com/watch?app=desktop&v={$id}",
            "https://youtu.be/{$id}",
            "https://youtu.be/{$id}?si=AbCdEfGhIjKl",
            "https://www.youtube.com/shorts/{$id}",
            "https://www.youtube.com/embed/{$id}",
            "https://www.youtube.com/live/{$id}",
            "https://www.youtube-nocookie.com/embed/{$id}",
        ] as $url) {
            $reel = new Reel(['youtube_url' => $url]);

            $this->assertSame($id, $reel->youtubeId(), "failed on {$url}");
            $this->assertSame(
                "https://www.youtube.com/embed/{$id}?rel=0&playsinline=1",
                $reel->embed_url,
                "failed on {$url}",
            );
        }
    }

    /**
     * Only YouTube may be framed. Without the host check, whatever an admin
     * pasted would be handed straight to an iframe src.
     */
    public function test_a_link_to_any_other_host_is_never_embedded(): void
    {
        foreach ([
            'https://evil.example.com/watch?v=' . self::ID,
            'https://youtube.com.evil.example/watch?v=' . self::ID,
            'https://notyoutube.com/watch?v=' . self::ID,
            'javascript:alert(1)',
            'https://www.youtube.com/@hiremindsacademy',   // a channel, not a video
            'https://www.youtube.com/watch?v=',            // no id at all
            'https://www.instagram.com/reel/C8xYzAbCdEf/',
        ] as $url) {
            $reel = new Reel(['youtube_url' => $url]);

            $this->assertNull($reel->youtubeId(), "should not have parsed {$url}");
            $this->assertNull($reel->embed_url, "should not have embedded {$url}");
        }
    }

    // ----------------------------------------------------------- which source

    public function test_an_upload_still_wins_over_a_youtube_link(): void
    {
        $reel = $this->reel([
            'video'       => 'assets/videos/reel.mp4',
            'youtube_url' => 'https://youtu.be/' . self::ID,
        ]);

        $this->assertFalse($reel->usesEmbed());
        $this->assertNotNull($reel->video_url);
    }

    /**
     * With both links, YouTube plays and Instagram stays what it has always
     * also been — the card's badge.
     */
    public function test_youtube_is_preferred_over_instagram(): void
    {
        $reel = $this->reel([
            'youtube_url'   => 'https://youtu.be/' . self::ID,
            'instagram_url' => 'https://www.instagram.com/reel/C8xYzAbCdEf/',
        ]);

        $this->assertSame('youtube', $reel->embedProvider());
        $this->assertStringContainsString('youtube.com/embed', $reel->embed_url);
    }

    public function test_an_instagram_only_reel_is_unaffected(): void
    {
        $reel = $this->reel(['instagram_url' => 'https://www.instagram.com/reel/C8xYzAbCdEf/']);

        $this->assertSame('instagram', $reel->embedProvider());
        $this->assertSame('https://www.instagram.com/reel/C8xYzAbCdEf/embed/', $reel->embed_url);
    }

    // ------------------------------------------------------------- the slider

    public function test_a_youtube_reel_renders_as_a_lazy_uncropped_iframe(): void
    {
        $this->reel(['youtube_url' => 'https://www.youtube.com/shorts/' . self::ID]);

        $html = $this->get(route('frontend.index'))->assertOk()->getContent();

        $this->assertStringContainsString('hm-reel--embed', $html);

        // The provider class is what switches the Instagram chrome crop off.
        $this->assertStringContainsString('hm-reel--youtube', $html);

        $this->assertStringContainsString(
            'data-src="https://www.youtube.com/embed/' . self::ID . '?rel=0&amp;playsinline=1"',
            $html,
        );

        // Withheld until the section scrolls into view, like every other card.
        $this->assertStringNotContainsString('<iframe class="hm-reel__embed" src=', $html);
    }

    /**
     * The Instagram badge is that link's job. A YouTube-only card has no
     * Instagram link, so it must not render a badge pointing nowhere.
     */
    public function test_a_youtube_only_card_carries_no_empty_instagram_badge(): void
    {
        $this->reel(['youtube_url' => 'https://youtu.be/' . self::ID]);

        $html = $this->get(route('frontend.index'))->assertOk()->getContent();

        // The anchor itself, not the class name — the slider's own script names
        // that class too, so it is in the page either way.
        $this->assertStringNotContainsString('<a class="hm-reel__badge"', $html);
    }

    public function test_a_youtube_card_keeps_an_instagram_badge_when_both_were_given(): void
    {
        $this->reel([
            'youtube_url'   => 'https://youtu.be/' . self::ID,
            'instagram_url' => 'https://www.instagram.com/reel/C8xYzAbCdEf/',
        ]);

        $html = $this->get(route('frontend.index'))->assertOk()->getContent();

        $this->assertStringContainsString('hm-reel--youtube', $html);
        $this->assertStringContainsString(
            'href="https://www.instagram.com/reel/C8xYzAbCdEf/"',
            $html,
        );
    }

    public function test_all_three_kinds_share_the_slider(): void
    {
        $this->reel(['video' => 'assets/videos/one.mp4']);
        $this->reel(['youtube_url' => 'https://youtu.be/' . self::ID]);
        $this->reel(['instagram_url' => 'https://www.instagram.com/reel/C8xYzAbCdEf/']);

        $html = $this->get(route('frontend.index'))->assertOk()->getContent();

        $this->assertStringContainsString('hm-reel__video', $html);
        $this->assertStringContainsString('hm-reel--youtube', $html);
        $this->assertStringContainsString('hm-reel--instagram', $html);
    }

    public function test_the_policy_allows_youtube_to_be_framed(): void
    {
        $policy = $this->get(route('frontend.index'))
            ->assertOk()
            ->headers->get('Content-Security-Policy');

        preg_match('/frame-src ([^;]+)/', $policy, $m);

        $this->assertStringContainsString('https://www.youtube.com', $m[1]);

        // Framing only. YouTube's scripts are still refused — everything the
        // player needs loads inside its own iframe.
        preg_match('/script-src ([^;]+)/', $policy, $s);
        $this->assertStringNotContainsString('youtube', $s[1]);
    }

    // --------------------------------------------------------------- the form

    public function test_a_reel_can_be_created_from_a_youtube_link_alone(): void
    {
        $this->signedInAsAdmin()
            ->post(route('backend.reels.store'), [
                'title'       => 'Added by YouTube',
                'youtube_url' => 'https://www.youtube.com/shorts/' . self::ID,
                'is_active'   => 1,
                'sort_order'  => 0,
            ])
            ->assertRedirect(route('backend.reels.index'))
            ->assertSessionHasNoErrors();

        $reel = Reel::where('title', 'Added by YouTube')->firstOrFail();

        $this->assertNull($reel->video);
        $this->assertTrue($reel->usesEmbed());
        $this->assertSame('youtube', $reel->embedProvider());
    }

    public function test_an_unusable_youtube_link_is_refused(): void
    {
        $this->signedInAsAdmin()
            ->post(route('backend.reels.store'), [
                'title'       => 'A channel, not a video',
                'youtube_url' => 'https://www.youtube.com/@hiremindsacademy',
                'is_active'   => 1,
            ])
            ->assertSessionHasErrors('youtube_url');

        $this->assertSame(0, Reel::count());
    }

    /** Even next to a working upload — a broken link is still a mistake. */
    public function test_a_broken_youtube_link_is_refused_alongside_an_upload(): void
    {
        $this->signedInAsAdmin()
            ->post(route('backend.reels.store'), [
                'title'       => 'Good file, bad link',
                'video'       => UploadedFile::fake()->create('clip.mp4', 128, 'video/mp4'),
                'youtube_url' => 'https://vimeo.com/123456789',
                'is_active'   => 1,
            ])
            ->assertSessionHasErrors('youtube_url');
    }

    /**
     * An Instagram profile link is a fine badge next to a YouTube video — it is
     * only rejected when it is the thing that has to play.
     */
    public function test_an_unembeddable_instagram_link_is_allowed_as_a_badge(): void
    {
        $this->signedInAsAdmin()
            ->post(route('backend.reels.store'), [
                'title'         => 'Plays from YouTube, badges to a profile',
                'youtube_url'   => 'https://youtu.be/' . self::ID,
                'instagram_url' => 'https://www.instagram.com/hireminds_academy/',
                'is_active'     => 1,
                'sort_order'    => 0,
            ])
            ->assertRedirect(route('backend.reels.index'))
            ->assertSessionHasNoErrors();

        $this->assertSame(1, Reel::count());
    }

    public function test_the_form_offers_the_youtube_field(): void
    {
        $this->signedInAsAdmin()
            ->get(route('backend.reels.create'))
            ->assertOk()
            ->assertSee('name="youtube_url"', false)
            ->assertSee('YouTube Link');
    }
}
