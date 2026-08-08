<?php

namespace Tests\Feature;

use App\Models\Hero;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * The home page hero's right-hand visual: a static yellow backdrop with an
 * optional admin-uploaded photo layered over it.
 */
class HeroVisualTest extends TestCase
{
    use RefreshDatabase;

    private function signedInAdmin(): self
    {
        $admin = User::where('is_super_admin', true)->firstOrFail();

        return $this->withSession([
            'admin_logged_in' => true,
            'admin_id'        => $admin->id,
            'admin_name'      => $admin->name,
            'admin_email'     => $admin->email,
        ]);
    }

    private function hero(): Hero
    {
        return Hero::current();
    }

    /**
     * Persist the hero with this photo (null for none).
     *
     * Not Hero::query()->update(): current() returns an unsaved fallback when the
     * table is empty, so that would change no rows and the page would still
     * render the fallback's own image.
     */
    private function heroPhoto(?string $image): void
    {
        Hero::current()->fill(['image' => $image])->save();
    }

    /* ============================== FRONTEND ============================== */

    public function test_the_backdrop_is_drawn_whether_or_not_a_photo_is_set(): void
    {
        $backdrop = asset(Hero::BACKDROP);

        // With a photo…
        $this->heroPhoto(Hero::DEFAULT_PERSON);
        $this->get(route('frontend.index'))->assertOk()->assertSee($backdrop, false);

        // …and without one. The yellow shape is the theme's, not the admin's.
        $this->heroPhoto(null);
        $this->get(route('frontend.index'))->assertOk()->assertSee($backdrop, false);
    }

    public function test_the_photo_is_drawn_only_when_one_is_set(): void
    {
        $this->heroPhoto(Hero::DEFAULT_PERSON);

        $this->get(route('frontend.index'))
            ->assertOk()
            ->assertSee(asset(Hero::DEFAULT_PERSON), false)
            ->assertSee('hm-hero__student', false);

        $this->heroPhoto(null);

        $this->get(route('frontend.index'))
            ->assertOk()
            ->assertDontSee('hm-hero__student', false);
    }

    public function test_the_loader_waits_on_the_backdrop_not_the_optional_photo(): void
    {
        // The loader dismisses when [data-hm-hero-img] has decoded. Hanging that
        // off the photo would put it on an element that may never be rendered.
        $this->heroPhoto(null);

        $html = $this->get(route('frontend.index'))->assertOk()->getContent();

        // Exactly one element carries it, and it is the backdrop. (The string
        // also appears in the loader's own querySelector, so count the tag.)
        $this->assertStringContainsString('class="hm-hero__blob" data-hm-hero-img', $html);
        $this->assertSame(1, preg_match_all('/<img[^>]*data-hm-hero-img/', $html));

        // Still exactly one once a photo is added — it must not move onto it.
        $this->heroPhoto(Hero::DEFAULT_PERSON);
        $html = $this->get(route('frontend.index'))->assertOk()->getContent();

        $this->assertSame(1, preg_match_all('/<img[^>]*data-hm-hero-img/', $html));
        $this->assertStringContainsString('class="hm-hero__blob" data-hm-hero-img', $html);
    }

    public function test_the_photo_is_only_preloaded_when_there_is_one(): void
    {
        $backdropPreload = 'rel="preload" as="image" fetchpriority="high"';

        $this->heroPhoto(null);
        $html = $this->get(route('frontend.index'))->assertOk()->getContent();

        // The backdrop is always worth preloading; a preload for an image the
        // page never requests is a wasted round trip and a console warning.
        $this->assertStringContainsString($backdropPreload, $html);
        $this->assertSame(1, substr_count($html, $backdropPreload));

        $this->heroPhoto(Hero::DEFAULT_PERSON);
        $html = $this->get(route('frontend.index'))->assertOk()->getContent();

        $this->assertSame(2, substr_count($html, $backdropPreload));
    }

    public function test_the_shipped_artwork_exists_on_disk(): void
    {
        foreach ([Hero::BACKDROP, Hero::DEFAULT_PERSON] as $path) {
            $this->assertFileExists(public_path($path), "Missing hero artwork: {$path}");
        }
    }

    /* ================================ ADMIN ================================ */

    public function test_the_admin_form_previews_the_photo_on_the_backdrop(): void
    {
        $html = $this->signedInAdmin()->get(route('backend.hero.edit'))->assertOk()->getContent();

        // What the panel shows has to be what the home page will draw.
        $this->assertStringContainsString('hero-shot__bg', $html);
        $this->assertStringContainsString('hero-shot__person', $html);
        $this->assertStringContainsString(asset(Hero::BACKDROP), $html);
        $this->assertStringContainsString('name="remove_image"', $html);
    }

    public function test_the_admin_can_remove_the_photo_and_keep_the_backdrop(): void
    {
        $this->heroPhoto(Hero::DEFAULT_PERSON);

        $this->signedInAdmin()
            ->put(route('backend.hero.update'), $this->payload(['remove_image' => '1']))
            ->assertRedirect();

        $this->assertNull($this->hero()->image);

        // The hero still renders — the backdrop is not the admin's to remove.
        $this->get(route('frontend.index'))->assertOk()->assertSee(asset(Hero::BACKDROP), false);
    }

    public function test_uploading_a_photo_stores_it_and_beats_a_pending_removal(): void
    {
        Storage::fake('public');

        $this->signedInAdmin()->put(route('backend.hero.update'), $this->payload([
            'image'        => UploadedFile::fake()->image('student.png', 800, 1000),
            'remove_image' => '1',   // both sent: the upload is the later intent
        ]))->assertRedirect();

        $image = $this->hero()->image;

        $this->assertNotNull($image, 'The upload should win over the remove tick.');
        $this->assertStringStartsWith('storage/hero/', $image);
    }

    public function test_leaving_both_alone_keeps_the_current_photo(): void
    {
        $this->heroPhoto(Hero::DEFAULT_PERSON);

        $this->signedInAdmin()->put(route('backend.hero.update'), $this->payload())->assertRedirect();

        $this->assertSame(Hero::DEFAULT_PERSON, $this->hero()->image);
    }

    /** The copy fields the form requires, so a test can vary only the image. */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'badge_text'  => 'Learn • Practice • Get Hired',
            'title'       => 'Your Future Starts With the Right Skills',
            'description' => 'Build practical knowledge.',
            'btn1_text'   => 'Explore Course',
            'btn2_text'   => 'Apply',
        ], $overrides);
    }
}
