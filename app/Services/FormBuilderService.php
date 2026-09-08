<?php

namespace App\Services;

use App\Models\Form;
use App\Models\FormField;
use App\Models\FormFieldOption;
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
    /** Ceiling on questions per form. Matches the max: rule in FormBuilderRequest. */
    public const MAX_FIELDS = 60;

    /** Ceiling on choices under one field. */
    public const MAX_OPTIONS = 60;

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

            $this->syncFields($form, $data['fields'] ?? []);

            return $form->fresh(['fields.options']);
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

        // Settings are only editable once a form exists, so a create must not
        // wipe them and an edit that does not post them must not either.
        if (array_key_exists('submit_label', $data) || array_key_exists('notify_enabled', $data)) {
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

        $settings['allow_multiple']  = (bool) ($data['allow_multiple'] ?? false);
        $settings['notify_enabled']  = (bool) ($data['notify_enabled'] ?? false);
        $settings['max_submissions'] = filled($data['max_submissions'] ?? null)
            ? (int) $data['max_submissions']
            : null;

        return $settings;
    }

    /* =============================== FIELDS ================================ */

    /**
     * Reconcile the posted rows against the fields already on the form.
     *
     * Display order is the order the rows arrived in — a form posts its inputs
     * in document order, so the builder's drag handle and its ▲/▼ buttons are
     * the whole of the reordering story and no order field is submitted.
     */
    private function syncFields(Form $form, array $rows): void
    {
        $existing = $form->fields()->get()->keyBy('id');
        $seen     = [];
        $keys     = [];
        $order    = 0;

        foreach (array_slice($rows, 0, self::MAX_FIELDS, true) as $row) {
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

            $attributes = [
                'field_type'       => $type,
                'label'            => $label,
                'field_key'        => $this->uniqueKey($row, $label, $keys),
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

        foreach (array_slice($rows, 0, self::MAX_OPTIONS, true) as $row) {
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
                'name'        => Str::limit($form->name . ' - Copy', 255, ''),
                'title'       => $form->title,
                'description' => $form->description,
                'slug'        => Form::uniqueSlug($form->slug . '-copy'),
                'status'      => Form::DRAFT,
                'settings'    => $form->settings,
            ]);

            foreach ($form->fields()->with('allOptions')->get() as $field) {
                $new = $copy->fields()->create($field->only([
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
