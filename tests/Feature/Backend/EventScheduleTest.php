<?php

namespace Tests\Feature\Backend;

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EventScheduleTest extends TestCase
{
    use RefreshDatabase;

    private function asAdmin(): self
    {
        $admin = User::where('is_super_admin', true)->firstOrFail();

        return $this->withSession(['admin_logged_in' => true, 'admin_id' => $admin->id]);
    }

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

    public function test_the_admin_can_save_a_date_time_and_location(): void
    {
        Storage::fake('public');

        $this->asAdmin()
            ->post(route('backend.events.store'), [
                'speaker'    => 'Regina Phalange',
                'title'      => 'Nail your interviews',
                'event_date' => '2026-07-20',
                'event_time' => '10:00',
                'location'   => 'Plot 456, T. Nagar, Chennai, Tamil Nadu, 600020',
                'type'       => 'Live Event',
                'tone'       => 'teal',
                'is_active'  => 1,
                'show_home'  => 1,
                'image'      => UploadedFile::fake()->image('speaker.png', 800, 1000),
            ])
            ->assertRedirect(route('backend.events.index'));

        $event = Event::where('title', 'Nail your interviews')->firstOrFail();

        $this->assertSame('2026-07-20', $event->event_date->toDateString());
        $this->assertSame('Plot 456, T. Nagar, Chennai, Tamil Nadu, 600020', $event->location);

        // Formatted exactly as the card prints them.
        $this->assertSame('20 July, 2026', $event->formatted_date);
        $this->assertSame('10:00 AM', $event->formatted_time);
        $this->assertSame('10:00', $event->time_input_value);
    }

    public function test_a_time_posted_with_seconds_is_accepted(): void
    {
        // Some browsers post H:i:s from <input type="time">.
        $this->asAdmin()
            ->put(route('backend.events.update', $this->event()), [
                'speaker'    => 'Regina Phalange',
                'title'      => 'Nail your interviews',
                'event_time' => '16:30:00',
                'type'       => 'Live Event',
                'tone'       => 'teal',
                'is_active'  => 1,
                'show_home'  => 1,
            ])
            ->assertRedirect(route('backend.events.index'));

        $this->assertSame('4:30 PM', Event::firstOrFail()->formatted_time);
    }

    public function test_the_schedule_is_optional_and_clears_when_blanked(): void
    {
        $event = $this->event([
            'event_date' => '2026-07-20',
            'event_time' => '10:00',
            'location'   => 'Chennai',
        ]);

        $this->asAdmin()
            ->put(route('backend.events.update', $event), [
                'speaker'    => $event->speaker,
                'title'      => $event->title,
                'event_date' => '',
                'event_time' => '',
                'location'   => '',
                'type'       => 'Live Event',
                'tone'       => 'teal',
                'is_active'  => 1,
                'show_home'  => 1,
            ])
            ->assertRedirect(route('backend.events.index'));

        $event->refresh();

        $this->assertNull($event->event_date);
        $this->assertNull($event->event_time);
        $this->assertNull($event->location);
        $this->assertNull($event->formatted_date);
        $this->assertNull($event->formatted_time);
    }

    public function test_an_invalid_time_is_rejected(): void
    {
        $this->asAdmin()
            ->put(route('backend.events.update', $this->event()), [
                'speaker'    => 'Regina Phalange',
                'title'      => 'Nail your interviews',
                'event_time' => 'half past ten',
                'type'       => 'Live Event',
                'tone'       => 'teal',
                'is_active'  => 1,
                'show_home'  => 1,
            ])
            ->assertSessionHasErrors('event_time');
    }

    public function test_the_home_card_prints_only_the_rows_that_are_filled(): void
    {
        // One event, date set but no time or location: exactly one row should render.
        $this->event(['event_date' => '2026-07-20']);

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertSame(1, substr_count($html, 'hm-ev-card__when-row'));
        $this->assertStringContainsString('20 July, 2026', $html);
    }

    public function test_a_card_with_no_schedule_renders_no_schedule_block(): void
    {
        $this->event();

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('hm-ev-card__title', $html);   // the card is there
        $this->assertStringNotContainsString('hm-ev-card__when', $html); // but no schedule
    }

    public function test_the_card_shows_date_and_time_but_never_the_location(): void
    {
        $this->event([
            'event_date' => '2026-07-20',
            'event_time' => '10:00',
            'location'   => 'Plot 456, T. Nagar, Chennai',
        ]);

        $html = $this->get('/')->assertOk()->getContent();

        // Two rows, not three: a full venue address needs two or three lines and
        // crowded the speaker photo, so it is kept for the event details page.
        $this->assertSame(2, substr_count($html, 'hm-ev-card__when-row'));
        $this->assertStringContainsString('20 July, 2026', $html);
        $this->assertStringContainsString('10:00 AM', $html);
        $this->assertStringNotContainsString('Plot 456, T. Nagar, Chennai', $html);
    }

    public function test_the_location_is_still_stored_and_editable(): void
    {
        // It is off the card, not out of the system — the admin still sets it.
        $event = $this->event();

        $this->asAdmin()
            ->put(route('backend.events.update', $event), [
                'speaker'   => $event->speaker,
                'title'     => $event->title,
                'location'  => 'Plot 456, T. Nagar, Chennai, Tamil Nadu, 600020',
                'type'      => 'Live Event',
                'tone'      => 'teal',
                'is_active' => 1,
                'show_home' => 1,
            ])
            ->assertRedirect(route('backend.events.index'));

        $this->assertSame('Plot 456, T. Nagar, Chennai, Tamil Nadu, 600020', $event->fresh()->location);
    }
}
