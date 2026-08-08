<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Department;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Card colours are dealt from the palette instead of picked in the panel.
 *
 * The two work differently on purpose: a category is only ever drawn in colour
 * on the home grid, so its tone comes from the card's position there; an event
 * is drawn on four surfaces, so its tone is stored once at creation and stays
 * with it.
 */
class CardToneCycleTest extends TestCase
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

    private function event(array $overrides = []): Event
    {
        return Event::create(array_merge([
            'speaker'   => 'Speaker',
            'title'     => 'Event ' . (Event::count() + 1),
            'type'      => 'Live Event',
            'image'     => 'assets/images/events/speaker.webp',
            'is_active' => true,
            'show_home' => true,
        ], $overrides));
    }

    /* ============================== CATEGORIES ============================== */

    public function test_the_home_grid_walks_the_palette_by_position(): void
    {
        $palette = Category::TONES;

        foreach (range(0, count($palette) * 2) as $i) {
            $this->assertSame(
                $palette[$i % count($palette)],
                Category::toneForIndex($i),
                "Position {$i} fell out of the palette cycle."
            );
        }
    }

    public function test_every_card_in_a_full_grid_is_a_different_colour(): void
    {
        $tones = array_map(
            fn ($i) => Category::toneForIndex($i),
            range(0, count(Category::TONES) - 1)
        );

        $this->assertCount(count(Category::TONES), array_unique($tones));
    }

    public function test_the_grid_renders_the_positional_tones(): void
    {
        $department = Department::create(['name' => 'Tech', 'is_active' => true]);

        // Deliberately stored out of palette order — the row's own value must not
        // be what the grid draws.
        foreach (['Alpha', 'Beta', 'Gamma'] as $i => $name) {
            Category::create([
                'department_id' => $department->id,
                'name'          => $name,
                'tone'          => 'gold',
                'icon'          => 'assets/images/categories/iconsax-cloud.png',
                'sort_order'    => $i,
                'is_active'     => true,
                'show_home'     => true,
            ]);
        }

        $html = $this->get(route('frontend.index'))->assertOk()->getContent();

        foreach ([0, 1, 2] as $i) {
            $this->assertStringContainsString('hm-cat--' . Category::toneForIndex($i), $html);
        }

        // The blob artwork follows the same tone, so it must exist for each.
        foreach (Category::TONES as $tone) {
            $this->assertFileExists(public_path("assets/images/categories/blob-{$tone}.png"));
        }
    }

    public function test_the_category_form_no_longer_asks_for_a_colour(): void
    {
        $html = $this->signedInAdmin()->get(route('backend.categories.create'))->assertOk()->getContent();

        $this->assertStringNotContainsString('name="tone"', $html);
        $this->assertStringNotContainsString('Card Tone', $html);
    }

    /* ================================ EVENTS ================================ */

    public function test_a_new_event_takes_the_next_colour_in_the_palette(): void
    {
        $palette = Event::TONES;

        // One full lap plus one, to prove it wraps rather than running out.
        foreach (range(0, count($palette)) as $i) {
            $this->assertSame($palette[$i % count($palette)], $this->event()->tone);
        }
    }

    public function test_an_event_keeps_its_colour_when_it_is_edited(): void
    {
        $event = $this->event();
        $tone = $event->tone;

        // The hook is on creating, not saving — renaming a card must not recolour it.
        $event->update(['title' => 'Renamed', 'sort_order' => 9]);

        $this->assertSame($tone, $event->fresh()->tone);
    }

    public function test_an_explicit_colour_is_still_honoured(): void
    {
        // Seeders and tests may still set one deliberately.
        $this->assertSame('green', $this->event(['tone' => 'green'])->tone);
    }

    public function test_the_same_event_is_the_same_colour_on_every_surface(): void
    {
        // Three so the listing, the carousel and the sidebar all have something
        // to draw, and so the tones are not accidentally all equal.
        $first = $this->event(['title' => 'First Event']);
        $this->event(['title' => 'Second Event']);
        $this->event(['title' => 'Third Event']);

        $listing = $this->get(route('frontend.events'))->assertOk()->getContent();
        $details = $this->get(route('frontend.event-details', $first->slug))->assertOk()->getContent();

        // Stored rather than derived per list, which is what keeps these agreeing.
        $this->assertStringContainsString('hm-evl-card--' . $first->tone, $listing);
        $this->assertStringContainsString('hm-ed-hero__media--' . $first->tone, $details);
    }

    public function test_the_event_form_no_longer_asks_for_a_colour(): void
    {
        $html = $this->signedInAdmin()->get(route('backend.events.create'))->assertOk()->getContent();

        $this->assertStringNotContainsString('name="tone"', $html);
        $this->assertStringNotContainsString('Card Colour', $html);
    }

    public function test_an_event_saves_without_a_colour_being_posted(): void
    {
        Storage::fake('public');

        // The field is gone from the form, so "required" here would have made
        // every single save fail.
        $this->signedInAdmin()->post(route('backend.events.store'), [
            'speaker' => 'Rochelle Fernandez',
            'title'   => 'Saved Without A Tone',
            'type'    => 'Live Event',
            'image'   => UploadedFile::fake()->image('speaker.jpg', 400, 400),
        ])->assertRedirect(route('backend.events.index'))->assertSessionHasNoErrors();

        $event = Event::firstOrFail();

        $this->assertContains($event->tone, Event::TONES);
    }
}
