<?php

namespace Tests\Feature\Frontend;

use App\Models\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventsListingTest extends TestCase
{
    use RefreshDatabase;

    private function event(array $attributes = []): Event
    {
        return Event::create(array_merge([
            'speaker'   => 'Regina Phalange',
            'title'     => 'Nail your interviews',
            'type'      => 'Live Event',
            'image'     => 'assets/images/events/person-3.webp',
            'tone'      => 'teal',
            'is_active' => true,
            'show_home' => true,
        ], $attributes));
    }

    public function test_the_home_page_links_to_the_listing(): void
    {
        $this->event();

        $this->get('/')
            ->assertOk()
            ->assertSee(route('frontend.events'), false)
            ->assertSee('See all');
    }

    public function test_the_see_all_button_has_a_rule_that_reveals_it(): void
    {
        // The button carries .hm-ev-anim, which starts at opacity 0 and is only
        // shown by a "<container>.is-in .hm-ev-anim" rule. The rule used to name
        // .hm-events__head alone, so the button rendered — correct markup, correct
        // link, completely invisible. assertSee() cannot catch that, hence this.
        $css = file_get_contents(public_path('assets/css/frontend/home.css'));

        $this->assertMatchesRegularExpression(
            '/\.hm-events__more\.is-in\s+\.hm-ev-anim/',
            $css,
            'home.css must reveal .hm-ev-anim inside .hm-events__more, or the "See all" button stays invisible'
        );
    }

    public function test_the_listing_shows_active_events_with_banner_and_crumbs(): void
    {
        $this->event(['title' => 'Nail your interviews']);
        $this->event(['title' => 'Hidden Event', 'is_active' => false]);

        $response = $this->get(route('frontend.events'))->assertOk();

        $response->assertSee('Events');
        $response->assertSee('Nail your interviews');
        $response->assertDontSee('Hidden Event');

        // Breadcrumb back to the home page.
        $response->assertSee(route('frontend.index'), false);
    }

    public function test_it_lists_events_that_are_not_flagged_for_the_home_page(): void
    {
        // The carousel only shows show_home events; the listing is the full programme.
        $this->event(['title' => 'Not On Home', 'show_home' => false]);

        $this->get(route('frontend.events'))->assertOk()->assertSee('Not On Home');
    }

    public function test_the_paid_free_tag_follows_the_price(): void
    {
        $this->event(['title' => 'Costs Money', 'price' => '₹499/-']);
        $this->event(['title' => 'Costs Nothing', 'price' => null]);

        $html = $this->get(route('frontend.events'))->assertOk()->getContent();

        $this->assertStringContainsString('Paid', $html);
        $this->assertStringContainsString('Free', $html);

        // The two carry different pill colours, which comes from the modifier —
        // without it both would render gold and the distinction would be lost.
        $this->assertStringContainsString('hm-evl-card__tag--paid', $html);
        $this->assertStringContainsString('hm-evl-card__tag--free', $html);
    }

    public function test_every_card_is_the_same_shape(): void
    {
        // The grid must not vary card height with content length: the height is
        // derived from the (equal) column width via aspect-ratio, so all cards
        // match. A stray `height: 100%` here would hand control back to content.
        $css = file_get_contents(public_path('assets/css/frontend/events.css'));

        $this->assertMatchesRegularExpression('/\.hm-evl-card\s*\{[^}]*aspect-ratio:/s', $css);
    }

    public function test_the_schedule_rows_appear_when_set(): void
    {
        $this->event([
            'title'      => 'Scheduled',
            'event_date' => '2026-07-20',
            'event_time' => '10:00',
        ]);

        $this->get(route('frontend.events'))
            ->assertOk()
            ->assertSee('20 July, 2026')
            ->assertSee('10:00 AM');
    }

    public function test_search_filters_by_title_and_speaker(): void
    {
        $this->event(['title' => 'No-code tools', 'speaker' => 'Rochelle Fernandez']);
        $this->event(['title' => 'Java Full Stack', 'speaker' => 'Regina Mothvani']);

        $this->get(route('frontend.events', ['q' => 'no-code']))
            ->assertOk()
            ->assertSee('No-code tools')
            ->assertDontSee('Java Full Stack');

        $this->get(route('frontend.events', ['q' => 'Mothvani']))
            ->assertOk()
            ->assertSee('Java Full Stack')
            ->assertDontSee('No-code tools');
    }

    public function test_an_empty_search_explains_itself(): void
    {
        $this->event();

        $this->get(route('frontend.events', ['q' => 'zzzznothing']))
            ->assertOk()
            ->assertSee('No events match')
            ->assertSee('Clear the search');
    }

    public function test_it_paginates_at_eight_per_page(): void
    {
        foreach (range(1, 10) as $i) {
            $this->event(['title' => "Event {$i}"]);
        }

        $first = $this->get(route('frontend.events'))->assertOk();
        $first->assertSee('Showing 1');
        $first->assertSee(route('frontend.events', ['page' => 2]), false);

        // Page 2 holds the remaining two.
        $this->get(route('frontend.events', ['page' => 2]))
            ->assertOk()
            ->assertSee('Event 10');
    }

    public function test_the_search_term_survives_pagination(): void
    {
        foreach (range(1, 10) as $i) {
            $this->event(['title' => "Workshop {$i}"]);
        }
        $this->event(['title' => 'Unrelated Talk']);

        $html = $this->get(route('frontend.events', ['q' => 'Workshop']))->assertOk()->getContent();

        // withQueryString() keeps ?q= on the page links.
        $this->assertStringContainsString('q=Workshop', $html);
    }
}
