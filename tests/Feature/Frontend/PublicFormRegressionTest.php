<?php

namespace Tests\Feature\Frontend;

use App\Models\Form;
use App\Models\FormResponse;
use App\Services\FormBuilderService;
use App\Services\FormSubmissionService;
use App\Support\FormFieldType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PDOException;
use Tests\TestCase;

/**
 * Regressions found by testing the public form end to end.
 *
 * Almost all of these were invisible to the existing tests because those
 * checked the ERROR BAG after a refused submission — never the page the visitor
 * is then sent back to. That page is where they broke.
 */
class PublicFormRegressionTest extends TestCase
{
    use RefreshDatabase;

    private function form(array $fields, array $overrides = []): Form
    {
        return app(FormBuilderService::class)->save($overrides + [
            'name'   => 'Regression Form',
            'status' => Form::PUBLISHED,
            'fields' => $fields,
        ]);
    }

    private function choices(array $labels): array
    {
        return array_map(fn ($label) => ['label' => $label, 'value' => ''], $labels);
    }

    /** POST from the form's page and follow the redirect back to it. */
    private function roundTrip(Form $form, array $data, array $files = [])
    {
        $url = route('frontend.form.show', $form->slug);

        return $this->from($url)->followingRedirects()->post(route('frontend.form.submit', $form->slug), $data + $files);
    }

    /* ======================= THE PAGE AFTER AN ERROR ======================= */

    /**
     * A wildcard error (one grid row, one ticked box, one of several files)
     * comes back from the message bag GROUPED under its own key, and printing
     * the group as text was a 500 — for a visitor who had only left a grid row
     * blank.
     */
    public function test_the_page_after_an_error_renders_for_every_kind_of_list_answer(): void
    {
        Storage::fake('local');

        $form = $this->form([
            ['field_type' => FormFieldType::SHORT_TEXT, 'label' => 'Name', 'is_required' => 1],
            ['field_type' => FormFieldType::MC_GRID, 'label' => 'Rate', 'is_required' => 1,
                'rows' => $this->choices(['Product', 'Support']), 'columns' => $this->choices(['Good', 'Bad'])],
            ['field_type' => FormFieldType::CHECKBOX, 'label' => 'Skills', 'is_required' => 0, 'options' => $this->choices(['PHP', 'Laravel'])],
            ['field_type' => FormFieldType::FILE, 'label' => 'Docs', 'is_required' => 0, 'file_types' => ['pdf'], 'multiple' => 1],
        ]);

        $valid = ['name' => 'Asha', 'rate' => [0 => 'Good', 1 => 'Bad']];

        // A grid row left blank.
        $this->roundTrip($form, ['name' => 'Asha', 'rate' => [0 => 'Good']])
            ->assertOk()
            ->assertSee('Answer “Support” under “Rate”.', false);

        // A ticked box that is not one of the options.
        $this->roundTrip($form, $valid + ['skills' => ['PHP', 'Cobol']])->assertOk();

        // One good file and one refused.
        $this->roundTrip($form, $valid, ['docs' => [
            UploadedFile::fake()->create('ok.pdf', 4, 'application/pdf'),
            UploadedFile::fake()->create('bad.exe', 4, 'application/x-msdownload'),
        ]])->assertOk();

        $this->assertSame(0, FormResponse::count());
    }

    /** A list posted where one answer belongs is refused and redrawn — not a 500. */
    public function test_an_array_posted_for_a_single_answer_redraws_the_form(): void
    {
        $form = $this->form([
            ['field_type' => FormFieldType::SHORT_TEXT, 'label' => 'Name', 'is_required' => 1],
            ['field_type' => FormFieldType::NUMBER, 'label' => 'Age', 'is_required' => 0],
            ['field_type' => FormFieldType::LINEAR_SCALE, 'label' => 'Score', 'is_required' => 0],
            ['field_type' => FormFieldType::RATING, 'label' => 'Stars', 'is_required' => 0],
            ['field_type' => FormFieldType::HIDDEN, 'label' => 'Source', 'default_value' => 'web'],
        ]);

        $this->roundTrip($form, ['name' => ['a', 'b'], 'age' => ['1'], 'score' => ['2'], 'stars' => ['3'], 'source' => ['x']])
            ->assertOk()
            ->assertSee('name="name"', false);
    }

    public function test_a_blank_grid_row_is_named_once_and_the_other_answer_is_kept(): void
    {
        $form = $this->form([
            ['field_type' => FormFieldType::MC_GRID, 'label' => 'Rate', 'is_required' => 1,
                'rows' => $this->choices(['Product', 'Support']), 'columns' => $this->choices(['Good', 'Bad'])],
        ]);

        $html = $this->roundTrip($form, ['rate' => [0 => 'Good']])->assertOk()->getContent();

        $this->assertSame(1, substr_count(html_entity_decode($html), 'Answer “Support” under “Rate”.'));
        $this->assertMatchesRegularExpression('/name="rate\[0\]"[^>]*value="Good"[^>]*checked/s', $html);
    }

    /* ============================ MOBILE NUMBER ============================ */

    /** An 11- or 15-digit "mobile number" was accepted and stored. It must be ten digits. */
    public function test_a_mobile_number_must_be_exactly_ten_digits(): void
    {
        $form = $this->form([['field_type' => FormFieldType::MOBILE, 'label' => 'Mobile', 'is_required' => 1]]);
        $url  = route('frontend.form.submit', $form->slug);

        foreach (['98765432101', '987654321012345', '987654321', 'call me', '+1 98765 43210', '919876543210'] as $wrong) {
            $this->post($url, ['mobile' => $wrong])->assertSessionHasErrors('mobile');
        }

        $this->assertSame(0, FormResponse::count());

        $errors = $this->post($url, ['mobile' => '98765432101'])->getSession()->get('errors');
        $this->assertSame('Enter a valid 10-digit mobile number for “Mobile”.', $errors->first('mobile'));
    }

    /** What people type around a number is fine; it is stored as the ten digits. */
    public function test_a_mobile_number_is_accepted_as_typed_and_stored_as_ten_digits(): void
    {
        $form = $this->form([['field_type' => FormFieldType::MOBILE, 'label' => 'Mobile', 'is_required' => 1]]);

        foreach (['9876543210', '98765 43210', '98765-43210', '+91 98765 43210', '+919876543210', '(987) 654-3210'] as $typed) {
            $this->post(route('frontend.form.submit', $form->slug), ['mobile' => $typed])->assertSessionHasNoErrors();
        }

        $stored = \App\Models\FormResponseValue::where('field_key', 'mobile')->pluck('value')->unique()->values()->all();
        $this->assertSame(['9876543210'], $stored);
    }

    public function test_the_mobile_input_only_takes_ten_digits(): void
    {
        $form = $this->form([['field_type' => FormFieldType::MOBILE, 'label' => 'Mobile', 'is_required' => 1]]);

        $html = $this->get(route('frontend.form.show', $form->slug))->assertOk()->getContent();

        preg_match('/<input[^>]*name="mobile"[^>]*>/s', $html, $input);
        $this->assertStringContainsString('maxlength="10"', $input[0]);
        $this->assertStringContainsString('pattern="[0-9]{10}"', $input[0]);
        $this->assertStringContainsString('inputmode="numeric"', $input[0]);
    }

    /* ============================ SUBMISSIONS ============================= */

    public function test_a_form_with_no_questions_takes_no_submission(): void
    {
        $form = $this->form([]);

        $this->post(route('frontend.form.submit', $form->slug))->assertSessionHas('form_error');

        $this->assertSame(0, FormResponse::count());
    }

    /**
     * The database refusing the statement (an answer past MySQL's
     * max_allowed_packet) used to be a raw server error page. It is a message.
     */
    public function test_a_response_the_database_refuses_is_explained_not_a_server_error(): void
    {
        $form = $this->form([['field_type' => FormFieldType::LONG_TEXT, 'label' => 'Essay', 'is_required' => 0]]);

        $this->partialMock(FormSubmissionService::class, function ($mock) {
            $mock->shouldReceive('store')->andThrow(new PDOException('SQLSTATE[HY000]: General error: 2006 MySQL server has gone away'));
        });

        $this->post(route('frontend.form.submit', $form->slug), ['essay' => 'Very long'])
            ->assertRedirect()
            ->assertSessionHas('form_error', fn ($message) => str_contains($message, 'could not save your response'));
    }

    /* ========================== CONDITIONAL LOGIC ========================== */

    /**
     * A condition on a question that is no longer on the form can never be met,
     * and it hid its question from every visitor for good. It is ignored.
     */
    public function test_a_condition_on_a_missing_question_is_ignored(): void
    {
        $form = $this->form([
            ['field_type' => FormFieldType::SHORT_TEXT, 'label' => 'Name', 'is_required' => 0],
            ['field_type' => FormFieldType::SHORT_TEXT, 'label' => 'Company', 'is_required' => 1,
                'cond_field_key' => 'deleted_question', 'cond_operator' => 'equals', 'cond_value' => 'Yes'],
        ]);

        $company = $form->fields->firstWhere('label', 'Company');
        $this->assertNull($company->condition());

        $this->get(route('frontend.form.show', $form->slug))
            ->assertOk()
            ->assertDontSee('data-cond-field="deleted_question"', false);

        // So it is simply a required question.
        $this->post(route('frontend.form.submit', $form->slug), ['name' => 'A'])->assertSessionHasErrors('company');
    }

    /** A condition watching a tick-box grid used to throw "Array to string conversion". */
    public function test_a_condition_on_a_tick_grid_does_not_crash(): void
    {
        $form = $this->form([
            ['field_type' => FormFieldType::TICK_GRID, 'label' => 'Days', 'is_required' => 0,
                'rows' => $this->choices(['Mon', 'Tue']), 'columns' => $this->choices(['AM', 'PM'])],
            ['field_type' => FormFieldType::SHORT_TEXT, 'label' => 'Why mornings', 'is_required' => 1,
                'cond_field_key' => 'days', 'cond_operator' => 'equals', 'cond_value' => 'AM'],
        ]);

        $this->post(route('frontend.form.submit', $form->slug), ['days' => [0 => ['AM'], 1 => ['PM']]])
            ->assertSessionHasErrors('why_mornings');

        $this->post(route('frontend.form.submit', $form->slug), ['days' => [0 => ['PM']]])
            ->assertSessionHasNoErrors();
    }

    /* ================================ WORDING ============================== */

    /** One "is too long" served text length, number maximum and file size alike. */
    public function test_limits_are_worded_for_what_they_limit(): void
    {
        Storage::fake('local');

        $form = $this->form([
            ['field_type' => FormFieldType::NUMBER, 'label' => 'Age', 'is_required' => 0, 'min_value' => 18, 'max_value' => 60],
            ['field_type' => FormFieldType::SHORT_TEXT, 'label' => 'Code', 'is_required' => 0, 'max_length' => 4],
            ['field_type' => FormFieldType::FILE, 'label' => 'Resume', 'is_required' => 0, 'file_types' => ['pdf'], 'max_file_size_kb' => 100],
        ]);

        $errors = $this->post(route('frontend.form.submit', $form->slug), [
            'age'    => 70,
            'code'   => 'ABCDEFG',
            'resume' => UploadedFile::fake()->create('cv.pdf', 500, 'application/pdf'),
        ])->assertSessionHasErrors(['age', 'code', 'resume'])->getSession()->get('errors');

        $this->assertSame('“Age” must be 60 or less.', $errors->first('age'));
        $this->assertSame('“Code” must be 4 characters or fewer.', $errors->first('code'));
        $this->assertStringStartsWith('“Resume” is larger than', $errors->first('resume'));

        $low = $this->post(route('frontend.form.submit', $form->slug), ['age' => 12])->getSession()->get('errors');
        $this->assertSame('“Age” must be 18 or more.', $low->first('age'));
    }
}
