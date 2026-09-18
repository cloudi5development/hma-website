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
     * and nothing else. FormController::updateSettings validates against this
     * same list rather than keeping a second copy that drifts.
     *
     * No lengths, and no submission cap or one-response-per-person setting:
     * those were limits, and the module has none.
     */
    public static function settingRules(): array
    {
        return [
            'submit_label'    => ['nullable', 'string'],
            'success_message' => ['nullable', 'string'],
            // A relative path is refused: this is put in a Location header, and
            // "javascript:..." must never reach one. That is safety, not a limit.
            'redirect_url'    => ['nullable', 'url'],
            'closed_message'  => ['nullable', 'string'],
            'notify_enabled'  => ['nullable', 'boolean'],
            'notify_emails'   => ['nullable', 'string'],
            'notify_subject'  => ['nullable', 'string'],
        ];
    }

    /**
     * NO LIMITS. Nothing an admin writes has a length cap, and nothing they add
     * has a count cap — questions, choices, pages, sections. What is checked is
     * shape: a real field type, a whole number where one is needed, a correct
     * answer that is one of the question's options.
     *
     * The few `max:` rules left are on values the admin never types — the
     * builder's own row references, and a derived storage key and link that
     * live in fixed-width, indexed columns.
     */
    public function rules(): array
    {
        $id = $this->route('form')?->id;

        // The settings rules ride along so a payload that does carry them (the
        // service's own tests, an API client) is still checked. The builder
        // screens no longer post any.
        return static::onlyForTheirTypes(static::settingRules() + [
            'name'        => ['required', 'string'],
            // Not asked for: the form's name is its title and its link.
            'title'       => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'slug'        => [
                'nullable', 'string', 'max:180', 'alpha_dash',
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
            'pages'                   => ['nullable', 'array'],
            'pages.*.id'              => ['nullable', 'integer'],
            'pages.*.title'           => ['nullable', 'string'],
            'pages.*.description'     => ['nullable', 'string'],

            'sections'                => ['nullable', 'array'],
            'sections.*.id'           => ['nullable', 'integer'],
            'sections.*.page_ref'     => ['nullable', 'string', 'max:40'],
            'sections.*.title'        => ['nullable', 'string'],
            'sections.*.description'  => ['nullable', 'string'],

            /* ------------------------------- fields ------------------------------- */
            'fields'                     => ['nullable', 'array'],
            'fields.*.page_ref'          => ['nullable', 'string', 'max:40'],
            'fields.*.section_ref'       => ['nullable', 'string', 'max:40'],
            // Checked against the question's own options in withValidator.
            'fields.*.correct_answer'    => ['nullable', 'string'],
            'fields.*.id'                => ['nullable', 'integer'],
            'fields.*.field_type'        => ['required', Rule::in(FormFieldType::keys())],
            'fields.*.label'             => ['nullable', 'string'],
            // The storage key: derived from the label and shortened by the
            // builder service, never typed by an admin.
            'fields.*.field_key'         => ['nullable', 'string', 'max:110'],
            'fields.*.placeholder'       => ['nullable', 'string'],
            'fields.*.help_text'         => ['nullable', 'string'],
            'fields.*.is_required'       => ['nullable', 'boolean'],
            'fields.*.default_value'     => ['nullable', 'string'],

            // A length or value window the admin chooses for the ANSWER. Only a
            // floor of 0 / 1 is checked, because a negative length means nothing.
            'fields.*.min_length'        => ['nullable', 'integer', 'min:0'],
            'fields.*.max_length'        => ['nullable', 'integer', 'min:1'],
            'fields.*.min_value'         => ['nullable', 'numeric'],
            'fields.*.max_value'         => ['nullable', 'numeric'],
            // The allowed file types stay a fixed list: that is what keeps a
            // .php or .exe from being accepted — safety, not a limit.
            'fields.*.file_types'        => ['nullable', 'array'],
            'fields.*.file_types.*'      => [Rule::in(FormFieldType::ALLOWED_FILE_EXTENSIONS)],
            'fields.*.max_file_size_kb'  => ['nullable', 'integer', 'min:1'],
            'fields.*.multiple'          => ['nullable', 'boolean'],

            'fields.*.cond_field_key'    => ['nullable', 'string', 'max:110'],
            'fields.*.cond_operator'     => ['nullable', Rule::in(['equals', 'not_equals'])],
            'fields.*.cond_value'        => ['nullable', 'string'],

            // Linear scale — any whole-number range the admin likes; only that
            // the top is above the bottom is checked, in withValidator.
            'fields.*.scale_min'         => ['nullable', 'integer'],
            'fields.*.scale_max'         => ['nullable', 'integer'],
            'fields.*.scale_min_label'   => ['nullable', 'string'],
            'fields.*.scale_max_label'   => ['nullable', 'string'],

            // A rating needs at least two icons to be a choice at all.
            'fields.*.rating_count'      => ['nullable', 'integer', 'min:2'],
            'fields.*.rating_icon'       => ['nullable', Rule::in(array_keys(FormFieldType::RATING_ICONS))],

            'fields.*.min_date'          => ['nullable', 'date'],
            'fields.*.max_date'          => ['nullable', 'date'],
            'fields.*.min_time'          => ['nullable', 'date_format:H:i'],
            'fields.*.max_time'          => ['nullable', 'date_format:H:i'],

            'fields.*.options'           => ['nullable', 'array'],
            'fields.*.options.*.label'   => ['nullable', 'string'],
            'fields.*.options.*.value'   => ['nullable', 'string'],

            // A grid's two lists, managed exactly as options are.
            'fields.*.rows'              => ['nullable', 'array'],
            'fields.*.rows.*.label'      => ['nullable', 'string'],
            'fields.*.rows.*.value'      => ['nullable', 'string'],
            'fields.*.columns'           => ['nullable', 'array'],
            'fields.*.columns.*.label'   => ['nullable', 'string'],
            'fields.*.columns.*.value'   => ['nullable', 'string'],
        ]);
    }

    /**
     * A type's own settings are checked only on a question of that type.
     *
     * Every row posts every panel — a question's rating count is still in the
     * page after it is switched to Short answer, just hidden — and the service
     * keeps only the settings its type offers (FormFieldType's `validations`).
     * Validating the hidden ones too refused a save over a box the admin could
     * no longer see: "The fields.f805.rating_count field must be at least 2."
     * So each is excluded unless the row's type is one that offers it.
     */
    private static function onlyForTheirTypes(array $rules): array
    {
        $offeredBy = [];

        foreach (FormFieldType::TYPES as $type => $spec) {
            foreach ($spec['validations'] ?? [] as $setting) {
                $offeredBy[$setting][] = $type;
            }
        }

        foreach ($offeredBy as $setting => $types) {
            if (isset($rules["fields.*.{$setting}"])) {
                array_unshift($rules["fields.*.{$setting}"], 'exclude_unless:fields.*.field_type,' . implode(',', $types));
            }
        }

        return $rules;
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

            $labelled = 0;

            foreach ((array) $this->input('fields', []) as $key => $row) {
                $type  = $row['field_type'] ?? null;
                $label = trim((string) ($row['label'] ?? ''));

                // A SAVED question whose text has been cleared is not an
                // abandoned row. Dropping it deleted the question — and, when
                // it had answers, took it off the responses table — with no
                // confirmation, just because its text was being retyped. The
                // bin icon is how a question is deleted.
                if ($label === '' && filled($row['id'] ?? null)) {
                    $validator->errors()->add(
                        "fields.{$key}.label",
                        'A question has no text. Type the question, or delete it with the bin icon.',
                    );

                    continue;
                }

                // An abandoned blank row is dropped on save, so it is not held to
                // any of this either.
                if ($label === '') {
                    continue;
                }

                $labelled++;

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

            // Creating a form publishes it and hands out its link, so it must
            // ask something. The Create Form dialog checks this too, but it
            // counted a blank row — which the service then drops — as a
            // question, and a published form with nothing in it went out.
            if ($this->isMethod('post') && ! $this->route('form') && $labelled === 0 && $this->has('fields')) {
                $validator->errors()->add('fields', 'Add at least one question (with its text filled in) before creating the form.');
            }
        });
    }

    protected function prepareForValidation(): void
    {
        $this->unpackPayload();

        // An unticked checkbox posts nothing, so a settings form that leaves
        // "notify" off has to be told it is off. But only a SETTINGS form: this
        // used to run on every builder save too, handing the service a
        // notify_enabled it took as "settings were posted" — and every builder
        // save quietly rewrote the form's settings, switching on "one response
        // per person" on the way.
        if ($this->hasAny(array_keys(Form::SETTING_DEFAULTS))) {
            $this->merge(['notify_enabled' => $this->boolean('notify_enabled')]);
        }
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

        // The builder always sends its pages and sections, even for a plain
        // form (the page → section nesting is always in the document), so an
        // EMPTY list is never a real save. It is what a second press of Save
        // sent while the first was still on its way — every input already
        // packed and disabled — and taken at its word it removed every
        // question, page and section on the form.
        if (! is_array($pairs) || $pairs === []) {
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

        // ...and the same on the ORIGINAL request. A form request is a copy,
        // and when validation fails Laravel flashes the original's input as
        // old() — which held only the JSON string, the questions themselves
        // having been packed into it. The builder then found no old('fields')
        // and redrew the form from the database: every unsaved question,
        // page and section gone because one of them had an error. With the
        // unpacked arrays here the builder redraws exactly what was posted,
        // and the JSON (the same data again, however large) is not flashed.
        $original = $this->container?->make('request');

        if ($original instanceof \Illuminate\Http\Request && $original !== $this) {
            $original->request->remove('builder_payload');

            foreach (self::PAYLOAD_ROOTS as $root) {
                $original->request->set($root, $data[$root] ?? []);
            }
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
            'fields.*.field_type.required' => 'Every field needs a type.',
            'fields.*.field_type.in'       => 'That is not a field type this builder offers.',
        ];
    }
}
