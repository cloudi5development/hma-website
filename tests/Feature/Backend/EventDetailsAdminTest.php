<?php

namespace Tests\Feature\Backend;

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * The Events CRUD side of the details page: the extra columns, and the
 * Highlights / Speakers / FAQ repeaters (add, edit, delete, reorder, enable).
 */
class EventDetailsAdminTest extends TestCase
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

    /** The minimum a valid submit needs, plus whatever the test is testing. */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'speaker' => 'Rochelle Fernandez',
            'title'   => 'AI & Future Tech Bootcamp 2026',
            'type'    => 'Live Event',
            'tone'    => 'purple',
            'image'   => UploadedFile::fake()->image('speaker.jpg', 400, 400),
        ], $overrides);
    }

    public function test_the_form_ships_every_repeater_and_its_template(): void
    {
        $html = $this->signedIn()->get(route('backend.events.create'))->assertOk()->getContent();

        foreach (['highlights', 'speakers', 'event_faqs'] as $key) {
            $this->assertStringContainsString('data-repeater="' . $key . '"', $html, "$key repeater missing");
            $this->assertStringContainsString('id="' . $key . 'Template"', $html, "$key template missing");
            $this->assertStringContainsString('data-repeater-add="' . $key . '"', $html, "$key add button missing");
        }

        // The clone placeholder has to survive Blade, or "Add" produces rows
        // that all share one key and overwrite each other on submit.
        $this->assertStringContainsString('highlights[__I__][title]', $html);
        $this->assertStringContainsString('speakers[__I__][name]', $html);
        $this->assertStringContainsString('event_faqs[__I__][question]', $html);
    }

    public function test_the_form_exposes_the_new_detail_fields(): void
    {
        $html = $this->signedIn()->get(route('backend.events.create'))->assertOk()->getContent();

        foreach (['slug', 'short_description', 'description', 'end_time', 'venue', 'city', 'state',
                  'country', 'offer_price', 'discount_percentage', 'total_seats', 'available_seats',
                  'duration', 'language', 'level', 'organizer', 'banner_image', 'thumbnail',
                  'seo_title', 'seo_description', 'seo_keywords', 'og_image', 'schema_json'] as $field) {
            $this->assertMatchesRegularExpression(
                '/name="' . preg_quote($field, '/') . '"/',
                $html,
                "The form has no {$field} input."
            );
        }
    }

    public function test_storing_an_event_saves_the_detail_fields_and_all_three_repeaters(): void
    {
        Storage::fake('public');

        $this->signedIn()->post(route('backend.events.store'), $this->payload([
            'short_description'   => 'A one-day bootcamp.',
            'description'         => "Paragraph one.\n\nParagraph two.",
            'venue'               => 'Hire Minds Campus',
            'city'                => 'Chennai',
            'state'               => 'Tamil Nadu',
            'country'             => 'India',
            'event_time'          => '10:00',
            'end_time'            => '18:00',
            'price'               => '₹999/-',
            'offer_price'         => '₹499/-',
            'discount_percentage' => 50,
            'total_seats'         => 100,
            'available_seats'     => 25,
            'duration'            => '1 Day',
            'language'            => 'English',
            'level'               => 'Beginner',
            'organizer'           => 'Hire Minds Academy',
            'banner_image'        => UploadedFile::fake()->image('banner.jpg', 1920, 480),
            'highlights'          => [
                ['icon' => 'tech', 'title' => 'Hands-on practice', 'description' => 'Build as you learn.', 'is_active' => '1'],
                ['icon' => 'career', 'title' => 'Career guidance', 'is_active' => '1'],
            ],
            'speakers'   => [['name' => 'Rahul Sharma', 'designation' => 'AI Engineer', 'company' => 'Google', 'is_active' => '1']],
            'event_faqs' => [['question' => 'Is it beginner friendly?', 'answer' => 'Yes, entirely.', 'is_active' => '1']],
        ]))->assertRedirect(route('backend.events.index'));

        $event = Event::firstOrFail();

        $this->assertSame('ai-future-tech-bootcamp-2026', $event->slug);
        $this->assertSame('Chennai', $event->city);
        $this->assertSame('₹499/-', $event->payable_price);
        $this->assertSame(50, $event->discount_percentage);
        $this->assertSame(25, $event->seats_left);
        $this->assertSame('10:00 AM - 6:00 PM', $event->time_range);
        $this->assertStringStartsWith('storage/events/banners/', $event->banner_image);

        $this->assertCount(2, $event->highlights);
        $this->assertCount(1, $event->speakers);
        $this->assertCount(1, $event->faqs);
        $this->assertSame('Hands-on practice', $event->highlights->first()->title);
        $this->assertSame('tech', $event->highlights->first()->icon);
    }

    public function test_blank_repeater_rows_are_dropped_rather_than_stored(): void
    {
        Storage::fake('public');

        $this->signedIn()->post(route('backend.events.store'), $this->payload([
            'highlights' => [
                ['icon' => 'ai', 'title' => 'Real one', 'is_active' => '1'],
                ['icon' => 'ai', 'title' => '', 'description' => ''],   // the "Add" click nobody filled in
            ],
            'event_faqs' => [
                ['question' => 'Answered?', 'answer' => 'Yes.'],
                ['question' => 'Unanswered?', 'answer' => ''],   // a FAQ needs both halves
            ],
        ]))->assertRedirect();

        $event = Event::firstOrFail();

        $this->assertCount(1, $event->highlights);
        $this->assertCount(1, $event->faqs);
        $this->assertSame('Answered?', $event->faqs->first()->question);
    }

    public function test_the_submitted_order_becomes_the_display_order(): void
    {
        Storage::fake('public');

        $this->signedIn()->post(route('backend.events.store'), $this->payload([
            // Keys deliberately out of sequence: the repeater hands out n0, n1, …
            // and moving a row with the arrows changes its position, not its key.
            'highlights' => [
                'n4' => ['icon' => 'career', 'title' => 'Third'],
                'n0' => ['icon' => 'tech', 'title' => 'First'],
                'n2' => ['icon' => 'network', 'title' => 'Second'],
            ],
        ]))->assertRedirect();

        $this->assertSame(
            ['Third', 'First', 'Second'],
            Event::firstOrFail()->highlights->pluck('title')->all()
        );
    }

    public function test_updating_replaces_the_rows_and_honours_the_show_toggle(): void
    {
        Storage::fake('public');

        $this->signedIn()->post(route('backend.events.store'), $this->payload([
            'event_faqs' => [
                ['question' => 'Old one', 'answer' => 'Old answer', 'is_active' => '1'],
                ['question' => 'To be deleted', 'answer' => 'Gone', 'is_active' => '1'],
            ],
        ]))->assertRedirect();

        $event = Event::firstOrFail();

        $this->signedIn()->put(route('backend.events.update', $event), $this->payload([
            'image'      => null,
            'event_faqs' => [
                ['question' => 'Old one', 'answer' => 'Edited answer', 'is_active' => '1'],
                ['question' => 'Hidden one', 'answer' => 'Not shown yet', 'is_active' => '0'],
            ],
        ]))->assertRedirect(route('backend.events.index'));

        $faqs = $event->fresh()->faqs;

        $this->assertCount(2, $faqs);
        $this->assertSame('Edited answer', $faqs[0]->answer);
        $this->assertTrue($faqs[0]->is_active);
        $this->assertFalse($faqs[1]->is_active);
        $this->assertSame([0, 1], $faqs->pluck('display_order')->all());
    }

    public function test_a_speaker_keeps_its_photo_when_the_row_is_resaved_without_a_new_upload(): void
    {
        Storage::fake('public');

        $this->signedIn()->post(route('backend.events.store'), $this->payload([
            'speakers' => [['name' => 'Rahul Sharma', 'photo' => UploadedFile::fake()->image('rahul.jpg', 400, 400)]],
        ]))->assertRedirect();

        $event = Event::firstOrFail();
        $photo = $event->speakers->first()->photo;

        $this->assertStringStartsWith('storage/events/speakers/', $photo);
        Storage::disk('public')->assertExists(substr($photo, strlen('storage/')));

        // What the edit form posts back: the hidden existing_photo, no new file.
        $this->signedIn()->put(route('backend.events.update', $event), $this->payload([
            'image'    => null,
            'speakers' => [['name' => 'Rahul Sharma', 'existing_photo' => $photo, 'designation' => 'AI Engineer']],
        ]))->assertRedirect();

        $speaker = $event->fresh()->speakers->first();

        $this->assertSame($photo, $speaker->photo);
        $this->assertSame('AI Engineer', $speaker->designation);
        Storage::disk('public')->assertExists(substr($photo, strlen('storage/')));
    }

    public function test_a_forged_existing_photo_path_is_ignored(): void
    {
        Storage::fake('public');

        $this->signedIn()->post(route('backend.events.store'), $this->payload())->assertRedirect();

        $event = Event::firstOrFail();

        $this->signedIn()->put(route('backend.events.update', $event), $this->payload([
            'image'    => null,
            'speakers' => [['name' => 'Rahul Sharma', 'existing_photo' => 'storage/users/someone-elses-id.webp']],
        ]))->assertRedirect();

        // The row is stored, but the path this event never owned is not.
        $this->assertNull($event->fresh()->speakers->first()->photo);
    }

    public function test_removing_a_speaker_deletes_its_photo_from_disk(): void
    {
        Storage::fake('public');

        $this->signedIn()->post(route('backend.events.store'), $this->payload([
            'speakers' => [['name' => 'Rahul Sharma', 'photo' => UploadedFile::fake()->image('rahul.jpg', 400, 400)]],
        ]))->assertRedirect();

        $event = Event::firstOrFail();
        $photo = $event->speakers->first()->photo;

        $this->signedIn()->put(route('backend.events.update', $event), $this->payload([
            'image'    => null,
            'speakers' => [],
        ]))->assertRedirect();

        $this->assertCount(0, $event->fresh()->speakers);
        Storage::disk('public')->assertMissing(substr($photo, strlen('storage/')));
    }

    public function test_two_events_cannot_share_a_slug(): void
    {
        Storage::fake('public');

        $this->signedIn()->post(route('backend.events.store'), $this->payload(['slug' => 'ai-bootcamp']))->assertRedirect();

        $this->signedIn()
            ->post(route('backend.events.store'), $this->payload(['title' => 'Another', 'slug' => 'ai-bootcamp']))
            ->assertSessionHasErrors('slug');

        $this->assertSame(1, Event::count());
    }

    public function test_an_event_may_keep_its_own_slug_on_update(): void
    {
        Storage::fake('public');

        $this->signedIn()->post(route('backend.events.store'), $this->payload(['slug' => 'ai-bootcamp']))->assertRedirect();

        $event = Event::firstOrFail();

        $this->signedIn()
            ->put(route('backend.events.update', $event), $this->payload(['image' => null, 'slug' => 'ai-bootcamp']))
            ->assertSessionHasNoErrors();
    }

    public function test_invalid_schema_json_is_rejected(): void
    {
        Storage::fake('public');

        $this->signedIn()
            ->post(route('backend.events.store'), $this->payload(['schema_json' => '{not json']))
            ->assertSessionHasErrors('schema_json');
    }

    public function test_deleting_an_event_removes_its_rows_and_uploads(): void
    {
        Storage::fake('public');

        $this->signedIn()->post(route('backend.events.store'), $this->payload([
            'banner_image' => UploadedFile::fake()->image('banner.jpg', 1600, 400),
            'highlights'   => [['icon' => 'tech', 'title' => 'Practice']],
            'speakers'     => [['name' => 'Rahul', 'photo' => UploadedFile::fake()->image('r.jpg', 300, 300)]],
            'event_faqs'   => [['question' => 'Q?', 'answer' => 'A.']],
        ]))->assertRedirect();

        $event = Event::firstOrFail();
        $banner = $event->banner_image;
        $photo = $event->speakers->first()->photo;
        $id = $event->id;

        $this->signedIn()->delete(route('backend.events.destroy', $event))->assertRedirect();

        $this->assertDatabaseMissing('events', ['id' => $id]);
        $this->assertDatabaseCount('event_highlights', 0);
        $this->assertDatabaseCount('event_speakers', 0);
        $this->assertDatabaseCount('event_faqs', 0);
        Storage::disk('public')->assertMissing(substr($banner, strlen('storage/')));
        Storage::disk('public')->assertMissing(substr($photo, strlen('storage/')));
    }
}
