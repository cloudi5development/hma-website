<?php

namespace Tests\Feature\Frontend;

use App\Models\Form;
use App\Models\FormResponse;
use App\Models\User;
use App\Services\FormBuilderService;
use App\Support\FormFieldType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Linear scale, rating, and the two grids.
 *
 * These are the field types with real structure behind them — a configurable
 * range, a configurable icon count, and two lists of choices crossed against
 * each other — so each is checked for the same three things: the admin controls
 * it, the public page renders it, and a submission stores and reads back
 * correctly.
 */
class FormAdvancedFieldsTest extends TestCase
{
    use RefreshDatabase;

    private function form(array $fields, array $overrides = []): Form
    {
        return app(FormBuilderService::class)->save($overrides + [
            'name'            => 'Feedback',
            'title'           => 'Tell us how we did',
            'description'     => null,
            'slug'            => 'feedback',
            'status'          => Form::PUBLISHED,
            'submit_label'    => 'Submit',
            'success_message' => 'Thanks.',
            'allow_multiple'  => 1,
            'fields'          => $fields,
        ]);
    }

    private function submit(Form $form, array $data = [])
    {
        return $this->post(route('frontend.form.submit', $form->slug), $data);
    }

    private function answers(): array
    {
        return FormResponse::with(FormResponse::WITH_ANSWERS)->firstOrFail()->keyed();
    }

    /** The rows/columns a grid test uses. */
    private function grid(array $overrides = []): array
    {
        return $overrides + [
            'field_type' => FormFieldType::MC_GRID,
            'label'      => 'Rate each area',
            'rows'       => [
                ['label' => 'Product', 'value' => 'product'],
                ['label' => 'Service', 'value' => 'service'],
                ['label' => 'Support', 'value' => 'support'],
            ],
            'columns'    => [
                ['label' => 'Excellent', 'value' => 'excellent'],
                ['label' => 'Good',      'value' => 'good'],
                ['label' => 'Average',   'value' => 'average'],
                ['label' => 'Poor',      'value' => 'poor'],
            ],
        ];
    }

    /* ============================= LINEAR SCALE ============================ */

    public function test_a_linear_scale_uses_the_range_the_admin_set(): void
    {
        $form = $this->form([[
            'field_type'      => FormFieldType::LINEAR_SCALE,
            'label'           => 'How satisfied are you?',
            'scale_min'       => 0,
            'scale_max'       => 10,
            'scale_min_label' => 'Not at all',
            'scale_max_label' => 'Extremely',
        ]]);

        $field = $form->fields->first();

        // 1-5 is a starting point, not a scale the module imposes.
        $this->assertSame(0, $field->scaleMin());
        $this->assertSame(10, $field->scaleMax());
        $this->assertCount(11, $field->scaleSteps());

        $html = $this->get(route('frontend.form.show', $form->slug))->assertOk()->getContent();

        $this->assertStringContainsString('Not at all', $html);
        $this->assertStringContainsString('Extremely', $html);
        $this->assertStringContainsString('value="0"', $html);
        $this->assertStringContainsString('value="10"', $html);
    }

    public function test_a_scale_defaults_when_the_admin_sets_nothing(): void
    {
        $field = $this->form([[
            'field_type' => FormFieldType::LINEAR_SCALE, 'label' => 'Rate us',
        ]])->fields->first();

        $this->assertSame([1, 2, 3, 4, 5], $field->scaleSteps());
    }

    public function test_a_scale_answer_outside_its_range_is_refused(): void
    {
        $form = $this->form([[
            'field_type' => FormFieldType::LINEAR_SCALE, 'label' => 'Rate us',
            'scale_min' => 1, 'scale_max' => 5,
        ]]);

        $this->submit($form, ['rate_us' => 9])->assertSessionHasErrors('rate_us');
        $this->submit($form, ['rate_us' => 4])->assertSessionHasNoErrors();

        $this->assertSame('4', $this->answers()['rate_us']->value);
    }

    public function test_a_scale_that_ends_below_where_it_starts_is_refused_at_the_builder(): void
    {
        $admin = User::where('is_super_admin', true)->firstOrFail();

        $this->withSession([
            'admin_logged_in' => true, 'admin_id' => $admin->id,
            'admin_name' => $admin->name, 'admin_email' => $admin->email,
        ])->post(route('backend.forms.store'), [
            'name' => 'Bad scale', 'title' => 'Bad scale', 'status' => Form::DRAFT,
            'fields' => ['f0' => [
                'field_type' => FormFieldType::LINEAR_SCALE, 'label' => 'Rate us',
                'scale_min' => 5, 'scale_max' => 2,
            ]],
        ])->assertSessionHasErrors('fields.f0.scale_max');
    }

    /* ================================ RATING =============================== */

    public function test_a_rating_uses_the_count_and_icon_the_admin_chose(): void
    {
        $form = $this->form([[
            'field_type'   => FormFieldType::RATING,
            'label'        => 'Rate our service',
            'rating_count' => 7,
            'rating_icon'  => 'heart',
        ]]);

        $field = $form->fields->first();

        $this->assertSame(7, $field->ratingCount());
        $this->assertSame('heart', $field->ratingIcon());

        $html = $this->get(route('frontend.form.show', $form->slug))->assertOk()->getContent();

        $this->assertStringContainsString('hmf-rating--heart', $html);
        $this->assertStringContainsString('7 of 7', $html);
    }

    public function test_a_rating_above_its_count_is_refused(): void
    {
        $form = $this->form([[
            'field_type' => FormFieldType::RATING, 'label' => 'Rate us', 'rating_count' => 5,
        ]]);

        $this->submit($form, ['rate_us' => 6])->assertSessionHasErrors('rate_us');
        $this->submit($form, ['rate_us' => 5])->assertSessionHasNoErrors();

        $this->assertSame('5', $this->answers()['rate_us']->value);
    }

    /* ========================= MULTIPLE-CHOICE GRID ======================== */

    public function test_a_grid_renders_its_rows_and_columns(): void
    {
        $form = $this->form([$this->grid()]);

        $html = $this->get(route('frontend.form.show', $form->slug))->assertOk()->getContent();

        foreach (['Product', 'Service', 'Support', 'Excellent', 'Good', 'Average', 'Poor'] as $heading) {
            $this->assertStringContainsString($heading, $html);
        }

        // One radio per cell, named by the row's POSITION — a row label can
        // contain anything, and a validation key cannot.
        $this->assertStringContainsString('name="rate_each_area[0]"', $html);
        $this->assertStringContainsString('name="rate_each_area[2]"', $html);
        $this->assertStringContainsString('type="radio"', $html);
    }

    public function test_a_grid_answer_is_stored_against_its_rows_and_reads_back_as_labels(): void
    {
        $form = $this->form([$this->grid()]);

        $this->submit($form, ['rate_each_area' => [0 => 'excellent', 1 => 'good', 2 => 'average']])
            ->assertSessionHasNoErrors();

        $value = $this->answers()['rate_each_area'];

        // Stored by row VALUE, which is stable when the wording changes...
        $this->assertSame(
            ['product' => 'excellent', 'service' => 'good', 'support' => 'average'],
            json_decode($value->value, true),
        );

        // ...and read back as the labels the admin wrote.
        $this->assertSame(
            ['Product' => ['Excellent'], 'Service' => ['Good'], 'Support' => ['Average']],
            $value->gridAnswers(),
        );

        $this->assertSame('Product: Excellent · Service: Good · Support: Average', $value->display);
    }

    public function test_a_required_grid_demands_every_row(): void
    {
        $form = $this->form([$this->grid(['is_required' => 1])]);

        // Two of three rows answered is not an answered grid.
        $this->submit($form, ['rate_each_area' => [0 => 'excellent', 1 => 'good']])
            ->assertSessionHasErrors('rate_each_area.2');

        $this->submit($form, ['rate_each_area' => [0 => 'excellent', 1 => 'good', 2 => 'poor']])
            ->assertSessionHasNoErrors();
    }

    public function test_a_grid_answer_outside_its_columns_is_refused(): void
    {
        $form = $this->form([$this->grid()]);

        $this->submit($form, ['rate_each_area' => [0 => 'magnificent']])
            ->assertSessionHasErrors('rate_each_area.0');

        $this->assertSame(0, FormResponse::count());
    }

    /* ============================= TICK BOX GRID =========================== */

    public function test_a_tick_box_grid_takes_several_columns_per_row(): void
    {
        $form = $this->form([$this->grid([
            'field_type' => FormFieldType::TICK_GRID,
            'label'      => 'How may we contact you',
            'columns'    => [
                ['label' => 'Email',    'value' => 'email'],
                ['label' => 'WhatsApp', 'value' => 'whatsapp'],
                ['label' => 'Phone',    'value' => 'phone'],
            ],
        ])]);

        $html = $this->get(route('frontend.form.show', $form->slug))->assertOk()->getContent();

        // The [] suffix is what lets one row hold several answers.
        $this->assertStringContainsString('name="how_may_we_contact_you[0][]"', $html);
        $this->assertStringContainsString('type="checkbox"', $html);

        $this->submit($form, ['how_may_we_contact_you' => [
            0 => ['email', 'whatsapp'],
            1 => ['phone'],
        ]])->assertSessionHasNoErrors();

        $value = $this->answers()['how_may_we_contact_you'];

        $this->assertSame(
            ['product' => ['email', 'whatsapp'], 'service' => ['phone']],
            json_decode($value->value, true),
        );

        $this->assertSame(
            ['Product' => ['Email', 'WhatsApp'], 'Service' => ['Phone']],
            $value->gridAnswers(),
        );
    }

    /* ============================== DATE / TIME ============================ */

    public function test_a_date_field_enforces_the_window_the_admin_set(): void
    {
        $form = $this->form([[
            'field_type' => FormFieldType::DATE, 'label' => 'Preferred start',
            'min_date' => '2026-09-01', 'max_date' => '2026-09-30',
        ]]);

        $this->get(route('frontend.form.show', $form->slug))
            ->assertOk()
            ->assertSee('min="2026-09-01"', false)
            ->assertSee('max="2026-09-30"', false);

        $this->submit($form, ['preferred_start' => '2026-08-15'])->assertSessionHasErrors('preferred_start');
        $this->submit($form, ['preferred_start' => '2026-10-15'])->assertSessionHasErrors('preferred_start');
        $this->submit($form, ['preferred_start' => '2026-09-15'])->assertSessionHasNoErrors();
    }

    public function test_a_time_field_enforces_its_window(): void
    {
        $form = $this->form([[
            'field_type' => FormFieldType::TIME, 'label' => 'Call me at',
            'min_time' => '09:00', 'max_time' => '17:30',
        ]]);

        $this->submit($form, ['call_me_at' => '08:30'])->assertSessionHasErrors('call_me_at');
        $this->submit($form, ['call_me_at' => '18:00'])->assertSessionHasErrors('call_me_at');
        $this->submit($form, ['call_me_at' => '14:00'])->assertSessionHasNoErrors();

        $this->assertSame('14:00', $this->answers()['call_me_at']->value);
    }

    /* ================================ BUILDER ============================== */

    public function test_a_grid_needs_both_rows_and_columns(): void
    {
        $admin = User::where('is_super_admin', true)->firstOrFail();

        $this->withSession([
            'admin_logged_in' => true, 'admin_id' => $admin->id,
            'admin_name' => $admin->name, 'admin_email' => $admin->email,
        ])->post(route('backend.forms.store'), [
            'name' => 'Half a grid', 'title' => 'Half a grid', 'status' => Form::DRAFT,
            'fields' => ['f0' => [
                'field_type' => FormFieldType::MC_GRID, 'label' => 'Rate each area',
                'rows' => [['label' => 'Product']],
            ]],
        ])->assertSessionHasErrors('fields.f0.columns');

        $this->assertSame(0, Form::count());
    }

    /** Switching a grid to a dropdown must not leave its rows behind. */
    public function test_changing_away_from_a_grid_clears_its_rows_and_columns(): void
    {
        $form  = $this->form([$this->grid()]);
        $field = $form->fields->first();

        $this->assertCount(3, $field->rows);
        $this->assertCount(4, $field->columns);

        app(FormBuilderService::class)->save([
            'name' => 'Feedback', 'title' => 'Tell us how we did', 'slug' => 'feedback',
            'status' => Form::PUBLISHED, 'allow_multiple' => 1,
            'fields' => [[
                'id' => $field->id, 'field_type' => FormFieldType::DROPDOWN, 'label' => 'Rate each area',
                'options' => [['label' => 'Good', 'value' => 'good']],
            ]],
        ], $form);

        $field->refresh()->load(['options', 'rows', 'columns']);

        $this->assertCount(1, $field->options);
        $this->assertCount(0, $field->rows);
        $this->assertCount(0, $field->columns);
    }

    public function test_duplicating_a_form_copies_a_grid_intact(): void
    {
        $form = $this->form([$this->grid()]);

        $copy = app(FormBuilderService::class)->duplicate($form);

        $field = $copy->fields->first()->load(['rows', 'columns']);

        $this->assertSame(FormFieldType::MC_GRID, $field->field_type);
        $this->assertSame(['product', 'service', 'support'], $field->rows->pluck('value')->all());
        $this->assertSame(['excellent', 'good', 'average', 'poor'], $field->columns->pluck('value')->all());
    }

    /**
     * The type dropdown is built from the registry, so every type it offers is
     * one the module can actually render and validate.
     */
    public function test_the_builder_offers_every_registered_type(): void
    {
        $admin = User::where('is_super_admin', true)->firstOrFail();

        $html = $this->withSession([
            'admin_logged_in' => true, 'admin_id' => $admin->id,
            'admin_name' => $admin->name, 'admin_email' => $admin->email,
        ])->get(route('backend.forms.create'))->assertOk()->getContent();

        // Labels are compared escaped — "Date & time" reaches the page as
        // "Date &amp; time", which is Blade doing its job.
        foreach (FormFieldType::TYPES as $key => $spec) {
            $this->assertStringContainsString('data-type-value="' . $key . '"', $html, "{$key} is not offered.");
            $this->assertStringContainsString(e($spec['label']), $html, "{$key}'s label is missing.");
        }

        // ...under the headings the specification asks for.
        foreach (FormFieldType::GROUP_ORDER as $group) {
            $this->assertStringContainsString('>' . e($group) . '</p>', $html);
        }
    }
}
