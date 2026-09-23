<?php

namespace Tests\Feature\Frontend;

use App\Models\Form;
use App\Models\FormResponse;
use App\Models\FormResponseValue;
use App\Models\Setting;
use App\Models\User;
use App\Services\FormBuilderService;
use App\Services\FormSubmissionService;
use App\Support\FormFieldType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * The public side: /forms/{slug} renders and stores any admin-built form.
 *
 * One route serves every form there will ever be, so these tests build forms
 * that have nothing in common and check that the same code path handles each.
 */
class PublicFormTest extends TestCase
{
    use RefreshDatabase;

    private function asAdmin(): self
    {
        $admin = User::where('is_super_admin', true)->firstOrFail();

        return $this->withSession(['admin_logged_in' => true, 'admin_id' => $admin->id]);
    }

    /** Build a form straight through the builder — the same path the panel uses. */
    private function form(array $fields, array $overrides = []): Form
    {
        return app(FormBuilderService::class)->save($overrides + [
            'name'            => 'Test Form',
            'title'           => 'Test Form',
            'description'     => null,
            'slug'            => 'test-form',
            'status'          => Form::PUBLISHED,
            'submit_label'    => 'Submit',
            'success_message' => 'Thank you! Your response has been submitted successfully.',
            'fields'          => $fields,
        ]);
    }

    private function field(array $overrides = []): array
    {
        return $overrides + ['field_type' => FormFieldType::SHORT_TEXT, 'label' => 'Name', 'is_required' => 0];
    }

    private function submit(Form $form, array $data = [])
    {
        return $this->post(route('frontend.form.submit', $form->slug), $data);
    }

    /* =============================== RENDERING ============================= */

    public function test_a_published_form_renders_its_own_title_description_and_button(): void
    {
        $form = $this->form([$this->field(['label' => 'Student Name'])], [
            'title'           => 'Enquire About Our Courses',
            'description'     => 'Please complete the form below.',
            'submit_label'    => 'Send Enquiry',
        ]);

        $this->get(route('frontend.form.show', $form->slug))
            ->assertOk()
            ->assertSee('Enquire About Our Courses')
            ->assertSee('Please complete the form below.')
            ->assertSee('Student Name')
            ->assertSee('Send Enquiry');
    }

    /** Every field type has to draw the control it claims to. */
    public function test_each_field_type_renders_its_own_control(): void
    {
        $form = $this->form([
            $this->field(['label' => 'Short', 'field_type' => FormFieldType::SHORT_TEXT]),
            $this->field(['label' => 'Long', 'field_type' => FormFieldType::LONG_TEXT]),
            $this->field(['label' => 'Mail', 'field_type' => FormFieldType::EMAIL]),
            $this->field(['label' => 'Mobile', 'field_type' => FormFieldType::MOBILE]),
            $this->field(['label' => 'Count', 'field_type' => FormFieldType::NUMBER]),
            $this->field(['label' => 'Day', 'field_type' => FormFieldType::DATE]),
            $this->field(['label' => 'Slot', 'field_type' => FormFieldType::TIME]),
            $this->field(['label' => 'When', 'field_type' => FormFieldType::DATETIME]),
            $this->field(['label' => 'Pick', 'field_type' => FormFieldType::DROPDOWN, 'options' => [['label' => 'A', 'value' => 'a']]]),
            $this->field(['label' => 'Choose', 'field_type' => FormFieldType::RADIO, 'options' => [['label' => 'B', 'value' => 'b']]]),
            $this->field(['label' => 'Tick', 'field_type' => FormFieldType::CHECKBOX, 'options' => [['label' => 'C', 'value' => 'c']]]),
            $this->field(['label' => 'Sure', 'field_type' => FormFieldType::YES_NO, 'options' => [['label' => 'Yes', 'value' => 'Yes'], ['label' => 'No', 'value' => 'No']]]),
            $this->field(['label' => 'Doc', 'field_type' => FormFieldType::FILE]),
            $this->field(['label' => 'Source', 'field_type' => FormFieldType::HIDDEN, 'default_value' => 'facebook']),
        ]);

        $html = $this->get(route('frontend.form.show', $form->slug))->assertOk()->getContent();

        $this->assertStringContainsString('name="short"', $html);
        $this->assertStringContainsString('<textarea class="hmf-control" id="hmf_long"', $html);
        $this->assertStringContainsString('type="email"', $html);
        $this->assertStringContainsString('type="tel"', $html);
        $this->assertStringContainsString('type="number"', $html);
        $this->assertStringContainsString('type="date"', $html);
        $this->assertStringContainsString('type="time"', $html);
        $this->assertStringContainsString('type="datetime-local"', $html);
        $this->assertStringContainsString('<select class="hmf-control hmf-select" id="hmf_pick"', $html);
        $this->assertStringContainsString('type="radio" id="hmf_choose_0"', $html);
        $this->assertStringContainsString('type="checkbox" id="hmf_tick_0" name="tick[]"', $html);
        $this->assertStringContainsString('type="file"', $html);

        // A hidden field carries its value and is drawn with no label at all.
        $this->assertStringContainsString('<input type="hidden" name="source" value="facebook">', $html);
        $this->assertStringNotContainsString('>Source', $html);
    }

    public function test_a_draft_form_shows_the_closed_message_instead_of_the_fields(): void
    {
        $form = $this->form([$this->field(['label' => 'Student Name'])], [
            'status'         => Form::DRAFT,
            'closed_message' => 'We are not taking enquiries right now.',
        ]);

        $this->get(route('frontend.form.show', $form->slug))
            ->assertOk()
            ->assertSee('We are not taking enquiries right now.')
            ->assertDontSee('name="student_name"', false);
    }

    public function test_an_unknown_slug_is_a_404(): void
    {
        $this->get(route('frontend.form.show', 'no-such-form'))->assertNotFound();
    }

    /* ============================== SUBMISSION ============================= */

    public function test_a_submission_is_stored_against_the_fields_that_were_asked(): void
    {
        $form = $this->form([
            $this->field(['label' => 'Student Name', 'is_required' => 1]),
            $this->field(['label' => 'Course', 'field_type' => FormFieldType::DROPDOWN, 'options' => [
                ['label' => 'Python Full Stack', 'value' => 'python'],
            ]]),
        ]);

        $this->submit($form, ['student_name' => 'Arun Kumar', 'course' => 'python'])
            ->assertRedirect()
            ->assertSessionHas('form_success');

        $response = FormResponse::firstOrFail();

        $this->assertSame($form->id, $response->form_id);
        $this->assertSame('New', $response->status);
        $this->assertNotNull($response->submitted_at);

        $values = $response->keyed();
        $this->assertSame('Arun Kumar', $values['student_name']->value);
        $this->assertSame('python', $values['course']->value);

        // The snapshot that keeps this readable after the form moves on.
        $this->assertSame('Student Name', $values['student_name']->field_label);
        $this->assertSame(FormFieldType::SHORT_TEXT, $values['student_name']->field_type);
    }

    public function test_required_fields_are_enforced_on_the_server(): void
    {
        $form = $this->form([$this->field(['label' => 'Student Name', 'is_required' => 1])]);

        $this->submit($form, [])->assertSessionHasErrors('student_name');

        $this->assertSame(0, FormResponse::count());
    }

    public function test_each_type_enforces_its_own_rules(): void
    {
        $form = $this->form([
            $this->field(['label' => 'Mail', 'field_type' => FormFieldType::EMAIL]),
            $this->field(['label' => 'Mobile', 'field_type' => FormFieldType::MOBILE]),
            $this->field(['label' => 'Age', 'field_type' => FormFieldType::NUMBER, 'min_value' => 18, 'max_value' => 60]),
            $this->field(['label' => 'Pick', 'field_type' => FormFieldType::DROPDOWN, 'options' => [['label' => 'A', 'value' => 'a']]]),
        ]);

        $this->submit($form, [
            'mail'   => 'not-an-email',
            'mobile' => 'call me maybe',
            'age'    => 12,
            // A value that is not one of the offered options.
            'pick'   => 'z',
        ])->assertSessionHasErrors(['mail', 'mobile', 'age', 'pick']);

        $this->submit($form, [
            'mail' => 'arun@example.com', 'mobile' => '+91 78240 94044', 'age' => 25, 'pick' => 'a',
        ])->assertSessionHasNoErrors();
    }

    public function test_length_rules_are_applied(): void
    {
        $form = $this->form([
            $this->field(['label' => 'Code', 'min_length' => 4, 'max_length' => 6]),
        ]);

        $this->submit($form, ['code' => 'ab'])->assertSessionHasErrors('code');
        $this->submit($form, ['code' => 'abcdefgh'])->assertSessionHasErrors('code');
        $this->submit($form, ['code' => 'abcd'])->assertSessionHasNoErrors();
    }

    public function test_a_checkbox_field_stores_every_ticked_option(): void
    {
        $form = $this->form([
            $this->field(['label' => 'Services', 'field_type' => FormFieldType::CHECKBOX, 'options' => [
                ['label' => 'Web Development', 'value' => 'web'],
                ['label' => 'SEO', 'value' => 'seo'],
                ['label' => 'Mobile App', 'value' => 'app'],
            ]]),
        ]);

        $this->submit($form, ['services' => ['web', 'seo']])->assertSessionHasNoErrors();

        $value = FormResponse::with('values.field.options')->firstOrFail()->keyed()['services'];

        // The canonical values are stored...
        $this->assertSame(['web', 'seo'], $value->decoded());
        // ...and read back as the labels the admin wrote.
        $this->assertSame('Web Development, SEO', $value->display);
    }

    /** A hidden field records its configured value even though nobody typed it. */
    public function test_a_hidden_field_records_its_default(): void
    {
        $form = $this->form([
            $this->field(['label' => 'Source', 'field_type' => FormFieldType::HIDDEN, 'default_value' => 'facebook']),
        ]);

        $this->submit($form, [])->assertSessionHasNoErrors();

        $this->assertSame('facebook', FormResponse::firstOrFail()->keyed()['source']->value);
    }

    /* ================================ FILES =============================== */

    public function test_an_upload_is_stored_privately_and_is_downloadable_only_by_an_admin(): void
    {
        Storage::fake('local');

        $form = $this->form([
            $this->field(['label' => 'Resume', 'field_type' => FormFieldType::FILE, 'file_types' => ['pdf']]),
        ]);

        $this->submit($form, ['resume' => UploadedFile::fake()->create('My CV.pdf', 40, 'application/pdf')])
            ->assertSessionHasNoErrors();

        $response = FormResponse::firstOrFail();
        $value    = $response->keyed()['resume'];
        $path     = $value->filePaths()[0];

        // On the private disk, under the module's own directory, never on the
        // public one that /storage serves straight out.
        $this->assertStringStartsWith(FormSubmissionService::UPLOAD_ROOT . '/' . $form->id . '/', $path);
        Storage::disk('local')->assertExists($path);

        // The readable name survives without ever having been the path.
        $this->assertSame('my-cv.pdf', \App\Models\FormResponseValue::originalName($path));

        $url = route('backend.forms.responses.file', [$form, $response, $value, 0]);

        $this->get($url)->assertRedirect(route('backend.auth.login'));

        $admin = User::where('is_super_admin', true)->firstOrFail();
        $this->withSession([
            'admin_logged_in' => true, 'admin_id' => $admin->id,
            'admin_name' => $admin->name, 'admin_email' => $admin->email,
        ])->get($url)->assertOk()->assertDownload('my-cv.pdf');
    }

    public function test_a_dangerous_upload_is_refused_whatever_the_admin_configured(): void
    {
        Storage::fake('local');

        // "php" is not in the module's allow-list, so it cannot be configured in
        // — the field falls back to the safe defaults.
        $form = $this->form([
            $this->field(['label' => 'Doc', 'field_type' => FormFieldType::FILE, 'file_types' => ['php', 'pdf']]),
        ]);

        $this->assertSame(['pdf'], $form->fields->first()->allowedExtensions());

        $this->submit($form, ['doc' => UploadedFile::fake()->create('shell.php', 4, 'application/x-php')])
            ->assertSessionHasErrors('doc');

        $this->assertSame(0, FormResponse::count());
    }

    public function test_an_oversized_upload_is_refused(): void
    {
        Storage::fake('local');

        $form = $this->form([
            $this->field(['label' => 'Doc', 'field_type' => FormFieldType::FILE, 'file_types' => ['pdf'], 'max_file_size_kb' => 100]),
        ]);

        $this->submit($form, ['doc' => UploadedFile::fake()->create('big.pdf', 500, 'application/pdf')])
            ->assertSessionHasErrors('doc');
    }

    /**
     * With no size of its own, a file question takes whatever the server takes.
     * The module used to stop at 10 MB whatever php.ini said.
     */
    public function test_a_file_question_with_no_size_set_is_bounded_only_by_the_server(): void
    {
        Storage::fake('local');

        $form  = $this->form([$this->field(['label' => 'Doc', 'field_type' => FormFieldType::FILE, 'file_types' => ['pdf']])]);
        $field = $form->fields->first();

        $this->assertSame(\App\Support\UploadLimit::kilobytes(), $field->maxFileKb());

        // An admin's own size is honoured up to what the server can take.
        $this->assertSame(
            \App\Support\UploadLimit::kilobytes(),
            $this->form([$this->field(['label' => 'Doc', 'field_type' => FormFieldType::FILE, 'max_file_size_kb' => PHP_INT_MAX])], ['slug' => 'huge'])
                ->fields->first()->maxFileKb(),
        );

        if (\App\Support\UploadLimit::kilobytes() <= 12 * 1024) {
            $this->markTestSkipped('This server takes no more than 12 MB, so the 10 MB cap cannot be shown to be gone.');
        }

        $this->submit($form, ['doc' => UploadedFile::fake()->create('big.pdf', 12 * 1024, 'application/pdf')])
            ->assertSessionHasNoErrors();

        $this->assertSame(1, FormResponse::count());
    }

    /* ========================= CONDITIONAL LOGIC ========================== */

    public function test_a_field_hidden_by_its_condition_is_not_required(): void
    {
        $form = $this->form([
            $this->field(['label' => 'Have Experience', 'field_type' => FormFieldType::YES_NO, 'options' => [
                ['label' => 'Yes', 'value' => 'Yes'], ['label' => 'No', 'value' => 'No'],
            ]]),
            $this->field([
                'label' => 'Years of Experience', 'is_required' => 1,
                'cond_field_key' => 'have_experience', 'cond_operator' => 'equals', 'cond_value' => 'Yes',
            ]),
        ]);

        // "No" — the follow-up was never shown, so it cannot be demanded.
        $this->submit($form, ['have_experience' => 'No'])->assertSessionHasNoErrors();

        // "Yes" — now it applies.
        $this->submit($form, ['have_experience' => 'Yes'])->assertSessionHasErrors('years_of_experience');

        $this->submit($form, ['have_experience' => 'Yes', 'years_of_experience' => '3'])
            ->assertSessionHasNoErrors();
    }

    public function test_a_field_hidden_by_its_condition_is_not_stored(): void
    {
        $form = $this->form([
            $this->field(['label' => 'Have Experience', 'field_type' => FormFieldType::YES_NO, 'options' => [
                ['label' => 'Yes', 'value' => 'Yes'], ['label' => 'No', 'value' => 'No'],
            ]]),
            $this->field([
                'label' => 'Years', 'cond_field_key' => 'have_experience',
                'cond_operator' => 'equals', 'cond_value' => 'Yes',
            ]),
        ]);

        // A forged value for a field the condition rules out is dropped.
        $this->submit($form, ['have_experience' => 'No', 'years' => '99'])->assertSessionHasNoErrors();

        $this->assertArrayNotHasKey('years', FormResponse::firstOrFail()->keyed());
    }

    /* ============================= AVAILABILITY ============================ */

    public function test_a_disabled_form_refuses_a_submission_posted_straight_at_it(): void
    {
        $form = $this->form([$this->field(['label' => 'Name'])], ['status' => Form::DISABLED]);

        $this->submit($form, ['name' => 'Arun'])->assertSessionHas('form_error');

        $this->assertSame(0, FormResponse::count());
    }

    /**
     * A published form takes every response it is sent: no response cap, no
     * one-per-visitor rule, no rate limit. The settings that used to switch
     * those on are ignored if they are still in an old form's JSON.
     */
    public function test_a_published_form_takes_any_number_of_responses_from_the_same_visitor(): void
    {
        $form = $this->form([$this->field(['label' => 'Name'])]);
        $form->update(['settings' => $form->settings + ['max_submissions' => 2, 'allow_multiple' => false]]);

        // Past the old throttle of 20 a minute, from one session and one IP.
        for ($i = 1; $i <= 30; $i++) {
            $this->submit($form, ['name' => "Student {$i}"])
                ->assertSessionHasNoErrors()
                ->assertSessionMissing('form_error');
        }

        $this->assertSame(30, FormResponse::count());

        // A new visitor — the last submit's thank-you flash would otherwise
        // stand in for the form.
        $this->flushSession();

        $this->get(route('frontend.form.show', $form->slug))
            ->assertOk()
            ->assertDontSee($form->closed_message)
            ->assertSee('name="name"', false);
    }

    public function test_an_answer_of_any_length_is_stored_whole(): void
    {
        $form = $this->form([
            $this->field(['label' => 'Name']),
            $this->field(['label' => 'Essay', 'field_type' => FormFieldType::LONG_TEXT]),
            $this->field(['label' => 'Email', 'field_type' => FormFieldType::EMAIL]),
        ]);

        $essay = str_repeat('Every word of this answer matters. ', 800);   // ~28,000 characters
        $name  = str_repeat('Arun ', 100);
        $email = str_repeat('a', 200) . '@example.com';

        $this->submit($form, ['name' => $name, 'essay' => $essay, 'email' => $email])
            ->assertSessionHasNoErrors();

        $values = FormResponseValue::pluck('value', 'field_key');

        $this->assertSame(trim($essay), $values['essay']);
        $this->assertSame(trim($name), $values['name']);
        $this->assertSame($email, $values['email']);
    }


    public function test_a_redirect_is_followed_when_one_is_configured(): void
    {
        $form = $this->form([$this->field(['label' => 'Name'])], [
            'redirect_url' => 'https://example.com/thank-you',
        ]);

        $this->submit($form, ['name' => 'Arun'])->assertRedirect('https://example.com/thank-you');

        $this->assertSame(1, FormResponse::count());
    }

    /* ============================== SECURITY ============================== */

    /**
     * CSRF cannot be exercised directly — Laravel's ValidateCsrfToken middleware
     * stands down under runningUnitTests(), which is why every other POST here
     * works without a token. What CAN be checked is that the protection is
     * actually wired up: the route is inside the "web" group that applies it,
     * and the page emits a token for the visitor to send back.
     */
    public function test_the_submission_route_is_csrf_protected(): void
    {
        $route = app('router')->getRoutes()->getByName('frontend.form.submit');

        $this->assertNotNull($route);
        $this->assertContains('web', $route->gatherMiddleware());
        // Not rate-limited, on purpose: a class or workshop submits from one
        // shared connection. The honeypot and CSRF are what guard it.
        $this->assertEmpty(array_filter($route->gatherMiddleware(), fn ($m) => is_string($m) && str_starts_with($m, 'throttle')));

        $form = $this->form([$this->field(['label' => 'Name'])]);

        $this->get(route('frontend.form.show', $form->slug))
            ->assertOk()
            ->assertSee('name="_token"', false);
    }

    public function test_the_honeypot_swallows_a_bot_without_storing_anything(): void
    {
        $form = $this->form([$this->field(['label' => 'Name'])]);

        // Answered like a success, so whatever is submitting learns nothing.
        $this->submit($form, ['name' => 'Arun', FormSubmissionService::HONEYPOT => 'http://spam.example'])
            ->assertRedirect();

        $this->assertSame(0, FormResponse::count());
    }

    /** Whatever a stranger types is data, never markup. */
    public function test_a_submitted_script_tag_is_escaped_when_the_admin_reads_it(): void
    {
        $form = $this->form([$this->field(['label' => 'Message', 'field_type' => FormFieldType::LONG_TEXT])]);

        $this->submit($form, ['message' => '<script>alert(1)</script>'])->assertSessionHasNoErrors();

        $response = FormResponse::firstOrFail();
        $admin    = User::where('is_super_admin', true)->firstOrFail();

        $html = $this->withSession([
            'admin_logged_in' => true, 'admin_id' => $admin->id,
            'admin_name' => $admin->name, 'admin_email' => $admin->email,
        ])->get(route('backend.forms.responses.show', [$form, $response]))->assertOk()->getContent();

        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    public function test_responses_are_never_reachable_without_signing_in(): void
    {
        $form     = $this->form([$this->field(['label' => 'Name'])]);
        $this->submit($form, ['name' => 'Arun']);
        $response = FormResponse::firstOrFail();

        $this->get(route('backend.forms.responses.index', $form))->assertRedirect(route('backend.auth.login'));
        $this->get(route('backend.forms.responses.show', [$form, $response]))->assertRedirect(route('backend.auth.login'));
    }

    /** A response id from one form must not be readable through another's URL. */
    public function test_a_response_cannot_be_read_through_the_wrong_form(): void
    {
        $mine  = $this->form([$this->field(['label' => 'Name'])], ['slug' => 'mine']);
        $other = $this->form([$this->field(['label' => 'Name'])], ['slug' => 'other', 'name' => 'Other']);

        $this->submit($mine, ['name' => 'Arun']);
        $response = FormResponse::firstOrFail();

        $admin = User::where('is_super_admin', true)->firstOrFail();

        $this->withSession([
            'admin_logged_in' => true, 'admin_id' => $admin->id,
            'admin_name' => $admin->name, 'admin_email' => $admin->email,
        ])->get(route('backend.forms.responses.show', [$other, $response]))->assertNotFound();
    }

    /* ============================ NOTIFICATION ============================ */

    public function test_the_configured_addresses_are_emailed_when_one_is_set(): void
    {
        Mail::fake();

        $form = $this->form([$this->field(['label' => 'Name'])], [
            'notify_enabled' => 1,
            'notify_emails'  => 'admissions@example.com, not-an-address, hr@example.com',
        ]);

        $this->submit($form, ['name' => 'Arun'])->assertSessionHasNoErrors();

        Mail::assertSent(\App\Mail\FormResponseNotification::class, function ($mail) {
            return $mail->hasTo('admissions@example.com') && $mail->hasTo('hr@example.com');
        });

        // and the site's own admin address alongside them
        Mail::assertSent(\App\Mail\FormResponseNotification::class, fn ($mail) => $mail->hasTo(
            \App\Models\Setting::adminNotificationRecipients()[0],
        ));
    }

    /**
     * The form's own extra addresses are the part that switches off. The site's
     * admin address is still told, because a response nobody hears about is the
     * bug this replaced: no form has ever had notify_enabled set, and the panel
     * offers no way to set it.
     */
    public function test_the_site_admin_is_emailed_even_with_the_forms_own_addresses_off(): void
    {
        Mail::fake();
        Setting::putMany(['contact_email' => 'admin@hireminds.test']);

        $form = $this->form([$this->field(['label' => 'Name'])], [
            'notify_enabled' => 0,
            'notify_emails'  => 'admissions@example.com',
        ]);

        $this->submit($form, ['name' => 'Arun']);

        Mail::assertSent(\App\Mail\FormResponseNotification::class, function ($mail) {
            return $mail->hasTo('admin@hireminds.test')
                && ! $mail->hasTo('admissions@example.com');
        });
    }

    /** With no address configured anywhere there is nobody to tell. */
    public function test_nothing_is_emailed_when_the_site_has_no_address(): void
    {
        Mail::fake();
        Setting::putMany(['contact_email' => '', 'mail_from_address' => '']);
        config()->set('mail.from.address', null);

        $form = $this->form([$this->field(['label' => 'Name'])], ['notify_enabled' => 0]);

        $this->submit($form, ['name' => 'Arun']);

        Mail::assertNothingSent();
    }

    /* =============================== EXPORT =============================== */

    /** The export's columns are the form's own questions, whatever they are. */
    public function test_the_export_columns_come_from_the_form(): void
    {
        $form = $this->form([
            $this->field(['label' => 'Applicant']),
            $this->field(['label' => 'Expected Salary', 'field_type' => FormFieldType::NUMBER]),
        ], ['name' => 'Job Application', 'slug' => 'job-application']);

        $this->submit($form, ['applicant' => 'Arun Kumar', 'expected_salary' => '90000']);

        $admin = User::where('is_super_admin', true)->firstOrFail();

        $csv = $this->withSession([
            'admin_logged_in' => true, 'admin_id' => $admin->id,
            'admin_name' => $admin->name, 'admin_email' => $admin->email,
        ])->get(route('backend.forms.responses.export', $form))->assertOk()->streamedContent();

        $this->assertStringContainsString('Applicant', $csv);
        $this->assertStringContainsString('Expected Salary', $csv);
        $this->assertStringContainsString('Arun Kumar', $csv);
        $this->assertStringContainsString('90000', $csv);
    }

    /* ============================ CLEARING DATA =========================== */

    /**
     * Emptying a form's responses — what you do after testing it and before it
     * goes live, when deleting them one at a time is not a plan.
     */
    public function test_a_form_s_responses_can_be_cleared_in_one_go(): void
    {
        Storage::fake('local');

        $form = $this->form([
            $this->field(['label' => 'Name']),
            $this->field(['label' => 'Resume', 'field_type' => FormFieldType::FILE, 'file_types' => ['pdf']]),
        ]);

        foreach (['One', 'Two', 'Three'] as $name) {
            $this->submit($form, [
                'name'   => $name,
                'resume' => UploadedFile::fake()->create($name . '.pdf', 10, 'application/pdf'),
            ])->assertSessionHasNoErrors();
        }

        $this->assertSame(3, FormResponse::count());

        $paths = FormResponse::with('values')->get()
            ->flatMap(fn ($r) => $r->values->flatMap->filePaths())
            ->all();

        $this->assertCount(3, $paths);

        $admin = User::where('is_super_admin', true)->firstOrFail();

        $this->withSession([
            'admin_logged_in' => true, 'admin_id' => $admin->id,
            'admin_name' => $admin->name, 'admin_email' => $admin->email,
        ])->delete(route('backend.forms.responses.clear', $form))->assertRedirect();

        $this->assertSame(0, FormResponse::count());
        // The answers go with them...
        $this->assertSame(0, \App\Models\FormResponseValue::count());

        // ...and so do the files, which live outside the database and would
        // otherwise sit on the disk forever.
        foreach ($paths as $path) {
            Storage::disk('local')->assertMissing($path);
        }
    }

    /** The clear must never take more than the screen is showing. */
    public function test_clearing_respects_the_filter_on_screen(): void
    {
        $form = $this->form([$this->field(['label' => 'Name'])]);

        foreach (['Keep me', 'Delete me', 'Delete me too'] as $name) {
            $this->submit($form, ['name' => $name]);
        }

        // Mark one Contacted, then clear only the ones still New.
        $keep = FormResponse::whereHas('values', fn ($q) => $q->where('value', 'Keep me'))->firstOrFail();
        $keep->update(['status' => 'Contacted']);

        $admin = User::where('is_super_admin', true)->firstOrFail();

        $this->withSession([
            'admin_logged_in' => true, 'admin_id' => $admin->id,
            'admin_name' => $admin->name, 'admin_email' => $admin->email,
        ])->delete(route('backend.forms.responses.clear', [$form, 'status' => 'New']))->assertRedirect();

        $this->assertSame(1, FormResponse::count());
        $this->assertSame($keep->id, FormResponse::first()->id);
    }

    /** The Responses screen can empty every form at once. */
    public function test_responses_can_be_cleared_across_every_form(): void
    {
        $first  = $this->form([$this->field(['label' => 'Name'])], ['slug' => 'first', 'name' => 'First']);
        $second = $this->form([$this->field(['label' => 'Name'])], ['slug' => 'second', 'name' => 'Second']);

        $this->submit($first, ['name' => 'A']);
        $this->submit($second, ['name' => 'B']);

        $this->assertSame(2, FormResponse::count());

        $admin = User::where('is_super_admin', true)->firstOrFail();

        $this->withSession([
            'admin_logged_in' => true, 'admin_id' => $admin->id,
            'admin_name' => $admin->name, 'admin_email' => $admin->email,
        ])->delete(route('backend.forms.clear-responses'))->assertRedirect();

        $this->assertSame(0, FormResponse::count());
        // The forms themselves are untouched — this clears data, not structure.
        $this->assertSame(2, Form::count());
        $this->assertSame(1, $first->fresh()->fields->count());
    }

    public function test_clearing_is_behind_the_admin_guard(): void
    {
        $form = $this->form([$this->field(['label' => 'Name'])]);
        $this->submit($form, ['name' => 'Arun']);

        $this->delete(route('backend.forms.responses.clear', $form))->assertRedirect(route('backend.auth.login'));
        $this->delete(route('backend.forms.clear-responses'))->assertRedirect(route('backend.auth.login'));

        $this->assertSame(1, FormResponse::count());
    }

    /* ============================ ORPHANED ROWS ============================
       A response whose form is gone. The cascade should make this impossible,
       and does wherever the foreign key is enforced — but a MyISAM table
       ignores constraints silently, and deleting a form by hand with the
       checks off skips the cascade, so live grew rows like this. They used to
       render with an empty Actions cell: visible, and impossible to remove. */

    /**
     * Strand a response the way an unenforced cascade does.
     *
     * Not by deleting a form: SQLite runs the cascade even with constraints
     * turned off inside a test's transaction, so that just deletes the row.
     * Pointing a response at a form id that never existed reaches the same
     * state — form_id set, no such form — without deleting anything.
     */
    private function orphan(string $name = 'Keerthika KT'): FormResponse
    {
        match (DB::connection()->getDriverName()) {
            // Deferred until commit, and the test rolls back, so it never runs.
            'sqlite' => DB::statement('PRAGMA defer_foreign_keys = ON'),
            default  => DB::statement('SET FOREIGN_KEY_CHECKS = 0'),
        };

        $response = FormResponse::create([
            'form_id'      => 424242,
            'status'       => FormResponse::STATUSES[0],
            'submitted_at' => now(),
        ]);

        FormResponseValue::create([
            'response_id' => $response->id,
            'field_id'    => null,
            'field_key'   => 'name',
            'field_label' => 'Name',
            'field_type'  => 'text',
            'value'       => $name,
        ]);

        $this->assertNull($response->fresh()->form, 'the response should be orphaned');

        return $response;
    }

    public function test_a_response_whose_form_is_gone_can_still_be_deleted(): void
    {
        $response = $this->orphan();

        $this->asAdmin()
            ->delete(route('backend.forms.response-destroy', $response))
            ->assertRedirect();

        $this->assertNull(FormResponse::find($response->id));
        // Its answers go with it rather than lingering as unreachable rows.
        $this->assertDatabaseMissing('form_response_values', ['response_id' => $response->id]);
    }

    public function test_the_responses_screen_shows_an_orphan_with_a_way_to_remove_it(): void
    {
        $response = $this->orphan();

        $page = $this->asAdmin()->get(route('backend.forms.all-responses'))->assertOk();

        // The answer is still readable, the state is explained rather than left
        // as a bare dash, and there is a delete button.
        $page->assertSee('Keerthika KT')
            ->assertSee('form deleted')
            ->assertSee('action="' . route('backend.forms.response-destroy', $response) . '"', false);
    }

    public function test_clearing_sweeps_orphans_too(): void
    {
        $this->orphan('Ghost One');
        $this->orphan('Ghost Two');

        $this->asAdmin()->delete(route('backend.forms.clear-responses'))->assertRedirect();

        $this->assertSame(0, FormResponse::count());
    }

    public function test_deleting_a_response_without_its_form_is_behind_the_admin_guard(): void
    {
        $response = $this->orphan();

        $this->delete(route('backend.forms.response-destroy', $response))
            ->assertRedirect(route('backend.auth.login'));

        $this->assertNotNull(FormResponse::find($response->id));
    }

    /* ================================ EMBED =============================== */

    public function test_an_embedded_submission_is_stored_exactly_like_a_direct_one(): void
    {
        $form = $this->form([$this->field(['label' => 'Name'])]);

        $this->get(route('frontend.form.show', ['slug' => $form->slug, 'embed' => 1]))
            ->assertOk()
            ->assertSee('hmf--embed', false);

        $this->post(route('frontend.form.submit', ['slug' => $form->slug, 'embed' => 1]), ['name' => 'Arun'])
            ->assertRedirect();

        $this->assertSame('Arun', FormResponse::firstOrFail()->keyed()['name']->value);
    }
}
