<?php

namespace App\Services;

use App\Models\Form;
use App\Models\FormField;
use App\Models\FormFieldOption;
use App\Models\FormPage;
use App\Models\FormSection;
use App\Support\FormFieldType;
use App\Services\FormSubmissionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Turns what the builder posted into a form, its fields and their options.
 *
 * The one thing this class exists to get right is FIELD IDENTITY. Every other
 * repeater in this panel (event highlights, speakers, course FAQs) saves by
 * deleting every row and writing the submitted ones back, which is fine for
 * content nothing points at. Form fields are pointed at — by every response
 * already submitted — so the same approach would orphan a year of answers the
 * first time an admin fixed a typo.
 *
 * So fields are matched by id and updated in place. A row the builder posts
 * without an id is new. A field the builder does not post at all has been
 * removed, and is soft-deleted when it has answers (keeping them readable) or
 * deleted outright when it has none.
 */
class FormBuilderService
{
    /*
     * There is no ceiling on anything here — questions per form, choices per
     * question, pages, sections. There were (60, then 200 questions; 60 choices;
     * 20 pages; 40 sections) and they were removed on request. The one limit a
     * form really had was PHP's max_input_vars, which silently dropped the tail
     * of a large form; the builder now posts its rows as a single JSON payload
     * (FormBuilderRequest::unpackPayload), so that one is gone too.
     */

    /**
     * Keys a field may not take, because the submitted request already uses
     * them. A question labelled "Token" would otherwise generate `_token` and
     * quietly overwrite the CSRF field on the public page.
     */
    private const RESERVED_KEYS = ['_token', '_method', 'embed', 'slug', FormSubmissionService::HONEYPOT];

    /**
     * Create or update a form and everything under it, in one transaction.
     *
     * @param  array  $data    the validated payload
     * @param  ?Form  $form    the form being edited, or null to create one
     */
    public function save(array $data, ?Form $form = null): Form
    {
        return DB::transaction(function () use ($data, $form) {
            $form = $this->saveForm($data, $form);

            // Containers before questions, always. A question is placed by the
            // id of the page or section it lands in, and a page created in this
            // same save has no id until it is written — so the order here is not
            // stylistic, it is what makes placing a question into a brand new
            // page possible at all.
            //
            // It also means a page the admin deleted is gone by the time the
            // questions are placed, so nothing can be filed into it.
            $pages    = $this->syncPages($form, $data['pages'] ?? []);
            $sections = $this->syncSections($form, $data['sections'] ?? [], $pages);

            $this->syncFields($form, $data['fields'] ?? [], $pages, $sections);

            return $form->fresh(['fields.options', 'pages', 'sections']);
        });
    }

    /* ================================ FORM ================================= */

    private function saveForm(array $data, ?Form $form): Form
    {
        $name = trim($data['name']);

        $attributes = [
            'name'        => $name,
            // One thing to type. The form's name is what visitors read at the
            // top of the page and what its link is built from — an admin should
            // not have to fill in three boxes that all say the same thing.
            'title'       => trim((string) ($data['title'] ?? '')) ?: $name,
            'description' => trim((string) ($data['description'] ?? '')) ?: null,
            'status'      => $data['status'] ?? Form::DRAFT,
        ];

        // Anything unrecognised becomes a plain form — the shape that works with
        // no pages and no sections, so a bad value degrades to something usable
        // rather than to a form that renders nothing.
        if (array_key_exists('structure_type', $data)) {
            $attributes['structure_type'] = array_key_exists((string) $data['structure_type'], Form::STRUCTURES)
                ? $data['structure_type']
                : Form::PLAIN;
        }

        // Same rule: absent leaves the form alone, unrecognised is standard.
        if (array_key_exists('form_type', $data)) {
            $attributes['form_type'] = array_key_exists((string) $data['form_type'], Form::FORM_TYPES)
                ? $data['form_type']
                : Form::STANDARD;
        }

        // Settings are written only when settings were actually sent. The
        // builder never sends any, and a builder save must leave them exactly
        // as they were — it used to overwrite them, because the request slipped
        // a notify_enabled=false into every save and this took that as "the
        // settings were posted". See FormBuilderRequest::prepareForValidation.
        if (array_intersect_key($data, Form::SETTING_DEFAULTS) !== []) {
            $attributes['settings'] = $this->settings($data);
        }

        if ($form) {
            // The slug is deliberately NOT re-derived from the name. The link
            // has been copied, shared and possibly printed by now, and fixing a
            // typo in a form's name is not a reason to break every copy of it.
            // An explicitly posted slug still wins, for the case where the admin
            // really does want to move it.
            $slug = Str::slug((string) ($data['slug'] ?? ''));

            if ($slug !== '' && $slug !== $form->slug) {
                $attributes['slug'] = Form::uniqueSlug($slug, $form->id);
            }

            $form->update($attributes);

            return $form;
        }

        $attributes['slug'] = Form::uniqueSlug(Str::slug((string) ($data['slug'] ?? '')) ?: $name);

        return Form::create($attributes);
    }

    /**
     * Write only the form's settings, leaving its questions alone.
     *
     * Its own entry point on purpose. Running a settings save through save()
     * would hand syncFields an empty list of questions, and it would dutifully
     * remove every one of them — the settings screen would silently empty the
     * form it was configuring.
     */
    public function saveSettings(Form $form, array $data): Form
    {
        $form->update(['settings' => $this->settings($data)]);

        return $form;
    }

    /** The settings JSON, taking only the keys the module documents. */
    private function settings(array $data): array
    {
        $settings = [];

        foreach (array_keys(Form::SETTING_DEFAULTS) as $key) {
            $settings[$key] = $data[$key] ?? null;
        }

        $settings['notify_enabled'] = (bool) ($data['notify_enabled'] ?? false);

        return $settings;
    }

    /* ========================= PAGES AND SECTIONS ==========================
       The containers a question can sit in. Both are reconciled the way fields
       are — matched by id and updated in place — rather than deleted and
       rewritten, because a question points at them: recreating a page would
       hand every question on it a stale id, and the questions would come loose
       from the very page the admin had just renamed.

       Both return a map of the key the builder posted under → the saved model,
       which is how a question lands in a page created in this same save. */

    /**
     * @return array<string, FormPage>
     */
    private function syncPages(Form $form, array $rows): array
    {
        // The structure is authoritative. A form the admin has switched back to
        // plain keeps no pages, whatever the browser posted — otherwise pages
        // linger invisibly and reappear on the next switch.
        if (! $form->hasPages()) {
            $this->wipePages($form);

            return [];
        }

        $existing = $form->pages()->get()->keyBy('id');
        $map      = [];
        $seen     = [];
        $order    = 0;

        foreach ($rows as $ref => $row) {
            // Only a page this form owns may be updated by id; an id from
            // anywhere else creates a new page rather than hijacking one.
            $page = isset($row['id']) ? $existing->get((int) $row['id']) : null;

            $attributes = [
                // A page needs no title. "Page 2" is a fine answer when the
                // admin only wanted to break a long form in half, and
                // FormPage::heading supplies it at read time.
                'title'       => trim((string) ($row['title'] ?? '')) ?: null,
                'description' => trim((string) ($row['description'] ?? '')) ?: null,
                'sort_order'  => $order++,
            ];

            $page ? $page->update($attributes) : $page = $form->pages()->create($attributes);

            $seen[]            = $page->id;
            $map[(string) $ref] = $page;
        }

        $this->wipePages($form, $seen);

        return $map;
    }

    /**
     * @param  array<string, FormPage>  $pages
     * @return array<string, FormSection>
     */
    private function syncSections(Form $form, array $rows, array $pages): array
    {
        if (! $form->hasSections()) {
            FormSection::where('form_id', $form->id)->delete();

            return [];
        }

        $existing = $form->sections()->get()->keyBy('id');
        $map      = [];
        $seen     = [];
        $order    = 0;

        foreach ($rows as $ref => $row) {
            // On a paged form a section belongs to a page; on a single-page one
            // it hangs off the form itself. A section whose page did not survive
            // this save belongs nowhere, and is dropped with it.
            $page = $form->hasPages() ? ($pages[(string) ($row['page_ref'] ?? '')] ?? null) : null;

            if ($form->hasPages() && ! $page) {
                continue;
            }

            $section = isset($row['id']) ? $existing->get((int) $row['id']) : null;

            $attributes = [
                'form_page_id' => $page?->id,
                'title'        => trim((string) ($row['title'] ?? '')) ?: null,
                'description'  => trim((string) ($row['description'] ?? '')) ?: null,
                'sort_order'   => $order++,
            ];

            $section ? $section->update($attributes) : $section = $form->sections()->create($attributes);

            $seen[]             = $section->id;
            $map[(string) $ref] = $section;
        }

        FormSection::where('form_id', $form->id)
            ->whereNotIn('id', $seen ?: [0])
            ->delete();

        return $map;
    }

    /**
     * Delete the form's pages, or everything but the ones just saved.
     *
     * Sections on a deleted page go with it, and questions on it are NOT
     * deleted — their page id is nulled, and the ones the builder still lists
     * are re-placed moments later. Questions are what responses point at, so
     * removing one is a decision only syncFields is allowed to make.
     *
     * Written as a plain query rather than through $form->pages(), whose ORDER
     * BY would ride along into the DELETE — which SQLite refuses outright.
     */
    private function wipePages(Form $form, array $keep = []): void
    {
        FormPage::where('form_id', $form->id)
            ->when($keep, fn ($q) => $q->whereNotIn('id', $keep))
            ->delete();
    }

    /* =============================== FIELDS ================================ */

    /**
     * Reconcile the posted rows against the fields already on the form.
     *
     * Display order is the order the rows arrived in — a form posts its inputs
     * in document order, so the builder's drag handle is the whole of the
     * reordering story and no order field is submitted. That holds across pages
     * and sections too: the rows are nested inside them in the DOM, so one flat
     * pass over the posted list is already in reading order.
     *
     * WHERE a question sits is not read from the DOM, though — it is posted
     * explicitly as page_ref / section_ref by each row. Dragging a question into
     * another section would otherwise mean rewriting every input name on it.
     */
    private function syncFields(Form $form, array $rows, array $pages = [], array $sections = []): void
    {
        $existing = $form->fields()->get()->keyBy('id');
        $seen     = [];
        $keys     = [];
        $order    = 0;

        // A saved question KEEPS its storage key, whatever its text becomes.
        // The builder never posts a key, and deriving it afresh from the label
        // on every save meant renaming "Course" to "Preferred Course" moved it
        // to a new key — and every answer already filed under the old one fell
        // out of the responses table and the exports, and any condition that
        // watched it stopped working. Two questions both called "Name" swapped
        // keys (and so swapped their answer history) when reordered.
        //
        // Reserved up front, so a NEW question given the same label further up
        // the list counts on from it ("name_2") instead of taking it.
        $kept = [];

        foreach ($rows as $row) {
            $field = isset($row['id']) ? $existing->get((int) $row['id']) : null;

            if ($field && trim((string) ($row['label'] ?? '')) !== '' && blank($row['field_key'] ?? null)) {
                $kept[$field->id] = $field->field_key;
            }
        }

        $keys = array_values($kept);

        foreach ($rows as $row) {
            $label = trim((string) ($row['label'] ?? ''));

            // A row with no label is one the admin added and never filled in.
            // Dropping it silently beats refusing to save the whole form.
            if ($label === '') {
                continue;
            }

            $type = FormFieldType::exists($row['field_type'] ?? null)
                ? $row['field_type']
                : FormFieldType::SHORT_TEXT;

            // Only a field this form actually owns may be updated by id; a posted
            // id from anywhere else creates a new field instead of hijacking one.
            $field = isset($row['id']) ? $existing->get((int) $row['id']) : null;

            [$pageId, $sectionId] = $this->placement($form, $row, $pages, $sections);

            $attributes = [
                'form_page_id'     => $pageId,
                'form_section_id'  => $sectionId,
                'field_type'       => $type,
                'label'            => $label,
                'field_key'        => $field && isset($kept[$field->id])
                    ? $kept[$field->id]
                    : $this->uniqueKey($row, $label, $keys),
                'placeholder'      => trim((string) ($row['placeholder'] ?? '')) ?: null,
                'help_text'        => trim((string) ($row['help_text'] ?? '')) ?: null,
                'is_required'      => (bool) ($row['is_required'] ?? false),
                'default_value'    => trim((string) ($row['default_value'] ?? '')) ?: null,
                'validation_rules' => $this->validationRules($type, $row),
                'settings'         => $this->fieldSettings($type, $row),
                'sort_order'       => $order++,
            ];

            if ($field) {
                $field->update($attributes);
            } else {
                $field = $form->fields()->create($attributes);
            }

            $seen[] = $field->id;

            $this->syncChoices($field, $type, $row);
        }

        $this->removeMissing($existing, $seen);
    }

    /**
     * Which page and section one posted question lands in.
     *
     * The form's structure decides which of the two can be set at all, so a form
     * switched from multi-page to plain has its questions released from their
     * pages in the same save — rather than keeping ids that nothing renders and
     * that would resurface the moment it was switched back.
     *
     * On a form with both, the page is taken from the SECTION rather than from
     * the row's own page_ref: a question is in a section, and that section is on
     * exactly one page. Reading both independently would let the two disagree.
     *
     * @param  array<string, FormPage>     $pages
     * @param  array<string, FormSection>  $sections
     * @return array{0: ?int, 1: ?int}
     */
    private function placement(Form $form, array $row, array $pages, array $sections): array
    {
        $section = $form->hasSections()
            ? ($sections[(string) ($row['section_ref'] ?? '')] ?? null)
            : null;

        if (! $form->hasPages()) {
            return [null, $section?->id];
        }

        $page = $section
            ? $section->form_page_id
            : ($pages[(string) ($row['page_ref'] ?? '')]->id ?? null);

        return [$page, $section?->id];
    }

    /**
     * Fields the builder no longer lists.
     *
     * One with answers is soft-deleted: it leaves the form and the response
     * table, and every value already filed against it stays reachable. One with
     * no answers is nothing to anybody, so it goes properly and takes its
     * options with it.
     */
    private function removeMissing($existing, array $seen): void
    {
        foreach ($existing as $field) {
            if (in_array($field->id, $seen, true)) {
                continue;
            }

            $hasAnswers = DB::table('form_response_values')->where('field_id', $field->id)->exists();

            $hasAnswers ? $field->delete() : $field->forceDelete();
        }
    }

    /**
     * A storage key that is unique within this form.
     *
     * Taken from what the admin typed where they typed one, otherwise generated
     * from the label. A collision counts up rather than being refused — two
     * questions legitimately read "Name", and the admin should not have to
     * invent database keys to have them.
     */
    private function uniqueKey(array $row, string $label, array &$taken): string
    {
        $typed = FormField::keyFrom($row['field_key'] ?? '');
        $base  = ($typed !== 'field' && filled($row['field_key'] ?? null)) ? $typed : FormField::keyFrom($label);
        $base  = Str::limit($base, 110, '');

        $key = $base;
        $n   = 1;

        while (in_array($key, $taken, true) || in_array($key, self::RESERVED_KEYS, true)) {
            $key = $base . '_' . ++$n;
        }

        $taken[] = $key;

        return $key;
    }

    /** Validation settings that hold text rather than a number. */
    private const TEXT_RULES = ['scale_min_label', 'scale_max_label', 'rating_icon', 'min_date', 'max_date', 'min_time', 'max_time'];

    /**
     * Only the validation settings this type actually offers.
     *
     * Driven off the registry, so a new type's settings are stored the moment it
     * declares them — there is no second list here to keep in step.
     */
    private function validationRules(string $type, array $row): array
    {
        $rules = [];

        foreach (FormFieldType::validations($type) as $key) {
            if ($key === 'file_types') {
                $rules['file_types'] = array_values(array_intersect(
                    array_map('strtolower', (array) ($row['file_types'] ?? [])),
                    FormFieldType::ALLOWED_FILE_EXTENSIONS,
                ));

                continue;
            }

            $value = $row[$key] ?? null;

            if ($value === null || $value === '') {
                $rules[$key] = null;

                continue;
            }

            $rules[$key] = in_array($key, self::TEXT_RULES, true) ? trim((string) $value) : (int) $value;
        }

        return array_filter($rules, fn ($value) => $value !== null && $value !== '' && $value !== []);
    }

    /** Per-type extras plus the conditional-visibility rule. */
    private function fieldSettings(string $type, array $row): array
    {
        $settings = [];

        if ($type === FormFieldType::FILE) {
            $settings['multiple'] = (bool) ($row['multiple'] ?? false);
        }

        // A Multiple choice question's right answer, stored as the option's
        // VALUE — the thing a response stores — so the two can be compared
        // directly later. Kept only when it really is one of the options posted
        // alongside it: FormBuilderRequest refuses a mismatch, and this is the
        // second line for any caller that skipped the request.
        if ($type === FormFieldType::RADIO && ($answer = $this->correctAnswer($row)) !== null) {
            $settings['correct_answer'] = $answer;
        }

        // Stored against the controlling field's KEY, not its id: duplicating a
        // form mints new ids, and a condition pointing at an id would then point
        // at the original form's field.
        if (filled($row['cond_field_key'] ?? null)) {
            $settings['condition'] = [
                'field_key' => (string) $row['cond_field_key'],
                'operator'  => in_array($row['cond_operator'] ?? '', ['equals', 'not_equals'], true)
                    ? $row['cond_operator']
                    : 'equals',
                'value'     => (string) ($row['cond_value'] ?? ''),
            ];
        }

        return $settings;
    }

    /** The posted correct answer, resolved to an option's stored value, or null. */
    private function correctAnswer(array $row): ?string
    {
        $answer = trim((string) ($row['correct_answer'] ?? ''));

        return $answer === '' ? null : self::matchOption((array) ($row['options'] ?? []), $answer);
    }

    /**
     * The stored value of the option an answer names, or null when it names none.
     *
     * Matched against each option's value AND its label, exactly (after
     * trimming): the builder's picker posts values, a spreadsheet has only the
     * wording to give, and an option left without a value stores its label
     * anyway. Shared with FormBuilderRequest so the two cannot disagree about
     * what counts as a match.
     */
    public static function matchOption(array $options, string $answer): ?string
    {
        $answer = trim($answer);

        foreach ($options as $option) {
            $label = trim((string) ($option['label'] ?? ''));

            if ($label === '') {
                continue;
            }

            $value = trim((string) ($option['value'] ?? '')) ?: $label;

            if ($answer === $value || $answer === $label) {
                return $value;
            }
        }

        return null;
    }

    /* =============================== OPTIONS =============================== */

    /**
     * Replace a field's choices — its options, or a grid's rows and columns.
     *
     * Delete-and-recreate is safe here in a way it is not for fields: a response
     * stores the choice's VALUE as text, never its id, so rewriting these rows
     * cannot orphan an answer.
     */
    private function syncChoices(FormField $field, string $type, array $row): void
    {
        // Everything goes first, so switching a grid to a dropdown (or the other
        // way) cannot leave the previous type's lists behind to be rendered.
        $field->allOptions()->delete();

        if (FormFieldType::needsOptions($type)) {
            $this->writeChoices($field, FormFieldOption::OPTION, $row['options'] ?? []);

            // A choice field with nothing to choose from renders a control
            // nobody can complete, so Yes/No falls back to its two.
            if ($type === FormFieldType::YES_NO && $field->options()->count() === 0) {
                $this->writeChoices($field, FormFieldOption::OPTION, FormFieldType::YES_NO_OPTIONS);
            }

            return;
        }

        if (FormFieldType::needsGrid($type)) {
            $this->writeChoices($field, FormFieldOption::ROW, $row['rows'] ?? []);
            $this->writeChoices($field, FormFieldOption::COLUMN, $row['columns'] ?? []);
        }
    }

    /** Write one list — options, rows or columns — in the order it arrived. */
    private function writeChoices(FormField $field, string $group, array $rows): void
    {
        $order = 0;
        $taken = [];

        foreach ($rows as $row) {
            $label = trim((string) ($row['label'] ?? ''));

            if ($label === '') {
                continue;
            }

            // The stored value defaults to the label, which is what an admin who
            // never opens the "value" box expects to see in their export.
            $value = trim((string) ($row['value'] ?? '')) ?: $label;

            // Two choices sharing a value would make an answer ambiguous, and
            // Rule::in could not tell them apart either.
            if (in_array($value, $taken, true)) {
                continue;
            }

            $taken[] = $value;

            $field->allOptions()->create([
                'group'      => $group,
                'label'      => $label,
                'value'      => $value,
                'sort_order' => $order++,
            ]);
        }
    }

    /* ============================== DUPLICATE ============================== */

    /**
     * Copy a form, its fields and their options — but never its responses.
     *
     * The copy starts as a draft whatever the original's status: publishing is a
     * decision, and duplicating a live form should not quietly put a second one
     * on the internet.
     */
    public function duplicate(Form $form): Form
    {
        return DB::transaction(function () use ($form) {
            $copy = Form::create([
                'name'           => $form->name . ' - Copy',
                'title'          => $form->title,
                'description'    => $form->description,
                'slug'           => Form::uniqueSlug($form->slug . '-copy'),
                'structure_type' => $form->structure(),
                'form_type'      => $form->isQuiz() ? Form::QUIZ : Form::STANDARD,
                'status'         => Form::DRAFT,
                'settings'       => $form->settings,
            ]);

            // The copy gets its own pages and sections, so the two forms can be
            // edited apart. Old id → new id, because everything below is placed
            // by id and pointing the copy's questions at the original's pages
            // would tie the two together for good.
            $pages = [];

            foreach ($form->pages as $page) {
                $pages[$page->id] = $copy->pages()->create(
                    $page->only(['title', 'description', 'sort_order']),
                )->id;
            }

            $sections = [];

            foreach ($form->sections as $section) {
                $sections[$section->id] = $copy->sections()->create([
                    'form_page_id' => $pages[$section->form_page_id] ?? null,
                ] + $section->only(['title', 'description', 'sort_order']))->id;
            }

            foreach ($form->fields()->with('allOptions')->get() as $field) {
                $new = $copy->fields()->create([
                    'form_page_id'    => $pages[$field->form_page_id] ?? null,
                    'form_section_id' => $sections[$field->form_section_id] ?? null,
                ] + $field->only([
                    'field_type', 'label', 'field_key', 'placeholder', 'help_text',
                    'is_required', 'default_value', 'validation_rules', 'settings', 'sort_order',
                ]));

                // Options, grid rows and grid columns alike — the group rides
                // along, so a duplicated grid is still a grid.
                foreach ($field->allOptions as $option) {
                    $new->allOptions()->create($option->only(['group', 'label', 'value', 'sort_order']));
                }
            }

            return $copy;
        });
    }
}
