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

            // Which of the four shapes. Absent on an older payload, and the
            // service reads that as "leave the form's shape alone".
            'structure_type' => ['nullable', Rule::in(array_keys(Form::STRUCTURES))],
            'form_type'      => ['nullable', Rule::in(array_keys(Form::FORM_TYPES))],

            /* ---------------------------- pages, sections -------------------------
               Titles are optional throughout: a page that only exists to break a
               long form in half needs no name, and the renderer numbers it. The
               refs are the builder's own keys for rows it has not saved yet —
               opaque strings that let a question name the page it is sitting in
               before that page has an id. */
            'pages'                   => ['nullable', 'array', 'max:' . FormBuilderService::MAX_PAGES],
            'pages.*.id'              => ['nullable', 'integer'],
            'pages.*.title'           => ['nullable', 'string', 'max:190'],
            'pages.*.description'     => ['nullable', 'string', 'max:1000'],

            'sections'                => ['nullable', 'array', 'max:' . FormBuilderService::MAX_SECTIONS],
            'sections.*.id'           => ['nullable', 'integer'],
            'sections.*.page_ref'     => ['nullable', 'string', 'max:40'],
            'sections.*.title'        => ['nullable', 'string', 'max:190'],
            'sections.*.description'  => ['nullable', 'string', 'max:1000'],

            /* ------------------------------- fields ------------------------------- */
            'fields.*.page_ref'          => ['nullable', 'string', 'max:40'],
            'fields.*.section_ref'       => ['nullable', 'string', 'max:40'],
            // Checked against the question's own options in withValidator.
            'fields.*.correct_answer'    => ['nullable', 'string', 'max:190'],
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
            if ($this->payloadUnreadable) {
                $validator->errors()->add(
                    'fields',
                    'Your questions could not be read from the page. Nothing was saved — reload the page and try again.',
                );

                return;
            }

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

                // A correct answer that is not one of the question's own options
                // would mark every response wrong. Not REQUIRED here, even on a
                // quiz: an admin building one adds the answers as they go, and
                // refusing to save a half-built quiz would lose the half.
                $answer = trim((string) ($row['correct_answer'] ?? ''));

                if ($type === FormFieldType::RADIO && $answer !== ''
                    && FormBuilderService::matchOption((array) ($row['options'] ?? []), $answer) === null) {
                    $validator->errors()->add(
                        "fields.{$key}.correct_answer",
                        "“{$label}”: the correct answer “{$answer}” is not one of its options.",
                    );
                }
            }
        });
    }

    protected function prepareForValidation(): void
    {
        $this->unpackPayload();

        $this->merge([
            'allow_multiple' => $this->boolean('allow_multiple'),
            'notify_enabled' => $this->boolean('notify_enabled'),
        ]);
    }

    /* ============================ THE PAYLOAD ==============================
       Why the builder's questions arrive as one JSON string.

       PHP stops reading a request after max_input_vars inputs — 1000 here and
       on most hosts — and DROPS THE REST WITHOUT AN ERROR. A question row is a
       dozen-odd inputs plus two per option, so a 50-question quiz with four
       options each is ~1,050 inputs, and its last questions simply never reached
       this class. Nothing failed; they were just gone after Save. Bulk upload is
       precisely what makes a form that size a minute's work.

       So the builder script packs every fields[…] / pages[…] / sections[…]
       input into a single `builder_payload` — one input, however large the form
       — as the list of [name, value] pairs the browser would have sent, in the
       order it would have sent them. This rebuilds the nested arrays from that
       list, applying the same rules PHP's own parser does: `[]` appends, a
       repeated name overwrites (so a hidden "0" followed by a ticked "1" is 1),
       and insertion order is display order — which is how the builder stores
       order, so it must survive exactly.

       A plain POST without the payload (the tests, any older client) is read
       exactly as before. */

    /** Kept out of the flashed old input: it duplicates what is unpacked from it. */
    protected $dontFlash = ['builder_payload'];

    /** Upper bound on pairs, so a hostile payload cannot build an unbounded array. */
    private const MAX_PAYLOAD_PAIRS = 50000;

    /** Only these top-level names may be written from the payload. */
    private const PAYLOAD_ROOTS = ['fields', 'pages', 'sections'];

    private bool $payloadUnreadable = false;

    private function unpackPayload(): void
    {
        if (! $this->has('builder_payload')) {
            return;
        }

        $pairs = json_decode((string) $this->input('builder_payload'), true);

        $this->request->remove('builder_payload');

        if (! is_array($pairs) || count($pairs) > self::MAX_PAYLOAD_PAIRS) {
            $this->payloadUnreadable = true;

            return;
        }

        $data = [];

        foreach ($pairs as $pair) {
            if (! is_array($pair) || count($pair) !== 2 || ! is_string($pair[0]) || ! is_scalar($pair[1] ?? '')) {
                continue;
            }

            self::assign($data, $pair[0], (string) ($pair[1] ?? ''));
        }

        // Replaced wholesale rather than merged key by key: the payload is the
        // complete list, and a stale input of the same name must not survive
        // inside it.
        foreach (self::PAYLOAD_ROOTS as $root) {
            $this->request->set($root, $data[$root] ?? []);
        }
    }

    /**
     * Write one "a[b][c]" / "a[b][]" name into a nested array, the way PHP's
     * request parser would. Names outside PAYLOAD_ROOTS are ignored.
     */
    private static function assign(array &$data, string $name, string $value): void
    {
        if (! preg_match('/^([a-z_]+)((?:\[[^\[\]]*\])*)$/i', $name, $m) || ! in_array($m[1], self::PAYLOAD_ROOTS, true)) {
            return;
        }

        preg_match_all('/\[([^\[\]]*)\]/', $m[2], $segments);

        $keys = array_merge([$m[1]], $segments[1]);
        $node = &$data;
        $last = count($keys) - 1;

        foreach ($keys as $i => $key) {
            if ($i === $last) {
                $key === '' ? $node[] = $value : $node[$key] = $value;

                break;
            }

            if ($key === '') {
                $node[] = [];
                $key    = array_key_last($node);
            }

            if (! isset($node[$key]) || ! is_array($node[$key])) {
                $node[$key] = [];
            }

            $node = &$node[$key];
        }
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
