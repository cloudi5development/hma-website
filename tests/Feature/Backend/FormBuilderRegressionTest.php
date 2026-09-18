<?php

namespace Tests\Feature\Backend;

use App\Models\Form;
use App\Models\FormField;
use App\Models\FormResponse;
use App\Models\User;
use App\Services\FormBuilderService;
use App\Support\FormBuilderTree;
use App\Support\FormFieldType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regressions found by driving the real builder screen in a browser.
 *
 * Each of these lost or scrambled an admin's work through an ordinary click, and
 * none showed up in the service-level tests, because the builder posts one JSON
 * payload and those tests posted plain arrays.
 */
class FormBuilderRegressionTest extends TestCase
{
    use RefreshDatabase;

    private function signedIn(): self
    {
        $admin = User::where('is_super_admin', true)->firstOrFail();

        return $this->withSession([
            'admin_logged_in' => true, 'admin_id' => $admin->id,
            'admin_name' => $admin->name, 'admin_email' => $admin->email,
        ]);
    }

    private function form(array $fields, array $overrides = []): Form
    {
        return app(FormBuilderService::class)->save($overrides + [
            'name'   => 'Regression Form',
            'status' => Form::PUBLISHED,
            'fields' => $fields,
        ]);
    }

    /**
     * The builder_payload the screen sends: [name, value] pairs, pages and
     * sections first, then every question — the way pack() writes them.
     */
    private function payload(array $pages, array $sections, array $fields): string
    {
        $pairs = [];

        $flatten = function (string $prefix, array $values) use (&$pairs, &$flatten) {
            foreach ($values as $key => $value) {
                is_array($value)
                    ? $flatten("{$prefix}[{$key}]", $value)
                    : $pairs[] = ["{$prefix}[{$key}]", (string) $value];
            }
        };

        $flatten('pages', $pages);
        $flatten('sections', $sections);
        $flatten('fields', $fields);

        return json_encode($pairs);
    }

    /** One saved question as the builder posts it back: id, text, type, place. */
    private function row(FormField $field, array $overrides = []): array
    {
        return $overrides + [
            'id' => $field->id, 'label' => $field->label, 'field_type' => $field->field_type,
            'is_required' => $field->is_required ? 1 : 0, 'page_ref' => 'p0', 'section_ref' => 's0',
        ];
    }

    private function save(Form $form, array $fields, array $extra = [])
    {
        return $this->signedIn()->from(route('backend.forms.edit', $form))->put(route('backend.forms.update', $form), $extra + [
            'name' => $form->name, 'status' => $form->status, 'structure_type' => $form->structure_type,
            'builder_payload' => $this->payload(['p0' => ['id' => '', 'title' => '']], ['s0' => ['id' => '', 'page_ref' => 'p0', 'title' => '']], $fields),
        ]);
    }

    /* ============================ STORAGE KEYS ============================ */

    /**
     * Renaming a question moved it to a new key, and every answer filed under
     * the old one vanished from the table and the exports.
     */
    public function test_a_renamed_question_keeps_its_key_and_its_answers(): void
    {
        $form  = $this->form([['field_type' => FormFieldType::SHORT_TEXT, 'label' => 'Course', 'is_required' => 0]]);
        $field = $form->fields->first();

        $this->post(route('frontend.form.submit', $form->slug), ['course' => 'Python Full Stack']);

        $this->save($form, ['f1' => $this->row($field, ['label' => 'Preferred Course'])])
            ->assertSessionHasNoErrors();

        $this->assertSame('course', $field->fresh()->field_key);

        $this->signedIn()->get(route('backend.forms.responses.index', $form))->assertSee('Python Full Stack');
        $this->assertStringContainsString(
            'Python Full Stack',
            $this->signedIn()->get(route('backend.forms.responses.export', $form))->streamedContent(),
        );
    }

    /** Two questions called "Name" swapped keys — and answer histories — when reordered. */
    public function test_reordering_two_questions_with_the_same_text_does_not_swap_their_keys(): void
    {
        $form = $this->form([
            ['field_type' => FormFieldType::SHORT_TEXT, 'label' => 'Name', 'is_required' => 0],
            ['field_type' => FormFieldType::SHORT_TEXT, 'label' => 'Name', 'is_required' => 0],
        ]);
        [$first, $second] = [$form->fields[0], $form->fields[1]];

        $this->save($form, ['f2' => $this->row($second), 'f1' => $this->row($first)])->assertSessionHasNoErrors();

        $this->assertSame('name', $first->fresh()->field_key);
        $this->assertSame('name_2', $second->fresh()->field_key);

        // A NEW question with the same text further up takes the next free key.
        $this->save($form, [
            'n1' => ['id' => '', 'label' => 'Name', 'field_type' => FormFieldType::SHORT_TEXT, 'page_ref' => 'p0', 'section_ref' => 's0'],
            'f1' => $this->row($first), 'f2' => $this->row($second),
        ])->assertSessionHasNoErrors();

        $this->assertSame(['name_3', 'name', 'name_2'], $form->fresh()->fields->pluck('field_key')->all());
    }

    /* ============================ FAILED SAVES ============================ */

    /**
     * One question with an error sent the admin back to a builder redrawn from
     * the database — every unsaved question, page and section gone.
     */
    public function test_a_failed_save_gives_back_every_unsaved_question(): void
    {
        $form = $this->form([['field_type' => FormFieldType::SHORT_TEXT, 'label' => 'Existing', 'is_required' => 0]]);

        $this->save($form, [
            'f1' => $this->row($form->fields->first()),
            'n1' => ['id' => '', 'label' => 'Unsaved Dropdown', 'field_type' => FormFieldType::DROPDOWN, 'page_ref' => 'p0', 'section_ref' => 's0'],
            'n2' => ['id' => '', 'label' => 'Unsaved Text', 'field_type' => FormFieldType::SHORT_TEXT, 'page_ref' => 'p0', 'section_ref' => 's0'],
        ])->assertRedirect(route('backend.forms.edit', $form))->assertSessionHasErrors('fields.n1.options');

        // Nothing was written...
        $this->assertSame(['Existing'], $form->fresh()->fields->pluck('label')->all());

        // ...and the builder draws what was posted, error beside the question.
        $html = $this->signedIn()->get(route('backend.forms.edit', $form))->assertOk()->getContent();

        $this->assertStringContainsString('value="Unsaved Dropdown"', $html);
        $this->assertStringContainsString('value="Unsaved Text"', $html);
        $this->assertStringContainsString('value="Existing"', $html);
        $this->assertStringContainsString('add at least one option', $html);
    }

    /** The old input flashed after a failed save is the questions, not the JSON string. */
    public function test_a_failed_save_does_not_flash_the_raw_payload(): void
    {
        $form = $this->form([]);

        $this->save($form, ['n1' => ['id' => '', 'label' => 'Pick', 'field_type' => FormFieldType::RADIO, 'page_ref' => 'p0', 'section_ref' => 's0']])
            ->assertSessionHasErrors();

        $old = session('_old_input');

        $this->assertArrayNotHasKey('builder_payload', $old);
        $this->assertSame('Pick', $old['fields']['n1']['label']);
        $this->assertArrayHasKey('s0', $old['sections']);
    }

    /* =========================== SAVES THAT LOSE ========================== */

    /**
     * A second press of Save, while the first was on its way, sent an empty
     * list — and every question, page and section on the form was deleted.
     */
    public function test_an_empty_payload_changes_nothing(): void
    {
        $form = $this->form([
            ['field_type' => FormFieldType::SHORT_TEXT, 'label' => 'One', 'is_required' => 0],
            ['field_type' => FormFieldType::SHORT_TEXT, 'label' => 'Two', 'is_required' => 0],
        ]);

        $this->signedIn()->put(route('backend.forms.update', $form), [
            'name' => $form->name, 'status' => $form->status, 'builder_payload' => '[]',
        ])->assertSessionHasErrors('fields');

        $this->assertSame(['One', 'Two'], $form->fresh()->fields->pluck('label')->all());
    }

    /** Clearing a saved question's text deleted it on Save, with no confirmation. */
    public function test_a_saved_question_with_its_text_cleared_is_refused_not_deleted(): void
    {
        $form  = $this->form([['field_type' => FormFieldType::SHORT_TEXT, 'label' => 'Keep Me', 'is_required' => 0]]);
        $field = $form->fields->first();

        $this->save($form, ['f1' => $this->row($field, ['label' => '   '])])
            ->assertSessionHasErrors('fields.f1.label');

        $this->assertNotNull($field->fresh());
        $this->assertNull(FormField::onlyTrashed()->find($field->id));
    }

    /** A setting for another type — still in the page, just hidden — blocked the save. */
    public function test_a_setting_for_a_different_type_does_not_block_the_save(): void
    {
        $form  = $this->form([['field_type' => FormFieldType::RATING, 'label' => 'Rate', 'is_required' => 0]]);
        $field = $form->fields->first();

        $this->save($form, ['f1' => $this->row($field, ['field_type' => FormFieldType::SHORT_TEXT, 'rating_count' => '1', 'scale_min' => 'x'])])
            ->assertSessionHasNoErrors();

        $this->assertSame(FormFieldType::SHORT_TEXT, $field->fresh()->field_type);

        // On a rating it still applies.
        $this->save($form, ['f1' => $this->row($field, ['field_type' => FormFieldType::RATING, 'rating_count' => '1'])])
            ->assertSessionHasErrors('fields.f1.rating_count');
    }

    /** Create published a form that asked nothing when its only question was blank. */
    public function test_a_form_cannot_be_created_without_a_question(): void
    {
        $this->signedIn()->post(route('backend.forms.store'), [
            'name' => 'Nothing To Ask', 'status' => Form::PUBLISHED,
            'builder_payload' => $this->payload(['p0' => ['id' => '']], ['s0' => ['id' => '', 'page_ref' => 'p0']],
                ['n1' => ['id' => '', 'label' => '', 'field_type' => FormFieldType::SHORT_TEXT, 'page_ref' => 'p0', 'section_ref' => 's0']]),
        ])->assertSessionHasErrors('fields');

        $this->assertFalse(Form::where('name', 'Nothing To Ask')->exists());
    }

    /* ======================== WHAT THE SCREEN CARRIES ======================= */

    /**
     * Conditions, answer limits and a hidden question's value are not edited on
     * this screen, and every Save used to erase them. The row carries them.
     */
    public function test_settings_the_screen_does_not_edit_survive_a_save_through_it(): void
    {
        $form = $this->form([
            ['field_type' => FormFieldType::RADIO, 'label' => 'Employed', 'is_required' => 0, 'options' => [['label' => 'Yes'], ['label' => 'No']]],
            ['field_type' => FormFieldType::SHORT_TEXT, 'label' => 'Company', 'is_required' => 0,
                'cond_field_key' => 'employed', 'cond_operator' => 'equals', 'cond_value' => 'Yes', 'max_length' => 80],
            ['field_type' => FormFieldType::NUMBER, 'label' => 'Age', 'is_required' => 0, 'min_value' => 18, 'max_value' => 60],
            ['field_type' => FormFieldType::DATE, 'label' => 'Start', 'is_required' => 0, 'min_date' => '2026-01-01', 'max_date' => '2026-12-31'],
            ['field_type' => FormFieldType::HIDDEN, 'label' => 'Source', 'default_value' => 'campaign-a'],
        ]);

        // Read the edit page as the browser would, and post every fields[…]
        // input straight back — which is what pack() does.
        $html = $this->signedIn()->get(route('backend.forms.edit', $form))->assertOk()->getContent();

        preg_match_all('/<(?:input|select|textarea)\b[^>]*\bname="((?:fields|pages|sections)\[[^"]*)"[^>]*>/', $html, $tags, PREG_SET_ORDER);

        $pairs = [];
        foreach ($tags as [$tag, $name]) {
            if (str_contains($name, '__')) {
                continue;   // a <template>'s placeholder row
            }
            if (preg_match('/type="(checkbox|radio)"/', $tag) && ! str_contains($tag, 'checked')) {
                continue;
            }
            preg_match('/\bvalue="([^"]*)"/', $tag, $v);
            $pairs[] = [html_entity_decode($name), html_entity_decode($v[1] ?? '')];
        }

        $this->signedIn()->put(route('backend.forms.update', $form), [
            'name' => $form->name, 'status' => $form->status, 'structure_type' => $form->structure_type,
            'builder_payload' => json_encode($pairs),
        ])->assertSessionHasNoErrors();

        $fields = $form->fresh()->fields->keyBy('label');

        $this->assertSame(['field_key' => 'employed', 'operator' => 'equals', 'value' => 'Yes'], $fields['Company']->condition());
        $this->assertEquals(80, $fields['Company']->rule('max_length'));
        $this->assertEquals(18, $fields['Age']->rule('min_value'));
        $this->assertEquals(60, $fields['Age']->rule('max_value'));
        $this->assertSame('2026-12-31', $fields['Start']->rule('max_date'));
        $this->assertSame('campaign-a', $fields['Source']->default_value);
    }

    /* ======================== SWITCHING STRUCTURES ======================== */

    /**
     * Every page with no sections of its own was given the same new-section
     * ref, so switching Multi-Page → Multi-Page + Sections put every question
     * on the last page.
     */
    public function test_each_page_without_sections_gets_its_own_new_section(): void
    {
        $form = app(FormBuilderService::class)->save([
            'name' => 'Paged', 'status' => Form::PUBLISHED, 'structure_type' => Form::PAGES,
            'pages'  => ['a' => ['title' => 'One'], 'b' => ['title' => 'Two']],
            'fields' => [
                ['field_type' => FormFieldType::SHORT_TEXT, 'label' => 'First', 'page_ref' => 'a'],
                ['field_type' => FormFieldType::SHORT_TEXT, 'label' => 'Second', 'page_ref' => 'b'],
            ],
        ]);

        $tree = FormBuilderTree::for($form->fresh(['pages', 'sections']));
        $refs = array_map(fn ($page) => $page['sections'][0]['ref'], $tree);

        $this->assertCount(2, array_unique($refs));

        // The post the screen then makes for Multi-Page + Sections.
        $pages = $sections = $fields = [];
        foreach ($tree as $page) {
            $pages[$page['ref']] = ['id' => $page['id'], 'title' => $page['title']];
            $section = $page['sections'][0];
            $sections[$section['ref']] = ['id' => '', 'page_ref' => $page['ref'], 'title' => ''];
            foreach ($section['fields'] as $key => $row) {
                $fields[$key] = ['id' => $row['id'], 'label' => $row['label'], 'field_type' => $row['field_type'],
                    'page_ref' => $page['ref'], 'section_ref' => $section['ref']];
            }
        }

        $this->signedIn()->put(route('backend.forms.update', $form), [
            'name' => 'Paged', 'status' => Form::PUBLISHED, 'structure_type' => Form::PAGES_SECTIONS,
            'builder_payload' => $this->payload($pages, $sections, $fields),
        ])->assertSessionHasNoErrors();

        $form = $form->fresh(['pages', 'fields']);
        $onPage = $form->fields->mapWithKeys(fn ($f) => [$f->label => $form->pages->firstWhere('id', $f->form_page_id)?->title])->all();

        $this->assertSame(['First' => 'One', 'Second' => 'Two'], $onPage);
    }

    /* ============================ THE FORM PAGE ============================ */

    /** The forms list sends people here to publish and duplicate; the buttons were missing. */
    public function test_the_form_page_offers_publish_and_duplicate(): void
    {
        $form = $this->form([['field_type' => FormFieldType::SHORT_TEXT, 'label' => 'Name', 'is_required' => 0]], ['status' => Form::DRAFT]);

        $this->signedIn()->get(route('backend.forms.show', $form))
            ->assertOk()
            ->assertSee(route('backend.forms.toggle', $form), false)
            ->assertSee(route('backend.forms.duplicate', $form), false)
            ->assertSee('Publish Form');

        $this->signedIn()->post(route('backend.forms.toggle', $form));
        $this->assertTrue($form->fresh()->isPublished());

        $this->signedIn()->get(route('backend.forms.show', $form))->assertSee('Disable Form');
    }

    /** The edit screen shows the form's real link, which renaming does not change. */
    public function test_the_edit_screen_shows_the_saved_link(): void
    {
        $form = $this->form([['field_type' => FormFieldType::SHORT_TEXT, 'label' => 'Name', 'is_required' => 0]]);

        $this->signedIn()->get(route('backend.forms.edit', $form))
            ->assertOk()
            ->assertSee('/forms/' . $form->slug, false);
    }
}
