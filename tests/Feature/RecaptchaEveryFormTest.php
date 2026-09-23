<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseEnquiry;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\Form;
use App\Models\FormResponse;
use App\Models\Setting;
use App\Models\User;
use App\Services\FormBuilderService;
use App\Support\FormFieldType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Every form the website offers is behind reCAPTCHA: the contact enquiry, the
 * course enquiry modal, the event registration modal and any form built in the
 * Forms module.
 *
 * Run under **v2**, the site's own setting: the visitor ticks "I'm not a robot"
 * and Google answers success or failure, with no score and no action.
 * ContactRecaptchaTest covers v3's extras once.
 */
class RecaptchaEveryFormTest extends TestCase
{
    use RefreshDatabase;

    private const VERIFY = 'https://www.google.com/recaptcha/api/siteverify';

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();

        // Setting caches its rows in a static for the life of the request, and
        // a test process is one long life: without this, keys saved by one test
        // are still cached when the next one starts with an empty database.
        Setting::putMany([]);

        config()->set('services.recaptcha.version', 'v2');
        config()->set('services.recaptcha.site_key', 'site-key-for-tests');
        config()->set('services.recaptcha.secret_key', 'secret-key-for-tests');
    }

    /** A ticked box that Google accepts. v2 answers carry no score. */
    private function person(string $action = ''): void
    {
        Http::fake([self::VERIFY => Http::response(['success' => true, 'hostname' => 'localhost'])]);
    }

    private function bot(): void
    {
        Http::fake([self::VERIFY => Http::response(['success' => false, 'error-codes' => ['invalid-input-response']])]);
    }

    private function course(): Course
    {
        return Course::create([
            'name' => 'Full Stack Development', 'slug' => 'full-stack-development',
            'image' => 'assets/images/courses/c1.webp', 'is_active' => true,
        ]);
    }

    private function event(): Event
    {
        return Event::create([
            'speaker' => 'Rochelle Fernandez', 'title' => 'AI Bootcamp 2026', 'type' => 'Live Event',
            'tone' => 'purple', 'image' => 'assets/images/events/speaker.webp', 'is_active' => true,
        ]);
    }

    private function publishedForm(): Form
    {
        return app(FormBuilderService::class)->save([
            'name' => 'Job Application', 'status' => Form::PUBLISHED,
            'submit_label' => 'Apply', 'success_message' => 'Thanks.',
            'fields' => [['field_type' => FormFieldType::SHORT_TEXT, 'label' => 'Full Name', 'is_required' => 1]],
        ]);
    }

    private function coursePayload(Course $course, array $overrides = []): array
    {
        return array_merge([
            'course_id' => $course->id, 'name' => 'Ravi Shankar', 'email' => 'ravi@example.com',
            'phone' => '9123456780', 'g-recaptcha-response' => 'a-token',
        ], $overrides);
    }

    private function eventPayload(Event $event, array $overrides = []): array
    {
        return array_merge([
            'event_id' => $event->id, 'name' => 'Alex Johnson', 'email' => 'alex@example.com',
            'phone' => '9876543210', 'agreed_terms' => 1, 'g-recaptcha-response' => 'a-token',
        ], $overrides);
    }

    /* ========================== COURSE ENQUIRY ============================= */

    public function test_a_person_can_send_a_course_enquiry(): void
    {
        $this->person('course_enquiry');
        $course = $this->course();

        $this->postJson(route('frontend.course-enquiry.store'), $this->coursePayload($course))
            ->assertOk()->assertJson(['success' => true]);

        $this->assertSame(1, CourseEnquiry::count());
    }

    public function test_a_bot_cannot_send_a_course_enquiry(): void
    {
        $this->bot();
        $course = $this->course();

        $this->postJson(route('frontend.course-enquiry.store'), $this->coursePayload($course))
            ->assertStatus(422)
            ->assertJsonValidationErrors('g-recaptcha-response');

        $this->assertSame(0, CourseEnquiry::count());
        Mail::assertNothingSent();
    }

    /** The token is proof, not an answer — it must not reach the record. */
    public function test_the_course_enquiry_does_not_store_the_token(): void
    {
        $this->person('course_enquiry');
        $course = $this->course();

        $this->postJson(route('frontend.course-enquiry.store'), $this->coursePayload($course));

        foreach (CourseEnquiry::first()->getAttributes() as $value) {
            $this->assertNotSame('a-token', $value);
        }
    }

    public function test_the_course_modal_carries_the_widget(): void
    {
        $page = $this->get(route('frontend.course-details', $this->course()->slug))->assertOk()->getContent();

        $this->assertStringContainsString('class="g-recaptcha" data-sitekey="site-key-for-tests"', $page);
        // The checkbox itself is drawn by Google inside its iframe; what the
        // page carries is the div it draws into, and our own prompt.
        $this->assertStringContainsString('Please tick the box to confirm you are not a robot.', $page);
    }

    /* ======================== EVENT REGISTRATION =========================== */

    public function test_a_person_can_register_for_an_event(): void
    {
        $this->person('event_registration');
        $event = $this->event();

        $this->postJson(route('frontend.event-registration.store'), $this->eventPayload($event))
            ->assertOk()->assertJson(['success' => true]);

        $this->assertSame(1, EventRegistration::count());
    }

    public function test_a_bot_cannot_register_for_an_event(): void
    {
        $this->bot();
        $event = $this->event();

        $this->postJson(route('frontend.event-registration.store'), $this->eventPayload($event))
            ->assertStatus(422)
            ->assertJsonValidationErrors('g-recaptcha-response');

        $this->assertSame(0, EventRegistration::count());
        Mail::assertNothingSent();
    }

    public function test_the_event_modal_carries_the_widget(): void
    {
        $page = $this->get(route('frontend.event-details', $this->event()->slug))->assertOk()->getContent();

        $this->assertStringContainsString('class="g-recaptcha" data-sitekey="site-key-for-tests"', $page);
    }

    /* =========================== DYNAMIC FORMS ============================= */

    public function test_a_person_can_submit_a_built_form(): void
    {
        $this->person('dynamic_form');
        $form = $this->publishedForm();

        $this->from(route('frontend.form.show', $form->slug))
            ->post(route('frontend.form.submit', $form->slug), [
                'full_name' => 'Arun Kumar', 'g-recaptcha-response' => 'a-token',
            ])
            ->assertRedirect();

        $this->assertSame(1, FormResponse::count());
    }

    public function test_a_bot_cannot_submit_a_built_form(): void
    {
        $this->bot();
        $form = $this->publishedForm();

        $this->from(route('frontend.form.show', $form->slug))
            ->post(route('frontend.form.submit', $form->slug), [
                'full_name' => 'Arun Kumar', 'g-recaptcha-response' => 'a-token',
            ])
            ->assertRedirect(route('frontend.form.show', $form->slug))
            ->assertSessionHas('form_error');

        $this->assertSame(0, FormResponse::count());
    }

    /** What the visitor typed survives the refusal, so nothing is retyped. */
    public function test_a_refused_form_keeps_what_was_typed(): void
    {
        $this->bot();
        $form = $this->publishedForm();

        $this->from(route('frontend.form.show', $form->slug))
            ->post(route('frontend.form.submit', $form->slug), [
                'full_name' => 'Arun Kumar', 'g-recaptcha-response' => 'a-token',
            ])
            ->assertSessionHasInput('full_name', 'Arun Kumar');
    }

    /** The token is not one of the form's questions, so it is not an answer. */
    public function test_the_token_is_not_stored_as_an_answer(): void
    {
        $this->person('dynamic_form');
        $form = $this->publishedForm();

        $this->post(route('frontend.form.submit', $form->slug), [
            'full_name' => 'Arun Kumar', 'g-recaptcha-response' => 'a-token',
        ]);

        $values = FormResponse::first()->values;

        $this->assertCount(1, $values);
        $this->assertSame('full_name', $values->first()->field_key);
    }

    public function test_the_form_page_carries_the_widget(): void
    {
        $form = $this->publishedForm();

        $page = $this->get(route('frontend.form.show', $form->slug))->assertOk()->getContent();

        $this->assertStringContainsString('class="g-recaptcha" data-sitekey="site-key-for-tests"', $page);
        // The checkbox itself is drawn by Google inside its iframe; what the
        // page carries is the div it draws into, and our own prompt.
        $this->assertStringContainsString('Please tick the box to confirm you are not a robot.', $page);
    }

    /**
     * The admin's preview is a rehearsal by somebody already signed in and it
     * stores nothing, so it must not be asked to prove it is a person.
     */
    public function test_the_admin_preview_is_not_behind_recaptcha(): void
    {
        $form = $this->publishedForm();
        $admin = \App\Models\User::factory()->create(['is_super_admin' => true]);

        $page = $this->withSession([
            'admin_logged_in' => true, 'admin_id' => $admin->id,
            'admin_name' => $admin->name, 'admin_email' => $admin->email,
        ])->get(route('backend.forms.preview', $form))->assertOk()->getContent();

        $this->assertStringNotContainsString('g-recaptcha', $page);
    }

    /* ============================== THE V2 RULES ============================ */

    /**
     * A v2 answer is a ticked box: Google returns no score and no action, so
     * neither may be judged. Scoring a v2 answer would refuse every visitor,
     * since the missing score reads as 0.
     */
    public function test_a_ticked_box_is_accepted_without_a_score(): void
    {
        Http::fake([self::VERIFY => Http::response(['success' => true])]);   // no score, no action
        $course = $this->course();

        $this->postJson(route('frontend.course-enquiry.store'), $this->coursePayload($course))->assertOk();

        $this->assertSame(1, CourseEnquiry::count());
    }

    /** And the action is not checked either — v2 tokens do not carry one. */
    public function test_a_ticked_box_is_not_judged_on_an_action(): void
    {
        Http::fake([self::VERIFY => Http::response(['success' => true, 'action' => 'something_else'])]);
        $course = $this->course();

        $this->postJson(route('frontend.course-enquiry.store'), $this->coursePayload($course))->assertOk();

        $this->assertSame(1, CourseEnquiry::count());
    }

    /** The version decides what the page renders; nothing else changes. */
    public function test_the_version_switches_the_widget(): void
    {
        $page = $this->get(route('frontend.contact-us'))->assertOk()->getContent();

        $this->assertStringContainsString('class="g-recaptcha"', $page);
        $this->assertStringContainsString('recaptcha/api.js', $page);
        $this->assertStringNotContainsString('api.js?render=', $page, 'that is the invisible build');
        // Google's widget supplies the response field itself, so the page must
        // not also render an input of the same name - two fields with one name
        // would post the empty one over the answer.
        $this->assertStringNotContainsString('<input type="hidden" name="g-recaptcha-response"', $page);
        $this->assertStringNotContainsString('data-recaptcha-action', $page);

        config()->set('services.recaptcha.version', 'v3');

        $page = $this->get(route('frontend.contact-us'))->assertOk()->getContent();

        $this->assertStringContainsString('api.js?render=site-key-for-tests', $page);
        $this->assertStringContainsString('<input type="hidden" name="g-recaptcha-response"', $page);
        $this->assertStringNotContainsString('class="g-recaptcha"', $page);
    }

    /* ============================ THE SETTINGS PAGE ========================= */

    private function signedInAdmin(): self
    {
        $admin = User::factory()->create(['is_super_admin' => true]);

        return $this->withSession([
            'admin_logged_in' => true, 'admin_id' => $admin->id,
            'admin_name' => $admin->name, 'admin_email' => $admin->email,
        ]);
    }

    public function test_the_settings_page_offers_the_two_key_fields(): void
    {
        $page = $this->signedInAdmin()->get(route('backend.settings.recaptcha'))->assertOk()->getContent();

        $this->assertStringContainsString('name="recaptcha_site_key"', $page);
        $this->assertStringContainsString('name="recaptcha_secret_key"', $page);
    }

    /**
     * Typed in the panel, and the forms pick them up: the keys live in the
     * database so each site holds its own and a deploy never carries them.
     */
    public function test_keys_typed_in_the_panel_put_the_box_on_the_forms(): void
    {
        // Nothing configured anywhere to begin with.
        config()->set('services.recaptcha.site_key', null);
        config()->set('services.recaptcha.secret_key', null);
        Setting::putMany(['recaptcha_site_key' => '', 'recaptcha_secret_key' => '']);

        $this->assertStringNotContainsString(
            'g-recaptcha',
            $this->get(route('frontend.contact-us'))->assertOk()->getContent(),
            'with no keys there should be no widget at all',
        );

        $this->signedInAdmin()->put(route('backend.settings.recaptcha.update'), [
            'recaptcha_site_key'   => 'site-key-from-the-panel',
            'recaptcha_secret_key' => 'secret-key-from-the-panel',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $page = $this->get(route('frontend.contact-us'))->assertOk()->getContent();

        $this->assertStringContainsString('data-sitekey="site-key-from-the-panel"', $page);
        $this->assertStringNotContainsString('secret-key-from-the-panel', $page, 'the secret must never be rendered');
    }

    /** The panel wins over the server's .env. */
    public function test_the_panel_key_overrides_the_env_key(): void
    {
        config()->set('services.recaptcha.site_key', 'key-from-env');
        config()->set('services.recaptcha.secret_key', 'secret-from-env');

        Setting::putMany([
            'recaptcha_site_key'   => 'key-from-the-panel',
            'recaptcha_secret_key' => 'secret-from-the-panel',
        ]);

        $page = $this->get(route('frontend.contact-us'))->assertOk()->getContent();

        $this->assertStringContainsString('data-sitekey="key-from-the-panel"', $page);
        $this->assertStringNotContainsString('key-from-env', $page);
    }

    /** Blank in the panel falls back to .env rather than switching it off. */
    public function test_a_blank_panel_key_falls_back_to_the_env(): void
    {
        config()->set('services.recaptcha.site_key', 'key-from-env');
        config()->set('services.recaptcha.secret_key', 'secret-from-env');
        Setting::putMany(['recaptcha_site_key' => '', 'recaptcha_secret_key' => '']);

        $this->assertStringContainsString(
            'data-sitekey="key-from-env"',
            $this->get(route('frontend.contact-us'))->assertOk()->getContent(),
        );
    }

    /** Saving without retyping the secret keeps the stored one. */
    public function test_leaving_the_secret_blank_keeps_it(): void
    {
        Setting::putMany(['recaptcha_secret_key' => 'the-saved-secret']);

        $this->signedInAdmin()->put(route('backend.settings.recaptcha.update'), [
            'recaptcha_site_key'   => 'a-new-site-key',
            'recaptcha_secret_key' => '',
        ])->assertSessionHasNoErrors();

        Setting::putMany([]);   // drop the request cache

        $this->assertSame('the-saved-secret', Setting::get('recaptcha_secret_key'));
        $this->assertSame('a-new-site-key', Setting::get('recaptcha_site_key'));
    }

    /** A key pasted with a stray space still works. */
    public function test_a_pasted_key_is_trimmed(): void
    {
        $this->signedInAdmin()->put(route('backend.settings.recaptcha.update'), [
            'recaptcha_site_key'   => '  spaced-site-key  ',
            'recaptcha_secret_key' => '  spaced-secret  ',
        ])->assertSessionHasNoErrors();

        Setting::putMany([]);

        $this->assertSame('spaced-site-key', Setting::get('recaptcha_site_key'));
        $this->assertSame('spaced-secret', Setting::get('recaptcha_secret_key'));
    }

    /** Settings are behind the admin guard, like every other settings screen. */
    public function test_the_keys_are_not_editable_by_a_stranger(): void
    {
        $this->get(route('backend.settings.recaptcha'))->assertRedirect();

        $this->put(route('backend.settings.recaptcha.update'), [
            'recaptcha_site_key' => 'someone-elses-key',
        ])->assertRedirect();

        $this->assertNotSame('someone-elses-key', Setting::get('recaptcha_site_key'));
    }

    /* ============================= WHERE IT SITS ============================ */

    /**
     * Above the submit button, on every form. Asserted on the markup order,
     * which is what decides it — nothing here is positioned by CSS.
     */
    public function test_the_checkbox_comes_before_the_submit_button(): void
    {
        $course = $this->course();
        $event  = $this->event();
        $form   = $this->publishedForm();

        $pages = [
            'contact'  => [route('frontend.contact-us'), 'hm-contact__submit'],
            'course'   => [route('frontend.course-details', $course->slug), 'hm-enq__submit'],
            'event'    => [route('frontend.event-details', $event->slug), 'hm-reg__submit'],
            'the form' => [route('frontend.form.show', $form->slug), 'hmf__submit'],
        ];

        foreach ($pages as $which => [$url, $submitClass]) {
            $html = $this->get($url)->assertOk()->getContent();

            $widget = strpos($html, 'class="g-recaptcha"');
            $button = strpos($html, $submitClass);

            $this->assertNotFalse($widget, "no widget on {$which}");
            $this->assertNotFalse($button, "no submit button on {$which}");
            $this->assertLessThan(
                $button, $widget,
                "the checkbox must come before the submit button on {$which}",
            );
        }
    }

    /**
     * On a stepped form it lives INSIDE the row the script reveals on the last
     * step, so page one never shows a tick box with no Submit beside it.
     */
    public function test_on_a_stepped_form_it_sits_inside_the_revealed_row(): void
    {
        $form = app(FormBuilderService::class)->save([
            'name' => 'Two Pager', 'status' => Form::PUBLISHED,
            'submit_label' => 'Submit', 'success_message' => 'Thanks.',
            'structure_type' => Form::PAGES,
            'pages' => ['p1' => ['title' => 'One'], 'p2' => ['title' => 'Two']],
            'fields' => [
                ['field_type' => FormFieldType::SHORT_TEXT, 'label' => 'A', 'is_required' => 1, 'page_ref' => 'p1'],
                ['field_type' => FormFieldType::SHORT_TEXT, 'label' => 'B', 'is_required' => 1, 'page_ref' => 'p2'],
            ],
        ]);

        $html = $this->get(route('frontend.form.show', $form->slug))->assertOk()->getContent();

        $row = strpos($html, 'data-submit-row');
        $this->assertNotFalse($row, 'no submit row');

        $widget = strpos($html, 'class="g-recaptcha"');
        $rowEnd = strpos($html, 'hmf__submit', $row);

        $this->assertGreaterThan($row, $widget, 'the widget must be inside the submit row');
        $this->assertLessThan($rowEnd, $widget, 'and still before the button');
    }

    /* ============================ ALL AT ONCE ============================== */

    /** Nothing on the site accepts a submission without a token. */
    public function test_no_form_accepts_a_submission_with_no_token(): void
    {
        Http::fake();
        $course = $this->course();
        $event  = $this->event();
        $form   = $this->publishedForm();

        $this->postJson(route('frontend.contact-enquiry.store'), [
            'name' => 'A', 'email' => 'a@example.com', 'phone' => '9876543210',
        ])->assertStatus(422);

        $this->postJson(route('frontend.course-enquiry.store'), $this->coursePayload($course, [
            'g-recaptcha-response' => null,
        ]))->assertStatus(422);

        $this->postJson(route('frontend.event-registration.store'), $this->eventPayload($event, [
            'g-recaptcha-response' => null,
        ]))->assertStatus(422);

        $this->from(route('frontend.form.show', $form->slug))
            ->post(route('frontend.form.submit', $form->slug), ['full_name' => 'Arun'])
            ->assertSessionHas('form_error');

        $this->assertSame(0, CourseEnquiry::count());
        $this->assertSame(0, EventRegistration::count());
        $this->assertSame(0, FormResponse::count());
    }

    /** With no keys, every form works exactly as it did before. */
    public function test_without_keys_every_form_still_works(): void
    {
        config()->set('services.recaptcha.site_key', null);
        config()->set('services.recaptcha.secret_key', null);
        Http::fake();

        $course = $this->course();
        $event  = $this->event();
        $form   = $this->publishedForm();

        $this->postJson(route('frontend.course-enquiry.store'), $this->coursePayload($course, [
            'g-recaptcha-response' => null,
        ]))->assertOk();

        $this->postJson(route('frontend.event-registration.store'), $this->eventPayload($event, [
            'g-recaptcha-response' => null,
        ]))->assertOk();

        $this->post(route('frontend.form.submit', $form->slug), ['full_name' => 'Arun'])->assertRedirect();

        $this->assertSame(1, CourseEnquiry::count());
        $this->assertSame(1, EventRegistration::count());
        $this->assertSame(1, FormResponse::count());
        Http::assertNothingSent();
    }
}
