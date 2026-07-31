<?php

namespace Tests\Feature\Frontend;

use App\Models\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EventDetailsTest extends TestCase
{
    use RefreshDatabase;

    private function event(array $overrides = []): Event
    {
        return Event::create(array_merge([
            'speaker'    => 'Rochelle Fernandez',
            'title'      => 'AI & Future Tech Bootcamp 2026',
            'type'       => 'Live Event',
            'tone'       => 'purple',
            'image'      => 'assets/images/events/speaker.webp',
            'is_active'  => true,
            'show_home'  => true,
        ], $overrides));
    }

    public function test_the_details_page_renders_everything_the_admin_entered(): void
    {
        $event = $this->event([
            'short_description'   => 'A one-day bootcamp on applied AI.',
            'description'         => "Paragraph one.\n\nParagraph two.",
            'event_date'          => '2026-08-20',
            'event_time'          => '10:00',
            'end_time'            => '18:00',
            'venue'               => 'Hire Minds Campus',
            'city'                => 'Chennai',
            'state'               => 'Tamil Nadu',
            'country'             => 'India',
            'price'               => '₹999/-',
            'offer_price'         => '₹499/-',
            'discount_percentage' => 50,
            'total_seats'         => 100,
            'available_seats'     => 25,
            'duration'            => '1 Day',
            'language'            => 'English',
            'level'               => 'Beginner',
            'organizer'           => 'Hire Minds Academy',
        ]);

        $event->highlights()->create(['icon' => 'tech', 'title' => 'Hands-on practice', 'display_order' => 0, 'is_active' => true]);
        $event->speakers()->create(['name' => 'Rahul Sharma', 'designation' => 'AI Engineer', 'display_order' => 0, 'is_active' => true]);
        $event->faqs()->create(['question' => 'Is it beginner friendly?', 'answer' => 'Yes, entirely.', 'display_order' => 0, 'is_active' => true]);

        $this->get(route('frontend.event-details', $event->slug))
            ->assertOk()
            ->assertSee('AI &amp; Future Tech Bootcamp 2026', false)
            ->assertSee('A one-day bootcamp on applied AI.')
            ->assertSee('Paragraph two.')
            ->assertSee('Hire Minds Campus, Chennai, Tamil Nadu, India')
            // The facts row splits the range: start in bold, "to <end>" beneath.
            ->assertSee('10:00 AM')
            ->assertSee('to 6:00 PM')
            ->assertSee('20 August, 2026')
            ->assertSee('Early Bird Price')
            ->assertSee('₹499/-')
            ->assertSee('50% OFF')
            ->assertSee('Only 25 Left')
            ->assertSee('100 Seats')
            ->assertSee('Beginner')
            ->assertSee('Hands-on practice')
            ->assertSee('Rahul Sharma')
            ->assertSee('Is it beginner friendly?');
    }

    public function test_disabled_rows_are_not_rendered(): void
    {
        $event = $this->event();

        $event->highlights()->create(['icon' => 'tech', 'title' => 'Shown highlight', 'display_order' => 0, 'is_active' => true]);
        $event->highlights()->create(['icon' => 'tech', 'title' => 'Hidden highlight', 'display_order' => 1, 'is_active' => false]);
        $event->speakers()->create(['name' => 'Shown Speaker', 'display_order' => 0, 'is_active' => true]);
        $event->speakers()->create(['name' => 'Hidden Speaker', 'display_order' => 1, 'is_active' => false]);
        $event->faqs()->create(['question' => 'Shown question?', 'answer' => 'Yes.', 'display_order' => 0, 'is_active' => true]);
        $event->faqs()->create(['question' => 'Hidden question?', 'answer' => 'No.', 'display_order' => 1, 'is_active' => false]);

        $this->get(route('frontend.event-details', $event->slug))
            ->assertOk()
            ->assertSee('Shown highlight')->assertDontSee('Hidden highlight')
            ->assertSee('Shown Speaker')->assertDontSee('Hidden Speaker')
            ->assertSee('Shown question?')->assertDontSee('Hidden question?');
    }

    public function test_rows_render_in_their_display_order(): void
    {
        $event = $this->event();

        // Created out of order — display_order, not insertion order, decides.
        $event->highlights()->create(['icon' => 'tech', 'title' => 'Third item', 'display_order' => 2, 'is_active' => true]);
        $event->highlights()->create(['icon' => 'tech', 'title' => 'First item', 'display_order' => 0, 'is_active' => true]);
        $event->highlights()->create(['icon' => 'tech', 'title' => 'Second item', 'display_order' => 1, 'is_active' => true]);

        $html = $this->get(route('frontend.event-details', $event->slug))->assertOk()->getContent();

        $this->assertTrue(
            strpos($html, 'First item') < strpos($html, 'Second item')
                && strpos($html, 'Second item') < strpos($html, 'Third item'),
            'Highlights are not rendered in display_order.'
        );
    }

    public function test_an_inactive_or_unknown_event_is_a_404(): void
    {
        $this->get(route('frontend.event-details', 'nothing-here'))->assertNotFound();

        $hidden = $this->event(['title' => 'Hidden Event', 'is_active' => false]);
        $this->get(route('frontend.event-details', $hidden->slug))->assertNotFound();
    }

    public function test_the_sidebar_lists_other_events_but_never_this_one(): void
    {
        $event = $this->event();
        $this->event(['title' => 'Second Event']);
        $this->event(['title' => 'Third Event']);
        $this->event(['title' => 'Fourth Event']);
        $this->event(['title' => 'Fifth Event']);

        $html = $this->get(route('frontend.event-details', $event->slug))->assertOk()->getContent();

        // The sidebar caps at three, and the current event is excluded from them.
        $this->assertSame(1, substr_count($html, 'href="' . route('frontend.event-details', $event->slug) . '"'),
            'The current event should only appear in the breadcrumb-level markup, not the sidebar.');

        // Counted off the sidebar's own items: the registration modal's "Select
        // Event" dropdown also names every event, so searching the whole page
        // would count those too.
        preg_match_all('/hm-ed-up__name">(.*?)</s', $html, $matches);
        $listed = array_map('trim', $matches[1]);

        $this->assertCount(3, $listed, 'The sidebar should list exactly three other events.');
        $this->assertNotContains($event->title, $listed, 'The sidebar must not list the event being viewed.');
    }

    public function test_the_page_is_free_of_n_plus_one_queries(): void
    {
        $event = $this->event();

        foreach (range(1, 4) as $i) {
            $event->highlights()->create(['icon' => 'tech', 'title' => "Highlight {$i}", 'display_order' => $i, 'is_active' => true]);
            $event->speakers()->create(['name' => "Speaker {$i}", 'display_order' => $i, 'is_active' => true]);
            $event->faqs()->create(['question' => "Question {$i}?", 'answer' => 'Yes.', 'display_order' => $i, 'is_active' => true]);
            $this->event(['title' => "Other Event {$i}"]);
        }

        $queries = 0;
        DB::listen(function () use (&$queries) {
            $queries++;
        });

        $this->get(route('frontend.event-details', $event->slug))->assertOk();

        // event + highlights + speakers + faqs + sidebar, plus whatever the shared
        // layout (settings, menus) runs. Eager loading keeps this flat however
        // many rows each relation holds.
        $this->assertLessThanOrEqual(25, $queries, "The details page ran {$queries} queries — check the eager loads.");
    }

    public function test_seo_falls_back_to_the_event_when_the_seo_fields_are_blank(): void
    {
        $event = $this->event(['short_description' => 'A one-day bootcamp on applied AI.']);

        $this->get(route('frontend.event-details', $event->slug))
            ->assertOk()
            ->assertSee('<title>AI &amp; Future Tech Bootcamp 2026', false)
            ->assertSee('A one-day bootcamp on applied AI.')
            ->assertSee('rel="canonical" href="' . route('frontend.event-details', $event->slug) . '"', false);
    }

    public function test_the_seo_fields_win_when_they_are_filled_in(): void
    {
        $event = $this->event([
            'seo_title'       => 'Bootcamp 2026 | Book Your Seat',
            'seo_description' => 'Reserve a seat at the applied-AI bootcamp.',
            'seo_keywords'    => 'ai bootcamp, chennai',
        ]);

        $this->get(route('frontend.event-details', $event->slug))
            ->assertOk()
            ->assertSee('<title>Bootcamp 2026 | Book Your Seat', false)
            ->assertSee('Reserve a seat at the applied-AI bootcamp.')
            ->assertSee('ai bootcamp, chennai');
    }

    public function test_the_listing_links_each_card_to_its_details_page(): void
    {
        $event = $this->event();

        $this->get(route('frontend.events'))
            ->assertOk()
            ->assertSee(route('frontend.event-details', $event->slug), false);
    }

    public function test_register_opens_the_modal_unless_the_event_has_its_own_link(): void
    {
        $withLink = $this->event(['title' => 'Has A Link', 'link' => 'https://example.com/register']);
        $noLink   = $this->event(['title' => 'No Link']);

        // An admin-set link means "book somewhere else" — it wins over the modal.
        $this->get(route('frontend.event-details', $withLink->slug))
            ->assertOk()
            ->assertSee('https://example.com/register', false)
            ->assertDontSee('id="hmRegisterModal"', false);

        $this->get(route('frontend.event-details', $noLink->slug))
            ->assertOk()
            ->assertSee('data-bs-target="#hmRegisterModal"', false);
    }

    public function test_the_enquiry_form_opens_pre_filled_for_the_event(): void
    {
        $event = $this->event(['event_date' => '2026-08-20']);

        $this->get(route('frontend.contact-us', ['event' => $event->slug]))
            ->assertOk()
            ->assertSee('I would like to register for AI &amp; Future Tech Bootcamp 2026 on 20 August, 2026.', false)
            ->assertSee('name="interest" value="AI &amp; Future Tech Bootcamp 2026"', false);
    }

    public function test_the_enquiry_form_is_untouched_without_a_valid_event(): void
    {
        $this->event(['is_active' => false, 'title' => 'Unpublished Event']);

        // No parameter at all, an unknown slug, and an unpublished event all
        // have to leave the shared contact form exactly as the home page uses it.
        foreach ([[], ['event' => 'no-such-event'], ['event' => 'unpublished-event']] as $query) {
            $this->get(route('frontend.contact-us', $query))
                ->assertOk()
                ->assertDontSee('I would like to register for')
                ->assertDontSee('name="interest"', false);
        }

        $this->get(route('frontend.index'))->assertOk()->assertDontSee('name="interest"', false);
    }

    public function test_the_faq_band_sits_outside_the_two_column_layout(): void
    {
        $event = $this->event();
        $event->faqs()->create(['question' => 'Shown question?', 'answer' => 'Yes.', 'display_order' => 0, 'is_active' => true]);

        $html = $this->get(route('frontend.event-details', $event->slug))->assertOk()->getContent();

        // The accordion is a full-bleed band, so it has to come after .hm-ed
        // closes rather than inside the left column beside the sidebar.
        $this->assertTrue(
            strpos($html, 'hm-ed-side') < strpos($html, 'hm-faq'),
            'The FAQ renders before the sidebar, so it is still nested in the content column.'
        );
        $this->assertStringContainsString('hm-ed--with-faq', $html);
    }

    public function test_an_event_without_faqs_drops_the_band_and_its_spacing_modifier(): void
    {
        $html = $this->get(route('frontend.event-details', $this->event()->slug))->assertOk()->getContent();

        $this->assertStringNotContainsString('hm-ed--with-faq', $html);
        $this->assertStringNotContainsString('hm-faq', $html);
    }

    public function test_every_offered_highlight_icon_has_artwork_on_disk(): void
    {
        $this->assertSame(
            array_keys(Event::HIGHLIGHT_ICONS),
            array_keys(Event::HIGHLIGHT_ICON_FILES),
            'The admin dropdown and the artwork map have drifted apart.'
        );

        foreach (Event::HIGHLIGHT_ICON_FILES as $key => $file) {
            $this->assertFileExists(
                public_path('assets/images/icons-details/' . $file),
                "The \"{$key}\" icon points at artwork that is not in icons-details/."
            );
        }
    }

    public function test_a_highlight_renders_its_chosen_icon_and_an_unknown_key_still_renders_one(): void
    {
        $event = $this->event();
        $event->highlights()->create(['icon' => 'career', 'title' => 'Career talk', 'display_order' => 0, 'is_active' => true]);
        $event->highlights()->create(['icon' => 'retired-key', 'title' => 'Old row', 'display_order' => 1, 'is_active' => true]);

        $html = $this->get(route('frontend.event-details', $event->slug))->assertOk()->getContent();

        $this->assertStringContainsString('icons-details/briefcase-business.png', $html);
        // A row saved before the icon set changed must not leave a blank circle.
        $this->assertSame(2, substr_count($html, 'hm-ed-learn__glyph'));

        $files = Event::HIGHLIGHT_ICON_FILES;
        $this->assertStringContainsString('icons-details/' . reset($files), $html);
    }
}
