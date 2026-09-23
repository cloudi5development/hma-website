<?php

namespace Tests\Feature\Backend;

use App\Models\Form;
use App\Models\FormField;
use App\Models\FormResponse;
use App\Models\User;
use App\Services\FormBuilderService;
use App\Support\FormFieldType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The admin side of the Forms module.
 *
 * The claim being tested throughout is that NOTHING about a particular form is
 * in the code: the same builder produces a course enquiry, a job application and
 * a survey, and the module never knows which it is looking at.
 */
class FormBuilderTest extends TestCase
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

    /** The builder's payload for a form, with sensible defaults. */
    private function payload(array $overrides = []): array
    {
        // No title and no slug: the create screen does not ask for either, and
        // a helper that posts them would be testing a form nobody can build.
        return $overrides + [
            'name'            => 'Course Enquiry',
            'description'     => 'Please complete the form below.',
            'status'          => Form::DRAFT,
            'submit_label'    => 'Send Enquiry',
            'success_message' => 'Thanks — we will be in touch.',
            'fields'          => [],
        ];
    }

    /** One field row as the builder posts it. */
    private function field(array $overrides = []): array
    {
        return $overrides + [
            'field_type'  => FormFieldType::SHORT_TEXT,
            'label'       => 'Student Name',
            'is_required' => 1,
        ];
    }

    private function makeForm(array $fields = [], array $overrides = []): Form
    {
        return app(FormBuilderService::class)->save(
            $this->payload($overrides + ['fields' => $fields]),
        );
    }

    /* ============================== THE POINT ============================== */

    /**
     * The headline claim: two forms with nothing in common, from one builder.
     */
    public function test_two_completely_different_forms_are_built_by_the_same_code(): void
    {
        $enquiry = $this->makeForm([
            $this->field(['label' => 'Student Name']),
            $this->field(['label' => 'Course Interested', 'field_type' => FormFieldType::DROPDOWN, 'options' => [
                ['label' => 'Python Full Stack', 'value' => 'python-full-stack'],
                ['label' => 'Data Science', 'value' => 'data-science'],
            ]]),
        ], ['name' => 'Course Enquiry']);

        $survey = $this->makeForm([
            $this->field(['label' => 'Favorite Color', 'field_type' => FormFieldType::RADIO, 'options' => [
                ['label' => 'Red', 'value' => 'red'],
                ['label' => 'Blue', 'value' => 'blue'],
            ]]),
            $this->field(['label' => 'Upload Certificate', 'field_type' => FormFieldType::FILE, 'is_required' => 0]),
        ], ['name' => 'Colour Survey', 'slug' => 'colour-survey']);

        $this->assertSame(['Student Name', 'Course Interested'], $enquiry->fields->pluck('label')->all());
        $this->assertSame(['Favorite Color', 'Upload Certificate'], $survey->fields->pluck('label')->all());

        // Not a shared column between them anywhere.
        $this->assertSame(['student_name', 'course_interested'], $enquiry->fields->pluck('field_key')->all());
        $this->assertSame(['favorite_color', 'upload_certificate'], $survey->fields->pluck('field_key')->all());
    }

    /**
     * Every screen in the module renders for a signed-in admin.
     *
     * Flat and unglamorous, and it earns its place: the listing 500'd on a
     * TypeError the first time it was opened for real, because nothing else here
     * actually loaded it while signed in — the guard test only ever checked that
     * it redirects when you are not.
     */
    public function test_every_screen_in_the_module_renders(): void
    {
        $form = $this->makeForm([
            $this->field(['label' => 'Student Name']),
            $this->field(['label' => 'Course', 'field_type' => FormFieldType::DROPDOWN, 'options' => [
                ['label' => 'Python Full Stack', 'value' => 'python'],
            ]]),
        ], ['status' => Form::PUBLISHED]);

        $response = $form->responses()->create(['status' => 'New', 'submitted_at' => now()]);
        $response->values()->create([
            'field_id'    => $form->fields->first()->id,
            'field_key'   => 'student_name',
            'field_label' => 'Student Name',
            'field_type'  => FormFieldType::SHORT_TEXT,
            'value'       => 'Arun Kumar',
        ]);

        foreach ([
            'listing'          => route('backend.forms.index'),
            'listing filtered' => route('backend.forms.index', ['q' => 'course', 'status' => Form::PUBLISHED]),
            'create'           => route('backend.forms.create'),
            'edit'             => route('backend.forms.edit', $form),
            'show'             => route('backend.forms.show', $form),
            'preview'          => route('backend.forms.preview', $form),
            'responses'        => route('backend.forms.responses.index', $form),
            'response'         => route('backend.forms.responses.show', [$form, $response]),
            'all responses'    => route('backend.forms.all-responses'),
            'all filtered'     => route('backend.forms.all-responses', ['form' => $form->id, 'status' => 'New']),
        ] as $screen => $url) {
            $this->signedIn()->get($url)->assertOk("The {$screen} screen did not render.");
        }

        $this->signedIn()->get(route('backend.forms.responses.export', $form))->assertOk();
    }

    public function test_a_new_form_starts_with_no_fields_at_all(): void
    {
        $html = $this->signedIn()->get(route('backend.forms.create'))->assertOk()->getContent();

        // Not one question row is rendered — no sample question is smuggled in
        // as a starting point. The template that the Add button clones is not a
        // row; it is inside a <template> and posts nothing.
        preg_match_all('#<div class="fb-field"#', $html, $rendered);
        $this->assertCount(1, $rendered[0], 'Only the hidden row template should be present.');

        $this->assertStringNotContainsString('name="fields[f0][label]"', $html);
        $this->assertStringContainsString('Add Question', $html);
    }

    /* ============================ THE CREATE FLOW ===========================
       Name, description, questions — and nothing else on the screen. The name
       is the heading and the link too, so there is one box to type it in. */

    public function test_the_create_screen_asks_only_for_a_name_a_description_and_questions(): void
    {
        $html = $this->signedIn()->get(route('backend.forms.create'))->assertOk()->getContent();

        $this->assertStringContainsString('name="name"', $html);
        $this->assertStringContainsString('name="description"', $html);

        // The name IS the title and the link, so neither is asked for.
        $this->assertStringNotContainsString('name="title"', $html);
        $this->assertStringNotContainsString('name="slug"', $html);

        // Settings belong to a form that exists, not to the act of making one.
        foreach (['submit_label', 'success_message', 'redirect_url', 'notify_emails'] as $setting) {
            $this->assertStringNotContainsString('name="' . $setting . '"', $html, "{$setting} should not be on the create screen.");
        }

        // Per question: the label, the type, and Required. Nothing else is ASKED.
        $this->assertStringContainsString('Generate Link', $html);
        $this->assertStringNotContainsString('[field_key]', $html);

        // Placeholder and help text are carried, not asked: hidden inputs only.
        // They have to be on the row — the service writes whatever a row posts,
        // so a row without them saved them blank, which went unnoticed only while
        // nothing could set them. Bulk upload can.
        preg_match_all('/<(input|textarea|select)\b[^>]*name="fields\[[^"]*\]\[(placeholder|help_text)\]"[^>]*>/', $html, $controls);

        $this->assertNotEmpty($controls[0], 'the row should carry placeholder and help text');

        foreach ($controls[0] as $i => $control) {
            $this->assertSame('input', $controls[1][$i], 'placeholder and help text must not be editable on this screen');
            $this->assertStringContainsString('type="hidden"', $control, 'placeholder and help text must not be editable on this screen');
        }
    }

    public function test_the_form_name_becomes_the_title_and_the_link(): void
    {
        $this->signedIn()
            ->post(route('backend.forms.store'), [
                'name'   => 'Student Registration',
                'fields' => ['f0' => $this->field(['label' => 'Student Name'])],
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $form = Form::firstOrFail();

        $this->assertSame('Student Registration', $form->name);
        $this->assertSame('Student Registration', $form->title);
        $this->assertSame('student-registration', $form->slug);
    }

    /**
     * A form is live the moment it is created — its link was just handed over.
     *
     * Submitted with the status the CREATE SCREEN itself renders, not one typed
     * into the test. That distinction is the whole point: the screen was posting
     * "draft" (a fresh Form carries 'draft', and `$form->status ?: PUBLISHED`
     * never falls through because 'draft' is truthy), so every form built in the
     * panel came out closed — while a test that posted "published" by hand
     * passed the entire time.
     */
    public function test_a_form_created_through_the_screen_is_published_immediately(): void
    {
        $create = $this->signedIn()->get(route('backend.forms.create'))->assertOk()->getContent();

        preg_match('#name="status"[^>]*value="([^"]+)"#', $create, $rendered);

        $this->assertSame(
            Form::PUBLISHED,
            $rendered[1] ?? null,
            'The create screen would save the form as a draft, and its link would show the closed message.',
        );

        // ...and posting exactly that produces a form whose link works.
        $this->signedIn()->post(route('backend.forms.store'), [
            'name'   => 'Job Application',
            'status' => $rendered[1],
            'fields' => ['f0' => $this->field(['label' => 'Applicant'])],
        ])->assertSessionHasNoErrors();

        $form = Form::firstOrFail();

        $this->assertTrue($form->isPublished());
        $this->get(route('frontend.form.show', $form->slug))
            ->assertOk()
            ->assertSee('Applicant')
            ->assertDontSee($form->closed_message);
    }

    /** Editing a form must not change whether it is live. */
    public function test_editing_keeps_the_status_the_form_already_has(): void
    {
        $form = $this->makeForm([$this->field()], ['status' => Form::DISABLED]);

        $edit = $this->signedIn()->get(route('backend.forms.edit', $form))->assertOk()->getContent();

        preg_match('#name="status"[^>]*value="([^"]+)"#', $edit, $rendered);

        $this->assertSame(Form::DISABLED, $rendered[1] ?? null, 'Editing a disabled form would have republished it.');
    }

    /**
     * The dialog asks the server for the address rather than slugging the name
     * in JavaScript, because only the server knows a name is already taken.
     */
    public function test_the_link_preview_returns_the_address_the_form_will_really_get(): void
    {
        $this->signedIn()
            ->postJson(route('backend.forms.slug-preview'), ['name' => 'Course Enquiry'])
            ->assertOk()
            ->assertJson([
                'slug' => 'course-enquiry',
                'url'  => route('frontend.form.show', 'course-enquiry'),
            ]);

        // With that name taken, the preview says so instead of promising it.
        $this->makeForm([], ['name' => 'Course Enquiry']);

        $this->signedIn()
            ->postJson(route('backend.forms.slug-preview'), ['name' => 'Course Enquiry'])
            ->assertOk()
            ->assertJson(['slug' => 'course-enquiry-2']);
    }

    public function test_the_link_preview_needs_a_name(): void
    {
        $this->signedIn()
            ->postJson(route('backend.forms.slug-preview'), ['name' => '   '])
            ->assertStatus(422);
    }

    /**
     * A shared link must not break because somebody fixed a typo in the form's
     * name. The slug is minted once and then left alone.
     */
    public function test_renaming_a_form_does_not_move_its_link(): void
    {
        $form = $this->makeForm([$this->field()], ['name' => 'Course Enquiry']);

        $this->assertSame('course-enquiry', $form->slug);

        app(FormBuilderService::class)->save(
            $this->payload(['name' => 'Course Enquiry 2026', 'fields' => [$this->field()]]),
            $form,
        );

        $form->refresh();

        $this->assertSame('Course Enquiry 2026', $form->name);
        $this->assertSame('Course Enquiry 2026', $form->title);
        $this->assertSame('course-enquiry', $form->slug, 'The public link moved out from under everyone holding it.');
    }

    /**
     * The edit screen asks for exactly what the create screen asks for.
     *
     * Same three things, so an admin who has built a form recognises the screen
     * they come back to.
     */
    public function test_the_edit_screen_asks_for_the_same_things_as_the_create_screen(): void
    {
        $form = $this->makeForm([$this->field(['label' => 'Student Name'])]);

        $html = $this->signedIn()->get(route('backend.forms.edit', $form))->assertOk()->getContent();

        $this->assertStringContainsString('name="name"', $html);
        $this->assertStringContainsString('name="description"', $html);
        $this->assertStringContainsString('Student Name', $html);

        foreach (['title', 'slug', 'submit_label', 'success_message', 'redirect_url', 'notify_emails'] as $absent) {
            $this->assertStringNotContainsString('name="' . $absent . '"', $html, "{$absent} should not be on the edit screen.");
        }
    }

    /**
     * The settings endpoint still works, and is still the only thing that may
     * change them.
     *
     * There is currently NO screen posting to it — the panel's UI was trimmed
     * down to the questions — so a form runs on the documented defaults unless
     * something calls this route. The behaviour is kept under test so putting a
     * screen back is a view, not a rebuild.
     */
    public function test_the_settings_endpoint_writes_only_the_settings(): void
    {
        $form = $this->makeForm([$this->field()]);

        $this->signedIn()->put(route('backend.forms.settings', $form), [
            'submit_label'    => 'Apply Now',
            'success_message' => 'We have your application.',
            'notify_enabled'  => 1,
            'notify_emails'   => 'hr@example.com',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $form->refresh();

        $this->assertSame('Apply Now', $form->submit_label);
        $this->assertSame('We have your application.', $form->success_message);
        // The form's OWN addresses - notificationRecipients() also carries the
        // site admin, who is told about every response.
        $this->assertSame(['hr@example.com'], $form->extraNotificationRecipients());
    }

    /**
     * And the reverse: saving the questions must not touch the settings.
     *
     * The builder posts no settings, and it used to write the settings JSON
     * anyway — from nothing — so every question save quietly reset the button
     * label, the thank-you message and the notification address.
     */
    public function test_saving_the_questions_leaves_the_settings_alone(): void
    {
        $form = $this->makeForm([$this->field()]);

        $this->signedIn()->put(route('backend.forms.settings', $form), [
            'submit_label'   => 'Apply Now',
            'notify_enabled' => 1,
            'notify_emails'  => 'hr@example.com',
        ])->assertSessionHasNoErrors();

        $this->signedIn()->put(route('backend.forms.update', $form), [
            'name'   => 'Course Enquiry',
            'status' => Form::PUBLISHED,
            'fields' => ['f0' => $this->field(['label' => 'Full Name'])],
        ])->assertRedirect()->assertSessionHasNoErrors();

        $form->refresh();

        $this->assertSame(['Full Name'], $form->fields->pluck('label')->all());
        $this->assertSame('Apply Now', $form->submit_label);
        // The form's OWN addresses - notificationRecipients() also carries the
        // site admin, who is told about every response.
        $this->assertSame(['hr@example.com'], $form->extraNotificationRecipients());
    }

    /**
     * A name has no length limit, and the activity log line that quotes it is
     * shortened rather than allowed to fail the save it records.
     */
    public function test_a_very_long_form_name_saves_and_is_logged(): void
    {
        $name = trim(str_repeat('Campus Placement Drive Registration ', 25));   // ~900 characters

        $this->signedIn()->post(route('backend.forms.store'), $this->payload([
            'name'   => $name,
            'fields' => ['f0' => $this->field()],
        ]))->assertRedirect()->assertSessionHasNoErrors();

        $form = Form::latest('id')->firstOrFail();

        $this->assertSame($name, $form->name);
        $this->assertSame($name, $form->title);
        $this->assertLessThanOrEqual(180, strlen($form->slug));
        $this->assertStringStartsWith('campus-placement-drive-registration', $form->slug);

        $log = \App\Models\ActivityLog::latest('id')->firstOrFail();
        $this->assertLessThanOrEqual(253, mb_strlen($log->description));
    }

    /**
     * The settings save must never touch the questions.
     *
     * Routing it through the builder would hand syncFields an empty list, and it
     * would remove every question on the form — silently emptying the thing the
     * admin was configuring.
     */
    public function test_saving_settings_leaves_the_questions_alone(): void
    {
        $form = $this->makeForm([
            $this->field(['label' => 'Student Name']),
            $this->field(['label' => 'Mobile Number']),
        ]);

        $this->signedIn()->put(route('backend.forms.settings', $form), ['submit_label' => 'Go'])
            ->assertSessionHasNoErrors();

        $this->assertCount(2, $form->fresh()->fields);
        $this->assertSame(['Student Name', 'Mobile Number'], $form->fresh()->fields->pluck('label')->all());
    }

    /** A save that carries no settings must not wipe the ones already stored. */
    public function test_saving_without_settings_leaves_them_alone(): void
    {
        $form = $this->makeForm([$this->field()], ['submit_label' => 'Send Enquiry']);

        $this->assertSame('Send Enquiry', $form->submit_label);

        app(FormBuilderService::class)->save([
            'name' => $form->name, 'status' => Form::PUBLISHED,
            'fields' => [$this->field(['id' => $form->fields->first()->id])],
        ], $form);

        $this->assertSame('Send Enquiry', $form->fresh()->submit_label);
    }

    public function test_the_listing_offers_a_copy_link_control(): void
    {
        $form = $this->makeForm([$this->field()], ['status' => Form::PUBLISHED]);

        $this->signedIn()->get(route('backend.forms.index'))
            ->assertOk()
            ->assertSee('Copy Link')
            ->assertSee('data-copy-url="' . $form->public_url . '"', false);
    }

    /* =============================== CREATE ================================ */

    public function test_an_admin_can_create_a_form_with_fields_and_options(): void
    {
        $this->signedIn()
            ->post(route('backend.forms.store'), $this->payload(['fields' => [
                'f0' => $this->field(['label' => 'Student Name']),
                'f1' => $this->field([
                    'label'      => 'Course Interested',
                    'field_type' => FormFieldType::DROPDOWN,
                    'options'    => [
                        ['label' => 'Python Full Stack', 'value' => 'python-full-stack'],
                        ['label' => 'Data Science', 'value' => ''],
                    ],
                ]),
            ]]))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $form = Form::firstOrFail();

        $this->assertSame('course-enquiry', $form->slug);
        $this->assertSame(Form::DRAFT, $form->status);
        $this->assertSame('Send Enquiry', $form->submit_label);
        $this->assertCount(2, $form->fields);

        $dropdown = $form->fields->last();
        $this->assertSame(FormFieldType::DROPDOWN, $dropdown->field_type);
        // A blank stored value falls back to the label.
        $this->assertSame(['python-full-stack', 'Data Science'], $dropdown->options->pluck('value')->all());
    }

    public function test_the_slug_is_generated_and_kept_unique(): void
    {
        $this->makeForm();
        $second = $this->makeForm([], ['slug' => '']);

        $this->assertSame('course-enquiry', Form::first()->slug);
        $this->assertSame('course-enquiry-2', $second->slug);
    }

    public function test_field_keys_are_generated_and_de_duplicated(): void
    {
        $form = $this->makeForm([
            $this->field(['label' => 'Name']),
            $this->field(['label' => 'Name']),
            $this->field(['label' => 'Name']),
        ]);

        $this->assertSame(['name', 'name_2', 'name_3'], $form->fields->pluck('field_key')->all());
    }

    /**
     * Punctuation in a label must not reach the key.
     *
     * Found by building a real form: "How satisfied are you?" generated
     * `how_satisfied_are_you?`, because Str::snake leaves punctuation alone. A
     * "?" merely looks wrong; a "." is read by the validator as nesting, and the
     * field's own rules would then never be applied to it.
     */
    public function test_punctuation_in_a_label_never_reaches_the_field_key(): void
    {
        $form = $this->makeForm([
            $this->field(['label' => 'How satisfied are you?']),
            $this->field(['label' => 'Mr. Smith / Ms. Jones']),
            $this->field(['label' => 'Rate us — honestly!']),
            $this->field(['label' => 'Email (work)']),
        ]);

        foreach ($form->fields as $field) {
            $this->assertMatchesRegularExpression(
                '/^[a-z0-9_]+$/',
                $field->field_key,
                "“{$field->label}” produced an unusable key: {$field->field_key}",
            );
        }

        $this->assertSame(
            ['how_satisfied_are_you', 'mr_smith_ms_jones', 'rate_us_honestly', 'email_work'],
            $form->fields->pluck('field_key')->all(),
        );
    }

    /**
     * A question labelled "Token" would otherwise generate the key `_token` and
     * quietly shadow the CSRF field on the public page.
     */
    public function test_a_field_key_cannot_collide_with_a_reserved_request_key(): void
    {
        $form = $this->makeForm([
            $this->field(['label' => 'Token', 'field_key' => '_token']),
        ]);

        $this->assertNotSame('_token', $form->fields->first()->field_key);
    }

    public function test_the_order_fields_are_posted_in_is_the_order_they_are_shown_in(): void
    {
        $form = $this->makeForm([
            $this->field(['label' => 'Third']),
            $this->field(['label' => 'First']),
            $this->field(['label' => 'Second']),
        ]);

        $this->assertSame(['Third', 'First', 'Second'], $form->fields->pluck('label')->all());
        $this->assertSame([0, 1, 2], $form->fields->pluck('sort_order')->all());
    }

    public function test_a_choice_field_with_no_options_is_refused(): void
    {
        $this->signedIn()
            ->post(route('backend.forms.store'), $this->payload(['fields' => [
                'f0' => $this->field(['label' => 'Course', 'field_type' => FormFieldType::DROPDOWN]),
            ]]))
            ->assertSessionHasErrors('fields.f0.options');

        $this->assertSame(0, Form::count());
    }

    public function test_a_blank_row_the_admin_abandoned_is_dropped_rather_than_blocking_the_save(): void
    {
        $form = $this->makeForm([
            $this->field(['label' => 'Student Name']),
            $this->field(['label' => '']),
        ]);

        $this->assertCount(1, $form->fields);
    }

    /* =============================== EDITING =============================== */

    /**
     * The rule the whole module hangs on: editing a form must not orphan the
     * answers already filed against its fields.
     */
    public function test_editing_a_form_keeps_existing_fields_and_their_responses(): void
    {
        $form  = $this->makeForm([$this->field(['label' => 'Course'])]);
        $field = $form->fields->first();

        $response = $form->responses()->create(['status' => 'New', 'submitted_at' => now()]);
        $response->values()->create([
            'field_id' => $field->id, 'field_key' => $field->field_key,
            'field_label' => 'Course', 'field_type' => $field->field_type, 'value' => 'Data Science',
        ]);

        // Rename the question — the same question, differently worded.
        app(FormBuilderService::class)->save($this->payload(['fields' => [
            $this->field(['id' => $field->id, 'label' => 'Select Your Preferred Course']),
        ]]), $form);

        $field->refresh();
        $this->assertSame('Select Your Preferred Course', $field->label);

        // The answer still points at it, and still says what was submitted.
        $value = $response->fresh()->values->first();
        $this->assertSame($field->id, $value->field_id);
        $this->assertSame('Data Science', $value->value);
        // ...under the wording it was actually asked under.
        $this->assertSame('Course', $value->field_label);
    }

    public function test_removing_a_field_that_has_answers_soft_deletes_it(): void
    {
        $form  = $this->makeForm([$this->field(['label' => 'Course'])]);
        $field = $form->fields->first();

        $response = $form->responses()->create(['status' => 'New', 'submitted_at' => now()]);
        $response->values()->create([
            'field_id' => $field->id, 'field_key' => $field->field_key,
            'field_label' => 'Course', 'field_type' => $field->field_type, 'value' => 'Data Science',
        ]);

        app(FormBuilderService::class)->save($this->payload(['fields' => []]), $form);

        $this->assertSoftDeleted('form_fields', ['id' => $field->id]);
        // The field leaves the form; the answer does not leave the response.
        $this->assertCount(0, $form->fresh()->fields);
        $this->assertDatabaseHas('form_response_values', ['field_id' => $field->id, 'value' => 'Data Science']);
    }

    public function test_removing_a_field_with_no_answers_deletes_it_outright(): void
    {
        $form  = $this->makeForm([$this->field(['label' => 'Unused'])]);
        $field = $form->fields->first();

        app(FormBuilderService::class)->save($this->payload(['fields' => []]), $form);

        $this->assertDatabaseMissing('form_fields', ['id' => $field->id]);
    }

    public function test_a_field_type_can_be_changed_and_keeps_its_label_and_options(): void
    {
        $form  = $this->makeForm([
            $this->field(['label' => 'Course', 'field_type' => FormFieldType::DROPDOWN, 'options' => [
                ['label' => 'Python', 'value' => 'python'],
            ]]),
        ]);
        $field = $form->fields->first();

        app(FormBuilderService::class)->save($this->payload(['fields' => [
            $this->field([
                'id' => $field->id, 'label' => 'Course', 'field_type' => FormFieldType::RADIO,
                'options' => [['label' => 'Python', 'value' => 'python']],
            ]),
        ]]), $form);

        $field->refresh()->load('options');

        $this->assertSame(FormFieldType::RADIO, $field->field_type);
        $this->assertSame('Course', $field->label);
        $this->assertSame(['python'], $field->options->pluck('value')->all());
    }

    /** Switching to a type with no options must not leave the old ones behind. */
    public function test_changing_to_a_type_without_options_clears_them(): void
    {
        $form  = $this->makeForm([
            $this->field(['label' => 'Course', 'field_type' => FormFieldType::DROPDOWN, 'options' => [
                ['label' => 'Python', 'value' => 'python'],
            ]]),
        ]);
        $field = $form->fields->first();

        app(FormBuilderService::class)->save($this->payload(['fields' => [
            $this->field(['id' => $field->id, 'label' => 'Course', 'field_type' => FormFieldType::SHORT_TEXT]),
        ]]), $form);

        $this->assertCount(0, $field->refresh()->options);
    }

    /** A posted id from another form must not let one form edit another's field. */
    public function test_a_field_id_from_another_form_cannot_be_hijacked(): void
    {
        $other = $this->makeForm([$this->field(['label' => 'Their Field'])], ['slug' => 'other-form']);
        $mine  = $this->makeForm([], ['slug' => 'my-form']);

        app(FormBuilderService::class)->save($this->payload(['fields' => [
            $this->field(['id' => $other->fields->first()->id, 'label' => 'Stolen']),
        ]]), $mine);

        $this->assertSame('Their Field', $other->fields->first()->fresh()->label);
        $this->assertSame('Stolen', $mine->fresh()->fields->first()->label);
    }

    /* ============================== LIFECYCLE ============================== */

    public function test_a_form_can_be_published_and_disabled(): void
    {
        $form = $this->makeForm([$this->field()]);

        $this->signedIn()->post(route('backend.forms.toggle', $form))->assertRedirect();
        $this->assertTrue($form->fresh()->isPublished());

        $this->signedIn()->post(route('backend.forms.toggle', $form))->assertRedirect();
        $this->assertSame(Form::DISABLED, $form->fresh()->status);
    }

    public function test_an_empty_form_cannot_be_published(): void
    {
        $form = $this->makeForm();

        $this->signedIn()->post(route('backend.forms.toggle', $form))->assertRedirect();

        $this->assertSame(Form::DRAFT, $form->fresh()->status);
    }

    public function test_duplicating_copies_the_fields_but_never_the_responses(): void
    {
        $form = $this->makeForm([
            $this->field(['label' => 'Course', 'field_type' => FormFieldType::DROPDOWN, 'options' => [
                ['label' => 'Python', 'value' => 'python'],
            ]]),
        ], ['status' => Form::PUBLISHED]);

        $form->responses()->create(['status' => 'New', 'submitted_at' => now()]);

        $this->signedIn()->post(route('backend.forms.duplicate', $form))->assertRedirect();

        $copy = Form::where('id', '!=', $form->id)->firstOrFail();

        $this->assertSame('Course Enquiry - Copy', $copy->name);
        $this->assertNotSame($form->slug, $copy->slug);
        // A copy of a live form is never itself live without a decision.
        $this->assertSame(Form::DRAFT, $copy->status);
        $this->assertSame(['python'], $copy->fields->first()->options->pluck('value')->all());
        $this->assertSame(0, $copy->responses()->count());
    }

    public function test_deleting_a_form_takes_its_fields_and_responses_with_it(): void
    {
        $form  = $this->makeForm([$this->field()]);
        $field = $form->fields->first();
        $form->responses()->create(['status' => 'New', 'submitted_at' => now()]);

        $this->signedIn()->delete(route('backend.forms.destroy', $form))->assertRedirect();

        $this->assertDatabaseMissing('forms', ['id' => $form->id]);
        $this->assertDatabaseMissing('form_fields', ['id' => $field->id]);
        $this->assertSame(0, FormResponse::count());
    }

    /* =============================== PREVIEW =============================== */

    public function test_the_preview_renders_the_real_configuration(): void
    {
        $form = $this->makeForm([
            $this->field(['label' => 'Student Name', 'placeholder' => 'Enter your name']),
            $this->field(['label' => 'Course', 'field_type' => FormFieldType::DROPDOWN, 'options' => [
                ['label' => 'Python Full Stack', 'value' => 'python'],
            ]]),
        ]);

        $this->signedIn()->get(route('backend.forms.preview', $form))
            ->assertOk()
            // The form's name is its heading, so that is what the preview shows.
            ->assertSee('Course Enquiry')
            ->assertSee('Student Name')
            ->assertSee('Enter your name', false)
            ->assertSee('Python Full Stack')
            ->assertSee('Send Enquiry');
    }

    /** The one thing a preview must never do is create a real response. */
    public function test_a_preview_submission_validates_but_saves_nothing(): void
    {
        $form = $this->makeForm([$this->field(['label' => 'Student Name', 'is_required' => 1])]);

        $this->signedIn()
            ->post(route('backend.forms.preview.submit', $form), [])
            ->assertSessionHasErrors('student_name');

        $this->signedIn()
            ->post(route('backend.forms.preview.submit', $form), ['student_name' => 'Arun'])
            ->assertSessionHasNoErrors();

        $this->assertSame(0, FormResponse::count());
    }

    /* =============================== SECURITY ============================== */

    public function test_the_whole_module_is_behind_the_admin_guard(): void
    {
        $form = $this->makeForm([$this->field()]);

        foreach ([
            route('backend.forms.index'),
            route('backend.forms.create'),
            route('backend.forms.edit', $form),
            route('backend.forms.show', $form),
            route('backend.forms.preview', $form),
            route('backend.forms.responses.index', $form),
        ] as $url) {
            $this->get($url)->assertRedirect(route('backend.auth.login'));
        }

        $this->post(route('backend.forms.store'), $this->payload())->assertRedirect(route('backend.auth.login'));
        $this->assertSame(1, Form::count());
    }

    /**
     * The module has to be grantable like every other, and the gate reads it
     * off the route name — including the nested response routes.
     */
    public function test_forms_is_a_grantable_module_covering_its_response_routes(): void
    {
        $this->assertTrue(\App\Support\AdminModules::isGrantable('forms'));
        $this->assertSame('forms', \App\Support\AdminModules::forRoute('backend.forms.index'));
        $this->assertSame('forms', \App\Support\AdminModules::forRoute('backend.forms.responses.show'));
    }

    public function test_an_admin_without_the_forms_module_is_refused(): void
    {
        $limited = User::create([
            'name' => 'Limited', 'email' => 'limited@example.com',
            'password' => bcrypt('secret1234'), 'is_super_admin' => false,
            'module_access' => ['blogs'], 'is_active' => true,
        ]);

        // The panel's gate keeps a signed-in admin inside it with an explanation
        // rather than dropping them on a bare 403 — see EnsureModuleAccess.
        $this->withSession([
            'admin_logged_in' => true, 'admin_id' => $limited->id,
            'admin_name' => $limited->name, 'admin_email' => $limited->email,
        ])
            ->get(route('backend.forms.index'))
            ->assertRedirect(route('backend.dashboard'))
            ->assertSessionHas('error');
    }

    /* ======================== LEAVING WITH UNSAVED CHANGES ==================
       The builder's "unsaved changes" dialog saves and then carries on to the
       page that was clicked. That address arrives from the browser, so it may
       only ever be a page of this site. */

    public function test_saving_on_the_way_out_carries_on_to_the_page_that_was_clicked(): void
    {
        $form = app(\App\Services\FormBuilderService::class)->save([
            'name' => 'Enquiry', 'status' => Form::PUBLISHED, 'fields' => [$this->field()],
        ]);

        $next = route('backend.forms.all-responses');

        $this->signedIn()->put(route('backend.forms.update', $form), [
            'name'       => 'Enquiry (renamed)',
            'status'     => Form::PUBLISHED,
            'fields'     => [['id' => $form->fields->first()->id] + $this->field()],
            'after_save' => $next,
        ])->assertRedirect($next)->assertSessionHas('success');

        $this->assertSame('Enquiry (renamed)', $form->fresh()->name);
    }

    public function test_creating_on_the_way_out_carries_on_too(): void
    {
        $next = route('backend.forms.index');

        $this->signedIn()->post(route('backend.forms.store'), [
            'name'       => 'Made On The Way Out',
            'fields'     => ['f0' => $this->field()],
            'after_save' => $next,
        ])->assertRedirect($next);

        $this->assertSame(1, Form::where('name', 'Made On The Way Out')->count());
    }

    /** Never an open redirect: anything off this site lands back on the builder. */
    public function test_an_address_off_this_site_is_ignored_after_saving(): void
    {
        $form = app(\App\Services\FormBuilderService::class)->save([
            'name' => 'Enquiry', 'status' => Form::PUBLISHED, 'fields' => [$this->field()],
        ]);

        $home = rtrim(url('/'), '/');

        foreach ([
            'https://evil.example/admin',
            '//evil.example/admin',
            'javascript:alert(1)',
            $home . '.evil.example/admin',     // looks like the site, is not
            $home . '@evil.example/admin',     // credentials trick
            $home . "/admin\r\nLocation: https://evil.example",
        ] as $next) {
            $this->signedIn()->put(route('backend.forms.update', $form), [
                'name'       => 'Enquiry',
                'status'     => Form::PUBLISHED,
                'fields'     => [['id' => $form->fields->first()->id] + $this->field()],
                'after_save' => $next,
            ])->assertRedirect(route('backend.forms.edit', $form));
        }
    }

    public function test_a_failed_save_on_the_way_out_stays_on_the_builder_with_the_work(): void
    {
        $this->signedIn()
            ->from(route('backend.forms.create'))
            ->post(route('backend.forms.store'), [
                'name'       => '',
                'fields'     => ['f0' => $this->field(['label' => 'Kept after the error'])],
                'after_save' => route('backend.forms.index'),
            ])
            ->assertRedirect(route('backend.forms.create'))
            ->assertSessionHasErrors('name')
            ->assertSessionHasInput('fields.f0.label', 'Kept after the error');

        $this->assertSame(0, Form::count());
    }

    public function test_the_builder_carries_the_unsaved_changes_dialog(): void
    {
        $this->signedIn()->get(route('backend.forms.create'))->assertOk()
            ->assertSee('id="leaveDialog"', false)
            ->assertSee('Create this form before leaving?')
            ->assertSee('name="after_save"', false);

        $form = app(\App\Services\FormBuilderService::class)->save([
            'name' => 'Enquiry', 'status' => Form::PUBLISHED, 'fields' => [$this->field()],
        ]);

        $this->signedIn()->get(route('backend.forms.edit', $form))->assertOk()
            ->assertSee('Save your changes?')
            ->assertSee('Leave without saving')
            ->assertSee('Stay on this page');
    }
}
