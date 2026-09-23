<?php

namespace Tests\Feature;

use App\Mail\AdminSubmissionNotification;
use App\Mail\ContactEnquiryThankYou;
use App\Models\ContactEnquiry;
use App\Models\Course;
use App\Models\CourseEnquiry;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Every submission is copied to the admin, and the copy carries what was
 * actually sent — the visitor's thank-you already worked and is untouched.
 */
class AdminSubmissionEmailTest extends TestCase
{
    use RefreshDatabase;

    private const ADMIN = 'admin@hireminds.test';

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        Setting::putMany(['contact_email' => self::ADMIN]);
    }

    /* ============================== CONTACT ================================ */

    private function contactPayload(array $overrides = []): array
    {
        return array_merge([
            'name'        => 'Asha Kumar',
            'email'       => 'asha@example.com',
            'phone'       => '9876543210',
            'looking_for' => 'Course details',
            'interest'    => 'Full Stack Development',
            'message'     => 'Please call me back after 6pm.',
        ], $overrides);
    }

    public function test_a_contact_enquiry_emails_the_admin_with_the_details(): void
    {
        $this->post(route('frontend.contact-enquiry.store'), $this->contactPayload());

        $enquiry = ContactEnquiry::firstOrFail();

        Mail::assertSent(AdminSubmissionNotification::class, function ($mail) use ($enquiry) {
            $rendered = $mail->render();

            return $mail->hasTo(self::ADMIN)
                && $mail->submission->is($enquiry)
                && str_contains($rendered, 'Asha Kumar')
                && str_contains($rendered, 'asha@example.com')
                && str_contains($rendered, '9876543210')
                && str_contains($rendered, 'Course details')
                && str_contains($rendered, 'Full Stack Development')
                && str_contains($rendered, 'Please call me back after 6pm.');
        });
    }

    /** The visitor's own thank-you is untouched by any of this. */
    public function test_the_visitor_still_gets_the_thank_you(): void
    {
        $this->post(route('frontend.contact-enquiry.store'), $this->contactPayload());

        Mail::assertSent(ContactEnquiryThankYou::class, fn ($mail) => $mail->hasTo('asha@example.com'));
        Mail::assertSent(AdminSubmissionNotification::class, fn ($mail) => $mail->hasTo(self::ADMIN));
    }

    /** The AJAX path returns before the redirect — the admin is told there too. */
    public function test_a_json_submission_also_emails_the_admin(): void
    {
        $this->postJson(route('frontend.contact-enquiry.store'), $this->contactPayload())
            ->assertOk()
            ->assertJson(['success' => true]);

        Mail::assertSent(AdminSubmissionNotification::class, fn ($mail) => $mail->hasTo(self::ADMIN));
    }

    /** Replying to the notification answers the person, not the site's inbox. */
    public function test_the_admin_copy_replies_to_the_sender(): void
    {
        $this->post(route('frontend.contact-enquiry.store'), $this->contactPayload());

        Mail::assertSent(AdminSubmissionNotification::class, fn ($mail) => $mail->hasReplyTo('asha@example.com'));
    }

    /** An unanswered optional question is left out, not printed empty. */
    public function test_blank_fields_are_left_out_of_the_admin_copy(): void
    {
        $this->post(route('frontend.contact-enquiry.store'), $this->contactPayload([
            'looking_for' => null, 'interest' => null, 'message' => null,
        ]));

        Mail::assertSent(AdminSubmissionNotification::class, function ($mail) {
            $rendered = $mail->render();

            return str_contains($rendered, 'Asha Kumar')
                && ! str_contains($rendered, 'Looking for')
                && ! str_contains($rendered, 'Interested in');
        });
    }

    /* =============================== COURSE ================================ */

    public function test_a_course_enquiry_emails_the_admin_with_the_details(): void
    {
        $course = Course::create([
            'name' => 'Full Stack Development', 'slug' => 'full-stack-development',
            'image' => 'assets/images/courses/c1.webp', 'is_active' => true,
        ]);

        $this->post(route('frontend.course-enquiry.store'), [
            'course_id'   => $course->id,
            'name'        => 'Ravi Shankar',
            'email'       => 'ravi@example.com',
            'phone'       => '9123456780',
            'city'        => 'Coimbatore',
            'career_goal' => 'Switch to development',
            'batch'       => 'Weekend — 12 Oct',
        ]);

        $this->assertSame(1, CourseEnquiry::count());

        Mail::assertSent(AdminSubmissionNotification::class, function ($mail) {
            $rendered = $mail->render();

            return $mail->hasTo(self::ADMIN)
                && str_contains($rendered, 'New Course Enquiry')
                && str_contains($rendered, 'Ravi Shankar')
                && str_contains($rendered, 'ravi@example.com')
                && str_contains($rendered, '9123456780')
                && str_contains($rendered, 'Coimbatore')
                && str_contains($rendered, 'Switch to development')
                && str_contains($rendered, 'Weekend')
                && str_contains($rendered, 'Full Stack Development');   // course snapshot
        });
    }

    /* ================================ EVENT ================================= */

    public function test_an_event_registration_emails_the_admin_with_the_details(): void
    {
        $event = Event::create([
            'speaker' => 'Rochelle Fernandez', 'title' => 'AI Bootcamp 2026',
            'type' => 'Live Event', 'tone' => 'purple',
            'image' => 'assets/images/events/speaker.webp', 'is_active' => true,
        ]);

        $this->post(route('frontend.event-registration.store'), [
            'event_id'            => $event->id,
            'name'                => 'Alex Johnson',
            'email'               => 'alex@example.com',
            'phone'               => '9876543210',
            'city'                => 'Chennai',
            'professional_status' => 'Student',
            'organisation'        => 'Anna University',
            'agreed_terms'        => 1,
        ]);

        $this->assertSame(1, EventRegistration::count());

        Mail::assertSent(AdminSubmissionNotification::class, function ($mail) {
            $rendered = $mail->render();

            return $mail->hasTo(self::ADMIN)
                && str_contains($rendered, 'New Event Registration')
                && str_contains($rendered, 'Alex Johnson')
                && str_contains($rendered, 'alex@example.com')
                && str_contains($rendered, 'Anna University')
                && str_contains($rendered, 'AI Bootcamp 2026')        // event snapshot
                && str_contains($rendered, 'Accepted the terms');
        });
    }

    /* ============================= THE ROWS ================================= */

    /**
     * The rows are read off the record, so a column added to an enquiry later
     * appears in the email without this code being touched.
     */
    public function test_the_rows_follow_the_record_not_a_hard_coded_list(): void
    {
        $enquiry = ContactEnquiry::create([
            'name' => 'Asha', 'email' => 'asha@example.com', 'phone' => '9876543210',
            'message' => 'Hello', 'status' => 'New', 'ip_address' => '203.0.113.9',
        ]);

        $labels = array_column($enquiry->adminEmailRows(), 'label');

        $this->assertSame(['Name', 'Email', 'Phone', 'Message'], $labels);

        // Housekeeping columns are the panel's business, not the reader's.
        foreach (['Status', 'Ip Address', 'Id', 'Created At', 'Is Read'] as $internal) {
            $this->assertNotContains($internal, $labels);
        }
    }

    /** Values are readable: a tick-box is Yes, not 1. */
    public function test_values_are_rendered_for_a_person(): void
    {
        $event = Event::create([
            'speaker' => 'R F', 'title' => 'AI Bootcamp', 'type' => 'Live Event',
            'tone' => 'purple', 'image' => 'x.webp', 'is_active' => true,
        ]);

        $registration = EventRegistration::create([
            'event_id' => $event->id, 'event_title' => $event->title,
            'name' => 'Alex', 'email' => 'alex@example.com', 'phone' => '9876543210',
            'agreed_terms' => true, 'status' => 'New',
        ]);

        $rows = collect($registration->adminEmailRows())->keyBy('label');

        $this->assertSame('Yes', $rows['Accepted the terms']['value']);
        $this->assertSame('AI Bootcamp', $rows['Event']['value']);
        $this->assertArrayNotHasKey('Event Id', $rows->all());   // the snapshot is shown instead
    }

    /* ============================= RECIPIENTS =============================== */

    /** The addresses typed on Settings -> Email win, and both are copied. */
    public function test_the_enquiry_addresses_from_the_settings_page_are_used(): void
    {
        Setting::putMany([
            'enquiry_mail_to'   => 'enquiries@hireminds.test',
            'enquiry_mail_to_2' => 'hr@hireminds.test',
            'contact_email'     => 'public@hireminds.test',
        ]);

        $this->assertSame(
            ['enquiries@hireminds.test', 'hr@hireminds.test'],
            Setting::adminNotificationRecipients(),
        );

        $this->post(route('frontend.contact-enquiry.store'), $this->contactPayload());

        Mail::assertSent(AdminSubmissionNotification::class, fn ($mail) => $mail->hasTo('enquiries@hireminds.test')
            && $mail->hasTo('hr@hireminds.test')
            && ! $mail->hasTo('public@hireminds.test'));
    }

    /** The second box is optional. */
    public function test_only_the_first_enquiry_address_is_needed(): void
    {
        Setting::putMany(['enquiry_mail_to' => 'enquiries@hireminds.test', 'enquiry_mail_to_2' => '']);

        $this->assertSame(['enquiries@hireminds.test'], Setting::adminNotificationRecipients());
    }

    /** The settings screen saves them, and refuses a mistyped address. */
    public function test_the_settings_page_saves_the_enquiry_addresses(): void
    {
        $admin = User::factory()->create(['is_super_admin' => true]);
        $signedIn = $this->withSession([
            'admin_logged_in' => true, 'admin_id' => $admin->id,
            'admin_name' => $admin->name, 'admin_email' => $admin->email,
        ]);

        $signedIn->get(route('backend.settings.email'))
            ->assertOk()
            ->assertSee('Enquiry Mail Address')
            ->assertSee('name="enquiry_mail_to"', false)
            ->assertSee('name="enquiry_mail_to_2"', false);

        $signedIn->put(route('backend.settings.email.update'), [
            'mail_mailer'       => 'smtp',
            'enquiry_mail_to'   => 'enquiries@hireminds.test',
            'enquiry_mail_to_2' => 'hr@hireminds.test',
        ])->assertRedirect()->assertSessionHasNoErrors();

        Setting::putMany([]);   // drop the request cache
        $this->assertSame('enquiries@hireminds.test', Setting::get('enquiry_mail_to'));
        $this->assertSame('hr@hireminds.test', Setting::get('enquiry_mail_to_2'));

        $signedIn->put(route('backend.settings.email.update'), [
            'mail_mailer'     => 'smtp',
            'enquiry_mail_to' => 'not-an-address',
        ])->assertSessionHasErrors('enquiry_mail_to');
    }

    /** An install where nobody has filled the new fields in keeps working. */
    public function test_the_admin_address_falls_back_through_the_settings(): void
    {
        Setting::putMany(['enquiry_mail_to' => '', 'enquiry_mail_to_2' => '']);
        $this->assertSame([self::ADMIN], Setting::adminNotificationRecipients());   // contact_email

        Setting::putMany(['contact_email' => '', 'mail_from_address' => 'from@hireminds.test']);
        $this->assertSame(['from@hireminds.test'], Setting::adminNotificationRecipients());

        Setting::putMany(['mail_from_address' => '']);
        config()->set('mail.from.address', 'env@hireminds.test');
        $this->assertSame(['env@hireminds.test'], Setting::adminNotificationRecipients());

        config()->set('mail.from.address', null);
        $this->assertSame([], Setting::adminNotificationRecipients());
    }

    /** With nobody to tell, the submission is still saved and the visitor thanked. */
    public function test_a_submission_survives_having_no_admin_address(): void
    {
        Setting::putMany(['contact_email' => '', 'mail_from_address' => '']);
        config()->set('mail.from.address', null);

        $this->post(route('frontend.contact-enquiry.store'), $this->contactPayload())
            ->assertSessionHasNoErrors();

        $this->assertSame(1, ContactEnquiry::count());
        Mail::assertNotSent(AdminSubmissionNotification::class);
    }
}
