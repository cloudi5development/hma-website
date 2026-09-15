<?php

namespace Tests\Feature\Backend;

use App\Models\Form;
use App\Models\FormResponse;
use App\Models\User;
use App\Services\FormBuilderService;
use App\Support\FormFieldType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The four shapes a form can be, built from one set of tables.
 *
 * What these tests are really defending is the claim that makes the module
 * worth having: that plain, sectioned, multi-page and multi-page-with-sections
 * are one system rather than three. So they check not only that each shape
 * renders, but that a form can be moved between them without losing a question
 * — and, crucially, without losing the ANSWERS already filed against them.
 */
class FormStructureTest extends TestCase
{
    use RefreshDatabase;

    private function builder(): FormBuilderService
    {
        return app(FormBuilderService::class);
    }

    private function asAdmin(): self
    {
        $admin = User::where('is_super_admin', true)->firstOrFail();

        return $this->withSession(['admin_logged_in' => true, 'admin_id' => $admin->id]);
    }

    /** One question row as the builder posts it. */
    private function q(string $label, string $type = FormFieldType::SHORT_TEXT, array $extra = []): array
    {
        return $extra + ['label' => $label, 'field_type' => $type, 'is_required' => 0];
    }

    private function save(array $data, ?Form $form = null): Form
    {
        return $this->builder()->save($data + [
            'name'   => 'Test Form',
            'status' => Form::PUBLISHED,
        ], $form);
    }

    private function page(Form $form, string $slug = null)
    {
        return $this->get(route('frontend.form.show', $slug ?? $form->slug));
    }

    /* ========================= THE FOUR STRUCTURES =========================
       Named for the acceptance criteria they stand in for. */

    /** TEST 1 — "Course Enquiry": a plain form, eight questions. */
    public function test_a_plain_form_is_one_page_with_no_grouping(): void
    {
        $labels = ['Full Name', 'Email', 'Phone', 'City', 'Course', 'Heard From', 'Start Date', 'Message'];

        $form = $this->save([
            'name'           => 'Course Enquiry',
            'structure_type' => Form::PLAIN,
            'fields'         => array_map(fn ($l) => $this->q($l), $labels),
        ]);

        $this->assertSame(8, $form->fields()->count());
        $this->assertSame(0, $form->pages()->count());
        $this->assertSame(0, $form->sections()->count());

        // Both placement columns stay null — a plain form is the shape that
        // needs neither, and that is what an older form already looks like.
        $this->assertSame(0, $form->fields()->whereNotNull('form_page_id')->count());
        $this->assertSame(0, $form->fields()->whereNotNull('form_section_id')->count());

        $page = $this->page($form)->assertOk();

        foreach ($labels as $label) {
            $page->assertSee($label);
        }

        // No step chrome and no section headings on a form that has neither —
        // and no card and no tinted ground either. A box drawn around every
        // question on a plain form would be a box inside a box saying nothing.
        $page->assertDontSee('class="hmf-steps__nav"', false)
            ->assertDontSee('class="hmf-sec__title"', false)
            ->assertDontSee('hmf-sec--card', false)
            ->assertDontSee('hmf__body--grouped', false);
    }

    /** TEST 2 — "Student Registration": one page, three named groups. */
    public function test_a_sectioned_form_groups_its_questions_under_headings(): void
    {
        $form = $this->save([
            'name'           => 'Student Registration',
            'structure_type' => Form::SECTIONS,
            'sections'       => [
                'a' => ['title' => 'Personal Information', 'description' => 'Tell us about yourself'],
                'b' => ['title' => 'Education'],
                'c' => ['title' => 'Contact Information'],
            ],
            'fields' => [
                $this->q('Full Name', FormFieldType::SHORT_TEXT, ['section_ref' => 'a']),
                $this->q('Qualification', FormFieldType::SHORT_TEXT, ['section_ref' => 'b']),
                $this->q('Email', FormFieldType::EMAIL, ['section_ref' => 'c']),
            ],
        ]);

        $this->assertSame(3, $form->sections()->count());
        $this->assertSame(0, $form->pages()->count());
        // Sections on a single-page form hang off the form, not off a page.
        $this->assertSame(3, $form->sections()->whereNull('form_page_id')->count());

        $page = $this->page($form)->assertOk()
            ->assertSee('Personal Information')
            ->assertSee('Tell us about yourself')
            ->assertSee('Education')
            ->assertSee('class="hmf-sec__title"', false)
            // Grouped, but still one page: no Next button.
            ->assertDontSee('class="hmf-steps__nav"', false);

        // Each group is its own container on a ground that lets it show —
        // one card per named section, no more.
        $this->assertSame(3, substr_count($page->getContent(), 'hmf-sec--card'));
        $page->assertSee('hmf__body--grouped', false);
    }

    /** TEST 3 — "Job Application": four steps with Next / Back and a progress indicator. */
    public function test_a_multi_page_form_renders_steps_with_navigation(): void
    {
        $form = $this->save([
            'name'           => 'Job Application',
            'structure_type' => Form::PAGES,
            'pages'          => [
                'p1' => ['title' => 'Personal Details'],
                'p2' => ['title' => 'Experience'],
                'p3' => ['title' => 'Documents'],
                'p4' => ['title' => 'Confirmation'],
            ],
            'fields' => [
                $this->q('Full Name', FormFieldType::SHORT_TEXT, ['page_ref' => 'p1']),
                $this->q('Years of Experience', FormFieldType::NUMBER, ['page_ref' => 'p2']),
                $this->q('Portfolio Link', FormFieldType::SHORT_TEXT, ['page_ref' => 'p3']),
                $this->q('Anything Else', FormFieldType::LONG_TEXT, ['page_ref' => 'p4']),
            ],
        ]);

        $this->assertSame(4, $form->pages()->count());

        $page = $this->page($form)->assertOk();

        // One progress segment per step: the indicator says how many steps
        // there are as well as how far along you are.
        $this->assertSame(4, substr_count($page->getContent(), 'data-step-seg='));

        $page->assertSee('class="hmf-steps__nav"', false)
            ->assertSee('of 4')
            ->assertSee('Personal Details')
            ->assertSee('Confirmation')
            // Each page is NAMED once — on its step. Printing it again as a
            // heading directly underneath gave the form two titles saying the
            // same word.
            ->assertSee('Experience')
            // Every step is in the document — the stepping is presentation, and
            // the whole form posts in one request.
            ->assertSee('data-step="0"', false)
            ->assertSee('data-step="3"', false)
            ->assertSee('name="anything_else"', false);

        // Counted as rendered TEXT (between tags), not as raw string: the step
        // also carries the name in an aria-label, which is the point — a screen
        // reader announces the step, and the page does not print the word twice.
        $this->assertSame(
            1,
            substr_count($page->getContent(), '>Personal Details<'),
            'a page name should be shown once, on its step — not again as a heading',
        );
    }

    public function test_a_stepped_form_is_still_submittable_without_javascript(): void
    {
        $form = $this->save([
            'structure_type' => Form::PAGES,
            'pages'          => ['p1' => ['title' => 'One'], 'p2' => ['title' => 'Two']],
            'fields'         => [
                $this->q('Full Name', FormFieldType::SHORT_TEXT, ['page_ref' => 'p1']),
                $this->q('Notes', FormFieldType::LONG_TEXT, ['page_ref' => 'p2']),
            ],
        ]);

        $html = $this->page($form)->assertOk()->getContent();

        /* The submit button is rendered in the document OUTSIDE the Back/Next
           bar, which the script moves it into once stepping is running. That
           order is the whole of the no-JavaScript guarantee: with the script
           absent nothing moves, the bar is hidden by the noscript rule, every
           step is shown, and the button is right where the markup put it. */
        $nav    = strpos($html, 'class="hmf-steps__nav"');
        $submit = strpos($html, 'data-submit-row');

        $this->assertNotFalse($submit, 'the submit button must be in the markup, not built by the script');
        $this->assertGreaterThan($nav, $submit, 'the submit button belongs after the bar in the document');

        $this->assertStringContainsString('<noscript>', $html);
        $this->assertStringContainsString('.hmf-step[hidden] { display: block !important; }', $html);
        $this->assertStringContainsString('.hmf-steps__nav { display: none !important; }', str_replace(
            '.hmf-steps__head, .hmf-steps__nav', '.hmf-steps__nav', $html,
        ));

        // And it really does take a submission with no stepping involved.
        $this->post(route('frontend.form.submit', $form->slug), [
            'full_name' => 'Arun Kumar', 'notes' => 'No JavaScript here',
        ])->assertRedirect()->assertSessionHas('form_success');

        $this->assertSame('No JavaScript here', FormResponse::firstOrFail()->keyed()['notes']->value);
    }

    public function test_a_page_description_is_still_shown_above_its_questions(): void
    {
        $form = $this->save([
            'structure_type' => Form::PAGES,
            'pages'          => [
                'p1' => ['title' => 'Your Details', 'description' => 'This takes about two minutes.'],
                'p2' => ['title' => 'Documents'],
            ],
            'fields' => [
                $this->q('Full Name', FormFieldType::SHORT_TEXT, ['page_ref' => 'p1']),
                $this->q('Portfolio', FormFieldType::SHORT_TEXT, ['page_ref' => 'p2']),
            ],
        ]);

        // Only the NAME is dropped from the step body — a description repeats
        // nothing, so it is still the one place it appears.
        $this->page($form)->assertOk()
            ->assertSee('This takes about two minutes.')
            ->assertSee('class="hmf-step__sub"', false);
    }

    /** TEST 4 — "Detailed Admission Form": sections inside pages. */
    public function test_a_multi_page_form_can_group_each_page_into_sections(): void
    {
        $form = $this->save([
            'name'           => 'Detailed Admission',
            'structure_type' => Form::PAGES_SECTIONS,
            'pages'          => ['p1' => ['title' => 'About You'], 'p2' => ['title' => 'Background']],
            'sections'       => [
                'a' => ['page_ref' => 'p1', 'title' => 'Personal Information'],
                'b' => ['page_ref' => 'p1', 'title' => 'Contact Information'],
                'c' => ['page_ref' => 'p2', 'title' => 'Education'],
            ],
            'fields' => [
                $this->q('Full Name', FormFieldType::SHORT_TEXT, ['section_ref' => 'a']),
                $this->q('Email', FormFieldType::EMAIL, ['section_ref' => 'b']),
                $this->q('Qualification', FormFieldType::SHORT_TEXT, ['section_ref' => 'c']),
            ],
        ]);

        $pages = $form->pages;

        // A section knows its page, and so does every question in it — taken
        // from the section rather than posted separately, so the two cannot
        // disagree.
        $this->assertSame(2, $form->sections()->where('form_page_id', $pages[0]->id)->count());
        $this->assertSame(1, $form->sections()->where('form_page_id', $pages[1]->id)->count());

        $email = $form->fields->firstWhere('field_key', 'email');
        $this->assertSame($pages[0]->id, $email->form_page_id);
        $this->assertNotNull($email->form_section_id);

        $this->page($form)->assertOk()
            ->assertSee('class="hmf-steps__nav"', false)
            ->assertSee('of 2')
            ->assertSee('Personal Information')
            ->assertSee('Education');
    }

    /* ============================== SWITCHING ==============================
       The claim that these are one system and not three. */

    public function test_switching_structure_never_loses_a_question_or_its_answers(): void
    {
        $form = $this->save([
            'name'           => 'Admission',
            'structure_type' => Form::PAGES_SECTIONS,
            'pages'          => ['p1' => ['title' => 'One'], 'p2' => ['title' => 'Two']],
            'sections'       => [
                'a' => ['page_ref' => 'p1', 'title' => 'Personal'],
                'b' => ['page_ref' => 'p2', 'title' => 'Education'],
            ],
            'fields' => [
                $this->q('Full Name', FormFieldType::SHORT_TEXT, ['section_ref' => 'a']),
                $this->q('Qualification', FormFieldType::SHORT_TEXT, ['section_ref' => 'b']),
            ],
        ]);

        // An answer that must survive everything below.
        $this->post(route('frontend.form.submit', $form->slug), [
            'full_name' => 'Keerthika KT', 'qualification' => 'BSc',
        ])->assertRedirect();

        $ids = $form->fields->pluck('id')->sort()->values()->all();
        $this->assertCount(2, $ids);

        /* The browser posts the whole nesting whatever the structure; the
           service keeps only what the chosen structure allows.

           The containers are posted WITH their ids while they exist and without
           once a plain save has dropped them, which is what the screen really
           does: a page card carries the id of a saved page, and a fresh one
           carries nothing. The questions always carry theirs — that is the part
           that must never change. */
        $repost = function (string $structure) use (&$form, $ids) {
            $form->refresh()->load(['pages', 'sections']);

            $keep = fn ($model) => $model ? ['id' => $model->id] : [];

            $this->save([
                'name'           => 'Admission',
                'structure_type' => $structure,
                'pages'          => [
                    'p1' => $keep($form->pages->get(0)) + ['title' => 'One'],
                    'p2' => $keep($form->pages->get(1)) + ['title' => 'Two'],
                ],
                'sections' => [
                    'a' => $keep($form->sections->get(0)) + ['page_ref' => 'p1', 'title' => 'Personal'],
                    'b' => $keep($form->sections->get(1)) + ['page_ref' => 'p2', 'title' => 'Education'],
                ],
                'fields' => [
                    ['id' => $ids[0], 'section_ref' => 'a'] + $this->q('Full Name'),
                    ['id' => $ids[1], 'section_ref' => 'b'] + $this->q('Qualification'),
                ],
            ], $form);
        };

        foreach ([Form::PLAIN, Form::SECTIONS, Form::PAGES, Form::PAGES_SECTIONS, Form::PLAIN] as $structure) {
            $repost($structure);
            $form->refresh()->load(['fields', 'pages', 'sections']);

            $this->assertSame($structure, $form->structure_type);

            // The same two questions, by id — not replacements wearing the same
            // labels. Anything else would have cut them off from their answers.
            $this->assertSame($ids, $form->fields->pluck('id')->sort()->values()->all(), "after switching to {$structure}");

            // Containers only exist where the structure says they can.
            $this->assertSame($form->hasPages() ? 2 : 0, $form->pages->count(), "pages after {$structure}");
            $this->assertSame($form->hasSections() ? 2 : 0, $form->sections->count(), "sections after {$structure}");
        }

        // And the answer is still readable, still against the right question.
        $response = FormResponse::firstOrFail();
        $this->assertSame('Keerthika KT', $response->keyed()['full_name']->value);
        $this->assertSame('BSc', $response->keyed()['qualification']->value);
    }

    public function test_an_older_form_with_no_structure_behaves_as_a_plain_one(): void
    {
        $form = $this->save(['fields' => [$this->q('Full Name')]]);

        // Nothing set the column: this is the shape every form built before
        // pages existed is in.
        $this->assertSame(Form::PLAIN, $form->structure_type);
        $this->assertFalse($form->hasPages());
        $this->assertFalse($form->hasSections());

        $this->page($form)->assertOk()
            ->assertSee('Full Name')
            ->assertDontSee('class="hmf-steps__nav"', false);
    }

    /* ============================ THE SAFETY NET ===========================
       Placement is nullable and is cleared rather than cascaded, so questions
       really can come loose. A question nobody can see is a question nobody
       can answer, so none may ever be dropped. */

    public function test_a_question_whose_page_was_deleted_is_still_rendered(): void
    {
        $form = $this->save([
            'structure_type' => Form::PAGES,
            'pages'          => ['p1' => ['title' => 'One'], 'p2' => ['title' => 'Two']],
            'fields'         => [
                $this->q('Full Name', FormFieldType::SHORT_TEXT, ['page_ref' => 'p1']),
                $this->q('Orphan Question', FormFieldType::SHORT_TEXT, ['page_ref' => 'p2']),
            ],
        ]);

        // Straight out of the database, the way a cascade or a hand-edit would.
        $form->pages[1]->delete();

        $form->refresh()->load(['fields', 'pages', 'sections']);

        $this->assertNull($form->fields->firstWhere('label', 'Orphan Question')->form_page_id);

        $this->page($form)->assertOk()
            ->assertSee('Orphan Question')
            ->assertSee('name="orphan_question"', false);
    }

    public function test_a_section_with_no_questions_is_not_drawn_on_the_public_page(): void
    {
        $form = $this->save([
            'structure_type' => Form::SECTIONS,
            'sections'       => [
                'a' => ['title' => 'Has Questions'],
                'b' => ['title' => 'Completely Empty'],
            ],
            'fields' => [$this->q('Full Name', FormFieldType::SHORT_TEXT, ['section_ref' => 'a'])],
        ]);

        // The builder keeps it — it is a section the admin has not filled in
        // yet — but a heading over nothing is not shown to a visitor.
        $this->assertSame(2, $form->sections()->count());

        $this->page($form)->assertOk()
            ->assertSee('Has Questions')
            ->assertDontSee('Completely Empty');
    }

    public function test_a_multi_page_form_with_only_one_page_is_drawn_without_a_stepper(): void
    {
        $form = $this->save([
            'structure_type' => Form::PAGES,
            'pages'          => ['p1' => ['title' => 'Only One']],
            'fields'         => [$this->q('Full Name', FormFieldType::SHORT_TEXT, ['page_ref' => 'p1'])],
        ]);

        // "Step 1 of 1" with a Next button that has nowhere to go would be
        // worse than no stepper at all.
        $this->page($form)->assertOk()
            ->assertSee('Full Name')
            ->assertDontSee('class="hmf-steps__nav"', false);
    }

    /* ============================= SUBMISSION ============================== */

    public function test_a_multi_page_form_is_submitted_in_one_request(): void
    {
        $form = $this->save([
            'structure_type' => Form::PAGES,
            'pages'          => ['p1' => ['title' => 'One'], 'p2' => ['title' => 'Two'], 'p3' => ['title' => 'Three']],
            'fields'         => [
                $this->q('Full Name', FormFieldType::SHORT_TEXT, ['page_ref' => 'p1']),
                $this->q('Experience', FormFieldType::NUMBER, ['page_ref' => 'p2']),
                $this->q('Notes', FormFieldType::LONG_TEXT, ['page_ref' => 'p3']),
            ],
        ]);

        $this->post(route('frontend.form.submit', $form->slug), [
            'full_name' => 'Arun Kumar', 'experience' => 4, 'notes' => 'Available from March',
        ])->assertRedirect()->assertSessionHas('form_success');

        // Answers from all three steps, stored exactly as a plain form's would
        // be — nothing in the response pipeline knows what a page is.
        $values = FormResponse::firstOrFail()->keyed();

        $this->assertSame('Arun Kumar', $values['full_name']->value);
        $this->assertSame('4', (string) $values['experience']->value);
        $this->assertSame('Available from March', $values['notes']->value);
    }

    /* ================================ QUIZ =================================
       A quiz is read as a numbered list: one question per row, its answers one
       under another, and no description line under it. A standard form keeps
       the two-to-a-row layout with its choices flowing along the line. */

    public function test_a_quiz_numbers_its_questions_straight_through_its_sections(): void
    {
        $form = $this->save([
            'structure_type' => Form::SECTIONS,
            'form_type'      => Form::QUIZ,
            'sections'       => ['a' => ['title' => 'Basics'], 'b' => ['title' => 'Advanced']],
            'fields'         => [
                $this->q('What is Python?', FormFieldType::RADIO, ['section_ref' => 'a', 'options' => [['label' => 'A language'], ['label' => 'A snake']]]),
                $this->q('Tracking code', FormFieldType::HIDDEN, ['section_ref' => 'a', 'default_value' => 'x']),
                $this->q('Which symbol starts a comment?', FormFieldType::RADIO, ['section_ref' => 'b', 'options' => [['label' => '#'], ['label' => '//']]]),
                $this->q('Your name', FormFieldType::SHORT_TEXT, ['section_ref' => 'b']),
            ],
        ]);

        $html = $this->page($form)->assertOk()->getContent();

        // 1, 2, 3 across both sections — the new section does not restart at 1,
        // and the hidden field, which nobody sees, is not counted.
        $this->assertMatchesRegularExpression('#<span class="hmf-num">1\.</span>\s*What is Python\?#', $html);
        $this->assertMatchesRegularExpression('#<span class="hmf-num">2\.</span>\s*Which symbol starts a comment\?#', $html);
        $this->assertMatchesRegularExpression('#<span class="hmf-num">3\.</span>\s*Your name#', $html);
        $this->assertSame(3, substr_count($html, 'class="hmf-num"'));
    }

    public function test_a_quiz_lists_its_answers_one_per_line_with_no_description(): void
    {
        $form = $this->save([
            'form_type' => Form::QUIZ,
            'fields'    => [$this->q('What is Python?', FormFieldType::RADIO, [
                'options'   => [['label' => 'Programming Language'], ['label' => 'Database'], ['label' => 'OS']],
                'help_text' => 'Choose one',
            ])],
        ]);

        $page = $this->page($form)->assertOk();

        // The same element carries both classes (Blade leaves a double space).
        $this->assertMatchesRegularExpression('/class="hmf-choices\s+hmf-choices--stacked\s*"/', $page->getContent());

        $page->assertSee('hmf-qs--quiz', false)
            // One question to a row.
            ->assertSee('hmf-field--wide', false)
            // The description is kept on the question, but not drawn on a quiz.
            ->assertDontSee('Choose one')
            ->assertDontSee('_help"', false);

        $this->assertSame('Choose one', $form->fields->first()->help_text);
    }

    public function test_a_standard_form_keeps_its_flowing_choices_its_descriptions_and_no_numbers(): void
    {
        $form = $this->save([
            'form_type' => Form::STANDARD,
            'fields'    => [$this->q('Gender', FormFieldType::RADIO, [
                'options'   => [['label' => 'Male'], ['label' => 'Female'], ['label' => 'Other']],
                'help_text' => 'Choose one',
            ])],
        ]);

        $this->page($form)->assertOk()
            ->assertSee('Choose one')
            ->assertDontSee('hmf-choices--stacked', false)
            ->assertDontSee('hmf-qs--quiz', false)
            ->assertDontSee('class="hmf-num"', false);
    }

    /* ============================== DUPLICATE ============================== */

    public function test_duplicating_a_structured_form_gives_the_copy_its_own_pages(): void
    {
        $form = $this->save([
            'name'           => 'Admission',
            'structure_type' => Form::PAGES_SECTIONS,
            'pages'          => ['p1' => ['title' => 'About You']],
            'sections'       => ['a' => ['page_ref' => 'p1', 'title' => 'Personal']],
            'fields'         => [$this->q('Full Name', FormFieldType::SHORT_TEXT, ['section_ref' => 'a'])],
        ]);

        $copy = $this->builder()->duplicate($form);
        $copy->load(['fields', 'pages', 'sections']);

        $this->assertSame(Form::PAGES_SECTIONS, $copy->structure_type);
        $this->assertSame(1, $copy->pages->count());
        $this->assertSame(1, $copy->sections->count());

        // Its own containers, not the original's — otherwise editing the copy
        // would rearrange the form it was copied from.
        $this->assertNotSame($form->pages[0]->id, $copy->pages[0]->id);
        $this->assertSame($copy->pages[0]->id, $copy->sections[0]->form_page_id);
        $this->assertSame($copy->sections[0]->id, $copy->fields[0]->form_section_id);
        $this->assertSame($copy->pages[0]->id, $copy->fields[0]->form_page_id);
    }

    /* ============================== THE PANEL ============================== */

    public function test_the_builder_offers_all_four_structures(): void
    {
        $page = $this->asAdmin()->get(route('backend.forms.create'))->assertOk();

        foreach (Form::STRUCTURES as $value => $spec) {
            $page->assertSee($spec['label'])
                ->assertSee('value="' . $value . '"', false);
        }

        // Plain is where a new form starts: it asks the admin to configure
        // nothing, and the other three are one click away.
        $page->assertSee('name="structure_type"', false);
        $this->assertMatchesRegularExpression('/value="plain"[^>]*checked/s', $page->getContent());
    }

    public function test_the_builder_renders_every_question_of_a_structured_form(): void
    {
        $form = $this->save([
            'structure_type' => Form::PAGES_SECTIONS,
            'pages'          => ['p1' => ['title' => 'About You'], 'p2' => ['title' => 'Background']],
            'sections'       => [
                'a' => ['page_ref' => 'p1', 'title' => 'Personal'],
                'b' => ['page_ref' => 'p2', 'title' => 'Education'],
            ],
            'fields' => [
                $this->q('Full Name', FormFieldType::SHORT_TEXT, ['section_ref' => 'a']),
                $this->q('Qualification', FormFieldType::SHORT_TEXT, ['section_ref' => 'b']),
            ],
        ]);

        $this->asAdmin()->get(route('backend.forms.edit', $form))->assertOk()
            ->assertSee('About You')
            ->assertSee('Background')
            ->assertSee('Personal')
            ->assertSee('Education')
            // Both questions, including the one on the page that is not the
            // one being shown — every page is in the document.
            ->assertSee('value="Full Name"', false)
            ->assertSee('value="Qualification"', false);
    }

    public function test_the_preview_shows_the_same_structure_as_the_live_form(): void
    {
        $form = $this->save([
            'structure_type' => Form::PAGES,
            'pages'          => ['p1' => ['title' => 'Step One'], 'p2' => ['title' => 'Step Two']],
            'fields'         => [
                $this->q('Full Name', FormFieldType::SHORT_TEXT, ['page_ref' => 'p1']),
                $this->q('Notes', FormFieldType::LONG_TEXT, ['page_ref' => 'p2']),
            ],
        ]);

        $this->asAdmin()->get(route('backend.forms.preview', $form))->assertOk()
            ->assertSee('class="hmf-steps__nav"', false)
            ->assertSee('Step One')
            ->assertSee('Step Two');
    }
}
