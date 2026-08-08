<?php

namespace Tests\Feature\Frontend;

use App\Models\Reel;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The reel slider's player chrome, and the floating contact icons that sit above
 * the scroll-to-top arrow.
 *
 * These assert the contract the player script depends on — the hooks it binds
 * to and the single-video-per-card shape. Whether a clip actually rolls is a
 * browser behaviour and is not asserted here.
 */
class ReelPlayerTest extends TestCase
{
    use RefreshDatabase;

    private function reel(array $overrides = []): Reel
    {
        return Reel::create(array_merge([
            'title'         => 'Reel ' . (Reel::count() + 1),
            'video'         => 'assets/videos/reel.mp4',
            'instagram_url' => 'https://instagram.com/p/abc',
            'sort_order'    => Reel::count(),
            'is_active'     => true,
        ], $overrides));
    }


    /**
     * How many HTML tags carry this attribute.
     *
     * Not substr_count: the partial ships its player script inline, and that
     * script names every one of these hooks in its selectors — so counting the
     * bare string counts the JavaScript too.
     */
    private function tagsWith(string $html, string $attribute): int
    {
        return preg_match_all('/<[a-z][^>]*\s' . preg_quote($attribute, '/') . '[\s=>]/i', $html);
    }

    /* =============================== CONTROLS =============================== */

    public function test_every_card_carries_the_player_controls(): void
    {
        $this->reel();
        $this->reel();

        $html = $this->get(route('frontend.index'))->assertOk()->getContent();

        // The deck renders each reel twice, so the slider has somewhere to loop
        // to. Counted off the table rather than hard-coded — the migrations seed
        // reels of their own, so these two are not the only ones on the page.
        $cards = substr_count($html, '<div class="hm-reel"');
        $this->assertSame(Reel::active()->count() * 2, $cards);
        $this->assertGreaterThan(0, $cards);

        // One of each control per card.
        $this->assertSame($cards, $this->tagsWith($html, 'data-reel-mute'));
        $this->assertSame($cards, $this->tagsWith($html, 'data-reel-seek'));
        $this->assertSame($cards, $this->tagsWith($html, 'data-reel-full'));
        $this->assertSame($cards, $this->tagsWith($html, 'data-reel-time'));
        // Two toggles per card: the big centre button and the one in the bar.
        $this->assertSame($cards * 2, $this->tagsWith($html, 'data-reel-toggle'));
    }

    public function test_clips_start_muted_and_are_not_fetched_up_front(): void
    {
        $this->reel();

        $html = $this->get(route('frontend.index'))->assertOk()->getContent();

        // src lives in data-src until the card is reached, so opening the page
        // does not pull every clip down at once.
        $this->assertStringContainsString('data-src="' . asset('assets/videos/reel.mp4') . '"', $html);
        $this->assertStringNotContainsString('<video class="hm-reel__video" src=', $html);

        $this->assertStringContainsString('muted', $html);
        $this->assertStringContainsString('preload="none"', $html);

        // No autoplay attribute: playback is driven by which card is centred.
        $this->assertStringNotContainsString('<video class="hm-reel__video" autoplay', $html);
    }

    public function test_the_browsers_own_video_chrome_is_kept_out_of_the_way(): void
    {
        $this->reel();

        $this->get(route('frontend.index'))
            ->assertOk()
            ->assertSee('disablepictureinpicture', false)
            ->assertSee('controlslist="nodownload noplaybackrate noremoteplayback"', false);
    }

    public function test_the_deck_no_longer_advances_itself(): void
    {
        $this->reel();

        // A deck that moved on every few seconds would restart the centre clip
        // before anyone could watch it.
        $this->get(route('frontend.index'))
            ->assertOk()
            ->assertDontSee('autoplay: { delay: 4000', false);
    }

    public function test_the_instagram_link_is_its_own_button_not_the_whole_card(): void
    {
        $this->reel(['instagram_url' => 'https://instagram.com/p/xyz']);

        $html = $this->get(route('frontend.index'))->assertOk()->getContent();

        // It used to be the card itself, which would fight the play/pause tap.
        $this->assertStringContainsString('<a class="hm-reel__badge" href="https://instagram.com/p/xyz"', $html);
        $this->assertStringNotContainsString('data-instagram', $html);
    }

    public function test_a_reel_without_an_instagram_link_still_gets_its_controls(): void
    {
        $this->reel(['instagram_url' => null]);

        $html = $this->get(route('frontend.index'))->assertOk()->getContent();

        $this->assertStringNotContainsString('<a class="hm-reel__badge"', $html);
        $this->assertGreaterThan(0, $this->tagsWith($html, 'data-reel-toggle'));
    }


    /**
     * Write social settings through the model's own API.
     *
     * Not updateOrCreate: Setting keeps a static cache of the whole table, and
     * only putMany() clears it — writing round the back leaves the page
     * rendering the values that were loaded before the test changed them.
     * A blank string is how a platform is switched off.
     */
    private function social(array $pairs): void
    {
        Setting::putMany($pairs);
    }

    /* ============================ FLOATING ICONS ============================ */

    public function test_whatsapp_and_instagram_float_above_the_scroll_arrow(): void
    {
        $this->social(['social_whatsapp' => '+91 78240 94044', 'social_instagram' => 'instagram.com/hireminds']);

        $html = $this->get(route('frontend.index'))->assertOk()->getContent();

        $this->assertStringContainsString('hm-floats__btn--whatsapp', $html);
        $this->assertStringContainsString('hm-floats__btn--instagram', $html);

        // A bare number is normalised to a wa.me link.
        $this->assertStringContainsString('https://wa.me/917824094044', $html);

        // They sit before the arrow in the document, which is the stack order.
        $this->assertLessThan(strpos($html, 'hm-scrolltop'), strpos($html, 'hm-floats'));
    }

    public function test_a_platform_left_blank_in_settings_does_not_float(): void
    {
        $this->social(['social_whatsapp' => '', 'social_instagram' => 'instagram.com/hireminds']);

        $html = $this->get(route('frontend.index'))->assertOk()->getContent();

        $this->assertStringNotContainsString('hm-floats__btn--whatsapp', $html);
        $this->assertStringContainsString('hm-floats__btn--instagram', $html);
    }

    public function test_the_float_strip_disappears_when_neither_is_configured(): void
    {
        $this->social(['social_whatsapp' => '', 'social_instagram' => '']);

        $this->get(route('frontend.index'))
            ->assertOk()
            ->assertDontSee('hm-floats', false);
    }

    public function test_the_floats_are_on_the_testimonials_page_too(): void
    {
        $this->social(['social_whatsapp' => '+91 78240 94044']);

        // They live in the shared footer, so every page gets them.
        $this->get(route('frontend.testimonials'))
            ->assertOk()
            ->assertSee('hm-floats__btn--whatsapp', false);
    }
}
