<?php

namespace Tests\Feature;

use App\Mail\EventRegistrationThankYou;
use App\Models\AdminNotification;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * The "Register for the Event" modal end to end: the form on the details page,
 * the stored record, the thank-you email, and the Leads → Event Registration
 * screens in the panel.
 */
class EventRegistrationTest extends TestCase
{
    use RefreshDatabase;

    private function event(array $overrides = []): Event
    {
        return Event::create(array_merge([
            'speaker'   => 'Rochelle Fernandez',
            'title'     => 'AI & Future Tech Bootcamp 2026',
            'type'      => 'Live Event',
            'tone'      => 'purple',
            'image'     => 'assets/images/events/speaker.webp',
            'is_active' => true,
            'show_home' => true,
        ], $overrides));
    }

    private function payload(Event $event, array $overrides = []): array
    {
        return array_merge([
            'event_id'            => $event->id,
            'name'                => 'Alex Johnson',
            'email'               => 'alex@example.com',
            'phone'               => '9876543210',
            'city'                => 'Chennai',
            'professional_status' => 'Student',
            'organisation'        => 'Anna University',
            'agreed_terms'        => '1',
        ], $overrides);
    }

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

    /* ============================== THE MODAL ============================== */

    public function test_the_details_page_ships_the_registration_modal(): void
    {
        $event = $this->event();

        $html = $this->get(route('frontend.event-details', $event->slug))->assertOk()->getContent();

        $this->assertStringContainsString('id="hmRegisterModal"', $html);
        $this->assertStringContainsString('Register for the Event', $html);
        $this->assertStringContainsString(route('frontend.event-registration.store'), $html);

        foreach (['name', 'phone', 'email', 'event_id', 'city', 'professional_status', 'organisation', 'agreed_terms'] as $field) {
            $this->assertStringContainsString('name="' . $field . '"', $html, "The modal has no {$field} input.");
        }

        // Every Register button opens it rather than navigating away.
        $this->assertSame(3, substr_count($html, 'data-bs-target="#hmRegisterModal"'));
        $this->assertStringContainsString('Company / College name', $html);
    }

    public function test_the_current_event_is_preselected_and_the_others_are_offered(): void
    {
        $event = $this->event();
        $other = $this->event(['title' => 'Second Event']);

        $html = $this->get(route('frontend.event-details', $event->slug))->assertOk()->getContent();

        $this->assertMatchesRegularExpression(
            '/<option value="' . $event->id . '" selected>/',
            $html,
            'The event being viewed should be pre-selected in the dropdown.'
        );
        $this->assertStringContainsString('value="' . $other->id . '"', $html);
    }

    public function test_an_event_with_its_own_link_skips_the_modal_entirely(): void
    {
        $event = $this->event(['link' => 'https://example.com/book']);

        $html = $this->get(route('frontend.event-details', $event->slug))->assertOk()->getContent();

        // The submit script names the modal either way; the markup is what must go.
        $this->assertStringNotContainsString('id="hmRegisterModal"', $html);
        $this->assertStringNotContainsString('data-bs-target="#hmRegisterModal"', $html);
        $this->assertStringContainsString('https://example.com/book', $html);
    }

    /* ============================== SUBMITTING ============================== */

    public function test_a_submission_is_stored_notified_and_thanked_by_email(): void
    {
        Mail::fake();
        $event = $this->event();

        $this->postJson(route('frontend.event-registration.store'), $this->payload($event))
            ->assertOk()
            ->assertJson(['success' => true]);

        $registration = EventRegistration::firstOrFail();

        $this->assertSame($event->id, $registration->event_id);
        $this->assertSame($event->title, $registration->event_title);   // snapshot
        $this->assertSame('Alex Johnson', $registration->name);
        $this->assertSame('Student', $registration->professional_status);
        $this->assertSame('Anna University', $registration->organisation);
        $this->assertTrue($registration->agreed_terms);
        $this->assertSame('New', $registration->status);
        $this->assertNotNull($registration->ip_address);

        Mail::assertSent(EventRegistrationThankYou::class, fn ($mail) => $mail->hasTo('alex@example.com'));

        $this->assertSame(1, AdminNotification::where('type', 'event')->count());
    }

    public function test_the_terms_box_is_required(): void
    {
        Mail::fake();
        $event = $this->event();

        $this->postJson(route('frontend.event-registration.store'), $this->payload($event, ['agreed_terms' => null]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('agreed_terms');

        $this->assertSame(0, EventRegistration::count());
        Mail::assertNothingSent();
    }

    public function test_name_email_and_phone_are_required(): void
    {
        $event = $this->event();

        $this->postJson(route('frontend.event-registration.store'), $this->payload($event, [
            'name' => '', 'email' => 'not-an-email', 'phone' => '',
        ]))->assertStatus(422)->assertJsonValidationErrors(['name', 'email', 'phone']);
    }

    public function test_an_unpublished_event_cannot_be_registered_for(): void
    {
        Mail::fake();
        $hidden = $this->event(['title' => 'Draft Event', 'is_active' => false]);

        // A direct POST must not be able to book a seat on a draft.
        $this->postJson(route('frontend.event-registration.store'), $this->payload($hidden))
            ->assertNotFound();

        $this->assertSame(0, EventRegistration::count());
    }

    public function test_a_mail_failure_still_keeps_the_registration(): void
    {
        $event = $this->event();

        Mail::shouldReceive('to')->andThrow(new \RuntimeException('SMTP down'));

        $this->postJson(route('frontend.event-registration.store'), $this->payload($event))
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertSame(1, EventRegistration::count());
    }

    /* =============================== THE PANEL =============================== */

    public function test_the_admin_list_shows_registrations(): void
    {
        Mail::fake();
        $event = $this->event();
        $this->postJson(route('frontend.event-registration.store'), $this->payload($event));

        $this->signedInAdmin()
            ->get(route('backend.event-registrations.index'))
            ->assertOk()
            ->assertSee('Event Registrations')
            ->assertSee('Alex Johnson')
            ->assertSee('AI &amp; Future Tech Bootcamp 2026', false);
    }

    public function test_the_admin_can_open_a_registration_and_change_its_status(): void
    {
        Mail::fake();
        $event = $this->event();
        $this->postJson(route('frontend.event-registration.store'), $this->payload($event));
        $registration = EventRegistration::firstOrFail();

        $this->signedInAdmin()
            ->get(route('backend.event-registrations.show', $registration))
            ->assertOk()
            ->assertSee('alex@example.com')
            ->assertSee('Anna University');

        $this->signedInAdmin()
            ->patch(route('backend.event-registrations.status', $registration), ['status' => 'Contacted'])
            ->assertRedirect();

        $this->assertSame('Contacted', $registration->fresh()->status);
    }

    public function test_the_admin_can_delete_a_registration(): void
    {
        Mail::fake();
        $event = $this->event();
        $this->postJson(route('frontend.event-registration.store'), $this->payload($event));
        $registration = EventRegistration::firstOrFail();

        $this->signedInAdmin()
            ->delete(route('backend.event-registrations.destroy', $registration))
            ->assertRedirect(route('backend.event-registrations.index'));

        $this->assertSame(0, EventRegistration::count());
    }

    public function test_deleting_the_event_keeps_the_registration_and_its_title(): void
    {
        Mail::fake();
        $event = $this->event();
        $this->postJson(route('frontend.event-registration.store'), $this->payload($event));

        $event->delete();

        $registration = EventRegistration::firstOrFail();

        $this->assertNull($registration->event_id);
        $this->assertSame('AI & Future Tech Bootcamp 2026', $registration->event_title);
        $this->signedInAdmin()->get(route('backend.event-registrations.show', $registration))
            ->assertOk()
            ->assertSee('(event removed)');
    }

    public function test_the_sidebar_offers_the_new_menu_entry(): void
    {
        // Not the dashboard: its charts call MONTH(), which sqlite has no
        // function for, so it cannot render under the test driver.
        $this->signedInAdmin()
            ->get(route('backend.event-registrations.index'))
            ->assertOk()
            ->assertSee('Event Registration')
            ->assertSee(route('backend.event-registrations.index'), false);
    }

    public function test_the_module_is_grantable_and_guarded(): void
    {
        $this->assertTrue(\App\Support\AdminModules::isGrantable('event-registrations'));
        $this->assertSame('event-registrations', \App\Support\AdminModules::forRoute('backend.event-registrations.index'));

        // A staff account without the module cannot reach the list.
        $staff = User::create([
            'name' => 'Staff', 'email' => 'staff-er@example.com', 'password' => 'password123',
            'is_active' => true, 'modules' => ['courses'],
        ]);

        $this->withSession(['admin_logged_in' => true, 'admin_id' => $staff->id])
            ->get(route('backend.event-registrations.index'))
            ->assertRedirect(route('backend.dashboard'));
    }

    public function test_the_page_loads_bootstrap_so_the_modal_can_open(): void
    {
        // Bootstrap's bundle is not in common-js — each page pushes it. Without
        // it data-bs-toggle does nothing and the Register buttons are inert
        // (as is the FAQ accordion, which is Bootstrap collapse).
        $this->get(route('frontend.event-details', $this->event()->slug))
            ->assertOk()
            ->assertSee('bootstrap.bundle.min.js', false);
    }

    public function test_a_placeholder_hash_link_still_opens_the_modal(): void
    {
        // "#" is the placeholder older events were seeded with. Treating it as a
        // real address turned every Register button into a dead link.
        $event = $this->event(['link' => '#']);

        $html = $this->get(route('frontend.event-details', $event->slug))->assertOk()->getContent();

        $this->assertStringContainsString('id="hmRegisterModal"', $html);
        $this->assertSame(3, substr_count($html, 'data-bs-target="#hmRegisterModal"'));
        $this->assertStringNotContainsString('class="hm-ed-btn hm-ed-btn--primary" href="#"', $html);
    }

    public function test_a_whitespace_only_link_is_treated_as_no_link(): void
    {
        $event = $this->event(['link' => '   ']);

        $this->get(route('frontend.event-details', $event->slug))
            ->assertOk()
            ->assertSee('data-bs-target="#hmRegisterModal"', false);
    }

    public function test_the_modal_scrolls_its_own_body_rather_than_being_clipped(): void
    {
        $html = $this->get(route('frontend.event-details', $this->event()->slug))->assertOk()->getContent();

        // Bootstrap's modal-dialog-scrollable only scrolls a .modal-body, which
        // this dialog does not have — using it clipped the form at the fold with
        // no way to reach the submit button. The scrolling is ours.
        $this->assertStringNotContainsString('modal-dialog-scrollable', $html);
        $this->assertStringContainsString('hm-reg__scroll', $html);

        // The submit button has to sit inside the scrolling region.
        $scroll = strpos($html, 'hm-reg__scroll');
        $submit = strpos($html, 'hm-reg__submit');
        $this->assertNotFalse($submit);
        $this->assertGreaterThan($scroll, $submit);
    }
}
