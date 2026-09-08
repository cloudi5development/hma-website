<?php

namespace App\Http\Requests\Backend;

use App\Models\Form;
use App\Services\FormBuilderService;
use App\Support\FormFieldType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates the form builder's payload.
 *
 * Named FormBuilderRequest rather than FormRequest for the obvious reason — the
 * class it extends already has that name.
 *
 * The field rows are validated structurally (a real type, a label that fits, a
 * sane number of options) but NOT for completeness: a half-filled row an admin
 * added and abandoned is dropped by the builder service rather than blocking
 * the save. Refusing to save the whole form over one stray empty row is the
 * quickest way to make a builder infuriating.
 */
class FormBuilderRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Route is already behind admin.auth + admin.module.
        return true;
    }

    /**
     * The rules for the form's settings.
     *
     * Split out because the settings are no longer edited on the builder — the
     * create and edit screens ask for a name, a description and the questions,
     * and nothing else. They are edited on the form's own page instead, and
     * FormController::updateSettings validates against this same list rather
     * than keeping a second copy that drifts.
     */
    public static function settingRules(): array
    {
        return [
            'submit_label'    => ['nullable', 'string', 'max:60'],
            'success_message' => ['nullable', 'string', 'max:500'],
            // A relative path is refused: this is put in a Location header, and
            // "javascript:..." must never reach one.
            'redirect_url'    => ['nullable', 'url', 'max:500'],
            'allow_multiple'  => ['nullable', 'boolean'],
            'max_submissions' => ['nullable', 'integer', 'min:1', 'max:1000000'],
            'closed_message'  => ['nullable', 'string', 'max:500'],
            'notify_enabled'  => ['nullable', 'boolean'],
            'notify_emails'   => ['nullable', 'string', 'max:500'],
            'notify_subject'  => ['nullable', 'string', 'max:190'],
        ];
    }

    public function rules(): array
    {
        $id = $this->route('form')?->id;

        // The settings rules ride along so a payload that does carry them (the
        // service's own tests, an API client) is still checked. The builder
        // screens no longer post any.
        return static::settingRules() + [
            'name'        => ['required', 'string', 'max:190'],
            // Not asked for: the form's name is its title and its link. Still
            // validated, because an edit screen may yet offer it.
            'title'       => ['nullable', 'string', 'max:190'],
            'description' => ['nullable', 'string', 'max:2000'],
            'slug'        => [
                'nullable', 'string', 'max:190', 'alpha_dash',
                Rule::unique('forms', 'slug')->ignore($id),
            ],
            'status'      => ['nullable', Rule::in(array_keys(Form::STATUSES))],

            /* ------------------------------- fields ------------------------------- */
            'fields'                     => ['nullable', 'array', 'max:' . FormBuilderService::MAX_FIELDS],
            'fields.*.id'                => ['nullable', 'integer'],
            'fields.*.field_type'        => ['required', Rule::in(FormFieldType::keys())],
            'fields.*.label'             => ['nullable', 'string', 'max:190'],
            'fields.*.field_key'         => ['nullable', 'string', 'max:110'],
            'fields.*.placeholder'       => ['nullable', 'string', 'max:190'],
            'fields.*.help_text'         => ['nullable', 'string', 'max:500'],
            'fields.*.is_required'       => ['nullable', 'boolean'],
            'fields.*.default_value'     => ['nullable', 'string', 'max:500'],

            'fields.*.min_length'        => ['nullable', 'integer', 'min:0', 'max:65535'],
            'fields.*.max_length'        => ['nullable', 'integer', 'min:1', 'max:65535'],
            'fields.*.min_value'         => ['nullable', 'numeric'],
            'fields.*.max_value'         => ['nullable', 'numeric'],
            'fields.*.file_types'        => ['nullable', 'array'],
            'fields.*.file_types.*'      => [Rule::in(FormFieldType::ALLOWED_FILE_EXTENSIONS)],
            'fields.*.max_file_size_kb'  => ['nullable', 'integer', 'min:1', 'max:' . FormFieldType::MAX_FILE_KB],
            'fields.*.multiple'          => ['nullable', 'boolean'],

            'fields.*.cond_field_key'    => ['nullable', 'string', 'max:110'],
            'fields.*.cond_operator'     => ['nullable', Rule::in(['equals', 'not_equals'])],
            'fields.*.cond_value'        => ['nullable', 'string', 'max:190'],

            // Linear scale — the range is the admin's, within the module's floor
            // and ceiling. Nothing here assumes 1 to 5.
            'fields.*.scale_min'         => ['nullable', 'integer', 'min:' . FormFieldType::SCALE_FLOOR, 'max:' . FormFieldType::SCALE_CEILING],
            'fields.*.scale_max'         => ['nullable', 'integer', 'min:' . FormFieldType::SCALE_FLOOR, 'max:' . FormFieldType::SCALE_CEILING],
            'fields.*.scale_min_label'   => ['nullable', 'string', 'max:60'],
            'fields.*.scale_max_label'   => ['nullable', 'string', 'max:60'],

            'fields.*.rating_count'      => ['nullable', 'integer', 'min:2', 'max:' . FormFieldType::RATING_MAX_COUNT],
            'fields.*.rating_icon'       => ['nullable', Rule::in(array_keys(FormFieldType::RATING_ICONS))],

            'fields.*.min_date'          => ['nullable', 'date'],
            'fields.*.max_date'          => ['nullable', 'date'],
            'fields.*.min_time'          => ['nullable', 'date_format:H:i'],
            'fields.*.max_time'          => ['nullable', 'date_format:H:i'],

            'fields.*.options'           => ['nullable', 'array', 'max:' . FormBuilderService::MAX_OPTIONS],
            'fields.*.options.*.label'   => ['nullable', 'string', 'max:190'],
            'fields.*.options.*.value'   => ['nullable', 'string', 'max:190'],

            // A grid's two lists, managed exactly as options are.
            'fields.*.rows'              => ['nullable', 'array', 'max:' . FormBuilderService::MAX_OPTIONS],
            'fields.*.rows.*.label'      => ['nullable', 'string', 'max:190'],
            'fields.*.rows.*.value'      => ['nullable', 'string', 'max:190'],
            'fields.*.columns'           => ['nullable', 'array', 'max:' . FormBuilderService::MAX_OPTIONS],
            'fields.*.columns.*.label'   => ['nullable', 'string', 'max:190'],
            'fields.*.columns.*.value'   => ['nullable', 'string', 'max:190'],
        ];
    }

    /**
     * A field with nothing to choose from renders a control nobody can complete,
     * so it is caught here rather than shipped to the public page. A grid needs
     * BOTH lists: rows with no columns is a list of questions with no answers.
     *
     * Checked in withValidator because it needs a row's type and its lists at
     * the same time, and because a scale's two ends have to be compared.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            foreach ((array) $this->input('fields', []) as $key => $row) {
                $type  = $row['field_type'] ?? null;
                $label = trim((string) ($row['label'] ?? ''));

                // An abandoned blank row is dropped on save, so it is not held to
                // any of this either.
                if ($label === '') {
                    continue;
                }

                $filled = fn (string $list) => collect($row[$list] ?? [])
                    ->filter(fn ($item) => trim((string) ($item['label'] ?? '')) !== '')
                    ->count();

                if (FormFieldType::needsOptions($type) && $filled('options') === 0) {
                    $validator->errors()->add(
                        "fields.{$key}.options",
                        "“{$label}” is a " . FormFieldType::label($type) . ' — add at least one option for people to choose from.',
                    );
                }

                if (FormFieldType::needsGrid($type)) {
                    if ($filled('rows') === 0) {
                        $validator->errors()->add("fields.{$key}.rows", "“{$label}” needs at least one row.");
                    }

                    if ($filled('columns') === 0) {
                        $validator->errors()->add("fields.{$key}.columns", "“{$label}” needs at least one column.");
                    }
                }

                // A scale that ends where it starts has one step and is not a
                // scale; one that ends below its start cannot be drawn at all.
                if ($type === FormFieldType::LINEAR_SCALE) {
                    $min = $row['scale_min'] ?? null;
                    $max = $row['scale_max'] ?? null;

                    if (filled($min) && filled($max) && (int) $max <= (int) $min) {
                        $validator->errors()->add(
                            "fields.{$key}.scale_max",
                            "“{$label}”: the top of the scale must be higher than the bottom.",
                        );
                    }
                }
            }
        });
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'allow_multiple' => $this->boolean('allow_multiple'),
            'notify_enabled' => $this->boolean('notify_enabled'),
        ]);
    }

    public function messages(): array
    {
        return [
            'name.required'        => 'Give the form a name — it becomes the heading visitors read and the link they open.',
            'slug.alpha_dash'      => 'The link can only contain letters, numbers, dashes and underscores.',
            'slug.unique'          => 'Another form is already using that link. Pick a different one.',
            'redirect_url.url'     => 'The redirect must be a full URL, e.g. https://example.com/thank-you',
            'fields.max'           => 'A form may hold at most ' . FormBuilderService::MAX_FIELDS . ' fields.',
            'fields.*.field_type.required' => 'Every field needs a type.',
            'fields.*.field_type.in'       => 'That is not a field type this builder offers.',
        ];
    }
}
