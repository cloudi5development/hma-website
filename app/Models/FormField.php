<?php

namespace App\Models;

use App\Support\FormFieldType;
use App\Support\UploadLimit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * One question on a form.
 *
 * Soft-deleted, and that is load-bearing: a question removed from a form that
 * already has responses must leave those answers readable, and a hard delete
 * would null the link they hang from. See the forms migration.
 *
 * The type is a key into App\Support\FormFieldType, which is what decides how
 * this field is drawn, which settings it offers, and how a submitted value is
 * checked. Nothing about a particular question is written into this class.
 */
class FormField extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'form_id', 'form_page_id', 'form_section_id', 'field_type', 'label', 'field_key',
        'placeholder', 'help_text', 'is_required', 'default_value', 'validation_rules',
        'settings', 'sort_order',
    ];

    protected $casts = [
        'is_required'      => 'boolean',
        'validation_rules' => 'array',
        'settings'         => 'array',
        'sort_order'       => 'integer',
    ];

    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }

    /**
     * Where this question sits. Both are null on a plain form, and either may be
     * null on any form — see the structure migration for why that is deliberate
     * and how FormLayout draws a question that has come loose.
     */
    public function page(): BelongsTo
    {
        return $this->belongsTo(FormPage::class, 'form_page_id');
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(FormSection::class, 'form_section_id');
    }

    /** The plain choices under a dropdown / radio / checkbox. */
    public function options(): HasMany
    {
        return $this->hasMany(FormFieldOption::class)
            ->where('group', FormFieldOption::OPTION)
            ->orderBy('sort_order')->orderBy('id');
    }

    /** A grid's row headings. */
    public function rows(): HasMany
    {
        return $this->hasMany(FormFieldOption::class)
            ->where('group', FormFieldOption::ROW)
            ->orderBy('sort_order')->orderBy('id');
    }

    /** A grid's column headings. */
    public function columns(): HasMany
    {
        return $this->hasMany(FormFieldOption::class)
            ->where('group', FormFieldOption::COLUMN)
            ->orderBy('sort_order')->orderBy('id');
    }

    /** Every choice of every kind — what the builder loads and what a delete clears. */
    public function allOptions(): HasMany
    {
        return $this->hasMany(FormFieldOption::class)->orderBy('sort_order')->orderBy('id');
    }

    /* ================================ TYPE ================================= */

    public function control(): string
    {
        return FormFieldType::control($this->field_type);
    }

    public function typeLabel(): string
    {
        return FormFieldType::label($this->field_type);
    }

    public function needsOptions(): bool
    {
        return FormFieldType::needsOptions($this->field_type);
    }

    public function isGrid(): bool
    {
        return FormFieldType::needsGrid($this->field_type);
    }

    public function hasChoices(): bool
    {
        return FormFieldType::hasChoices($this->field_type);
    }

    /* ---------------------------- SCALE / RATING --------------------------- */

    /** A linear scale's low end. Configurable — 1 is only where it starts. */
    public function scaleMin(): int
    {
        $min = $this->rule('scale_min');

        return $min === null ? FormFieldType::SCALE_DEFAULT_MIN : (int) $min;
    }

    /** A linear scale's high end, always at least one step above the low end. */
    public function scaleMax(): int
    {
        $max = $this->rule('scale_max');
        $max = $max === null ? FormFieldType::SCALE_DEFAULT_MAX : (int) $max;

        return max($max, $this->scaleMin() + 1);
    }

    /** The steps a scale offers, e.g. [1,2,3,4,5] — or [0,…,10] if that is what was set. */
    public function scaleSteps(): array
    {
        return range($this->scaleMin(), $this->scaleMax());
    }

    public function scaleMinLabel(): ?string
    {
        return $this->rule('scale_min_label');
    }

    public function scaleMaxLabel(): ?string
    {
        return $this->rule('scale_max_label');
    }

    /** How many stars (or hearts, or circles) a rating field offers. */
    public function ratingCount(): int
    {
        $count = (int) ($this->rule('rating_count') ?: FormFieldType::RATING_DEFAULT_COUNT);

        // Two is the least that is still a choice; there is no most.
        return max(2, $count);
    }

    /** The shape a rating is drawn with — star, heart or circle. */
    public function ratingIcon(): string
    {
        $icon = (string) $this->rule('rating_icon');

        return array_key_exists($icon, FormFieldType::RATING_ICONS) ? $icon : 'star';
    }

    /** True when a submitted value is a list rather than a single string. */
    public function isMultiple(): bool
    {
        return FormFieldType::isMultiple($this->field_type)
            || ($this->field_type === FormFieldType::FILE && $this->allowsMultipleFiles());
    }

    public function isHidden(): bool
    {
        return $this->field_type === FormFieldType::HIDDEN;
    }

    public function isFile(): bool
    {
        return $this->field_type === FormFieldType::FILE;
    }

    /* ============================== SETTINGS =============================== */

    public function rule(string $key): mixed
    {
        $value = $this->validation_rules[$key] ?? null;

        return ($value === null || $value === '') ? null : $value;
    }

    public function setting(string $key): mixed
    {
        return $this->settings[$key] ?? null;
    }

    /**
     * The stored VALUE of the right option, on a Multiple choice question.
     *
     * Kept whatever the form's type — a quiz switched back to a standard form
     * and forward again should not have lost its answers in between. It is
     * never rendered on the public page.
     */
    public function correctAnswer(): ?string
    {
        $answer = $this->field_type === FormFieldType::RADIO ? $this->setting('correct_answer') : null;

        return filled($answer) ? (string) $answer : null;
    }

    public function allowsMultipleFiles(): bool
    {
        return $this->isFile() && (bool) $this->setting('multiple');
    }

    /**
     * The extensions this file field takes, always intersected with the module's
     * allow-list — an admin cannot widen it to something executable by typing
     * "php" into the box.
     *
     * @return array<int, string>
     */
    public function allowedExtensions(): array
    {
        $configured = collect((array) $this->rule('file_types'))
            ->map(fn ($ext) => strtolower(trim((string) $ext, " \t.")))
            ->filter()
            ->intersect(FormFieldType::ALLOWED_FILE_EXTENSIONS)
            ->unique()
            ->values()
            ->all();

        return $configured ?: FormFieldType::DEFAULT_FILE_EXTENSIONS;
    }

    /**
     * The size ceiling in KB.
     *
     * The module sets none of its own. What is left is the server's —
     * upload_max_filesize / post_max_size in php.ini, which PHP enforces before
     * this code ever runs — so that is the figure used, and the one the form
     * shows, rather than a number the server would not honour anyway. An admin
     * may still choose a smaller one for a particular field.
     */
    public function maxFileKb(): int
    {
        $configured = (int) $this->rule('max_file_size_kb');

        return $configured > 0 ? UploadLimit::cap($configured) : UploadLimit::kilobytes();
    }

    /** The `accept` attribute for the file input — a convenience, never the guard. */
    public function acceptAttribute(): string
    {
        return collect($this->allowedExtensions())->map(fn ($e) => '.' . $e)->implode(',');
    }

    /* =========================== CONDITIONAL LOGIC ========================= */

    /**
     * The rule that decides whether this field is shown, or null when it always is.
     *
     * Stored against another field's KEY rather than its id, so duplicating a
     * form (which creates new ids) carries its conditions across intact.
     *
     * @return array{field_key: string, operator: string, value: string}|null
     */
    public function condition(): ?array
    {
        $condition = $this->setting('condition');

        if (! is_array($condition) || blank($condition['field_key'] ?? null)) {
            return null;
        }

        // A condition on a question that is no longer on the form can never be
        // met or failed — the page finds no input to watch and the server
        // finds no answer. Left in force it hid this question from every
        // visitor for good, so it is treated as no condition at all: the page,
        // the server and the builder all read this one method, and so agree.
        if (! $this->controllerExists((string) $condition['field_key'])) {
            return null;
        }

        return [
            'field_key' => (string) $condition['field_key'],
            'operator'  => in_array($condition['operator'] ?? '', ['equals', 'not_equals'], true)
                ? $condition['operator']
                : 'equals',
            'value'     => (string) ($condition['value'] ?? ''),
        ];
    }

    /**
     * Whether this field's condition is met by what was actually submitted.
     *
     * A field the visitor never saw must not then fail its "required" rule, so
     * the submission service asks this before it builds the rules — see
     * FormSubmissionService.
     */
    /** @var array<string, bool> Whether each controlling key names a live question — asked once per key. */
    private array $controllers = [];

    private function controllerExists(string $key): bool
    {
        return $this->controllers[$key] ??= static::where('form_id', $this->form_id)
            ->where('field_key', $key)
            ->whereKeyNot($this->getKey())
            ->exists();
    }

    /**
     * A mobile number as it should be checked and stored: the ten digits.
     *
     * People type the spaces, dashes, dots and brackets they are used to
     * ("98765 43210", "(987) 654-3210") and often the country code ("+91 …");
     * those are taken off here. Nothing else is: a number that is still not ten
     * digits afterwards is refused by the rule, never trimmed to fit.
     */
    public static function normaliseMobile(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        $digits = preg_replace('/[\s().\-]+/', '', trim($value));

        return str_starts_with($digits, '+91') ? substr($digits, 3) : $digits;
    }

    public function isVisibleFor(array $input): bool
    {
        $condition = $this->condition();

        if (! $condition) {
            return true;
        }

        $actual = $input[$condition['field_key']] ?? null;

        // A checkbox controlling another field matches when the value is among
        // the boxes that were ticked. Flattened first: a tick-box grid answers
        // with a list per row, and strval() of a list is an error, not a miss.
        $matches = is_array($actual)
            ? in_array($condition['value'], array_map('strval', array_filter(\Illuminate\Support\Arr::flatten($actual), 'is_scalar')), true)
            : (string) $actual === $condition['value'];

        return $condition['operator'] === 'not_equals' ? ! $matches : $matches;
    }

    /* ============================== VALIDATION ============================= */

    /** The name this field posts under. */
    public function inputName(): string
    {
        return $this->field_key;
    }

    /**
     * Laravel rules for this field, built from its own configuration.
     *
     * Returns the rules keyed by input name, so a multi-value field can add its
     * `field.*` member rule alongside the `field` rule itself.
     *
     * `$required` is passed in rather than read off the model because a field
     * hidden by conditional logic is not required whatever its own flag says.
     *
     * @return array<string, array>
     */
    public function validationRules(bool $required): array
    {
        $name  = $this->inputName();
        $first = $required ? 'required' : 'nullable';

        return match ($this->field_type) {
            FormFieldType::LONG_TEXT => [
                $name => [$first, 'string', ...$this->lengthRules()],
            ],
            FormFieldType::EMAIL => [
                $name => [$first, 'string', 'email:rfc', ...$this->lengthRules()],
            ],
            FormFieldType::MOBILE => [
                // A 10-digit mobile number, exactly. The answer arrives already
                // cleaned by normaliseMobile() — spaces, dashes and a +91 prefix
                // taken off — so this checks the number itself: an 11th digit
                // is refused, not stored.
                $name => [$first, 'string', 'regex:/^[0-9]{10}$/'],
            ],
            FormFieldType::NUMBER => [
                $name => array_filter([
                    $first, 'numeric',
                    $this->rule('min_value') !== null ? 'min:' . $this->rule('min_value') : null,
                    $this->rule('max_value') !== null ? 'max:' . $this->rule('max_value') : null,
                ]),
            ],
            FormFieldType::DATE => [
                $name => array_filter([
                    $first, 'date',
                    $this->rule('min_date') ? 'after_or_equal:' . $this->rule('min_date') : null,
                    $this->rule('max_date') ? 'before_or_equal:' . $this->rule('max_date') : null,
                ]),
            ],
            FormFieldType::TIME => [
                // The window is checked in withValidator-style closures rather
                // than after_or_equal, which compares dates and would read a
                // bare "09:00" against today in a way that is easy to get wrong.
                $name => array_filter([
                    $first, 'date_format:H:i',
                    $this->timeWindowRule(),
                ]),
            ],
            FormFieldType::DATETIME => [$name => [$first, 'date']],

            FormFieldType::LINEAR_SCALE => [
                $name => [$first, 'integer', 'between:' . $this->scaleMin() . ',' . $this->scaleMax()],
            ],
            FormFieldType::RATING => [
                $name => [$first, 'integer', 'between:1,' . $this->ratingCount()],
            ],

            // A grid posts one answer per ROW, keyed by the row's position, so
            // the rules are built per row — see gridRules().
            FormFieldType::MC_GRID,
            FormFieldType::TICK_GRID => $this->gridRules($required),

            FormFieldType::DROPDOWN,
            FormFieldType::RADIO,
            FormFieldType::YES_NO => [
                $name => [$first, 'string', Rule::in($this->optionValues())],
            ],

            FormFieldType::CHECKBOX => [
                $name       => [$first, 'array'],
                $name . '.*' => ['string', Rule::in($this->optionValues())],
            ],

            FormFieldType::FILE => $this->fileRules($required),

            FormFieldType::HIDDEN => [$name => ['nullable', 'string']],

            default => [$name => [$first, 'string', ...$this->lengthRules()]],
        };
    }

    /**
     * min/max length rules — only the ones the admin set.
     *
     * There is no default ceiling on an answer. There used to be (255 for a
     * short answer, 5000 for a paragraph, 190 for an email), and the column an
     * answer is stored in is LONGTEXT, so nothing needed it.
     */
    private function lengthRules(): array
    {
        $rules = [];

        if ($this->rule('min_length') !== null) {
            $rules[] = 'min:' . (int) $this->rule('min_length');
        }

        if ($this->rule('max_length')) {
            $rules[] = 'max:' . (int) $this->rule('max_length');
        }

        return $rules;
    }

    /**
     * File rules. `mimes:` checks the real signature rather than the filename,
     * so a renamed .exe does not get through on its extension.
     */
    private function fileRules(bool $required): array
    {
        $name       = $this->inputName();
        $extensions = implode(',', $this->allowedExtensions());
        $max        = $this->maxFileKb();

        if ($this->allowsMultipleFiles()) {
            return [
                $name        => [$required ? 'required' : 'nullable', 'array'],
                $name . '.*' => ['file', 'mimes:' . $extensions, 'max:' . $max],
            ];
        }

        return [
            $name => [$required ? 'required' : 'nullable', 'file', 'mimes:' . $extensions, 'max:' . $max],
        ];
    }

    /**
     * A closure rule holding a time inside the configured window.
     *
     * `after_or_equal` compares dates, so a bare "09:00" would be resolved
     * against today and the comparison would depend on the clock. Comparing the
     * HH:MM strings is what actually answers "is this within opening hours".
     */
    private function timeWindowRule(): ?\Closure
    {
        $min = $this->rule('min_time');
        $max = $this->rule('max_time');

        if (! $min && ! $max) {
            return null;
        }

        $label = $this->label;

        return function (string $attribute, mixed $value, \Closure $fail) use ($min, $max, $label) {
            if (blank($value)) {
                return;
            }

            if ($min && $value < $min) {
                $fail("“{$label}” must be at or after {$min}.");
            }

            if ($max && $value > $max) {
                $fail("“{$label}” must be at or before {$max}.");
            }
        };
    }

    /**
     * Rules for a grid.
     *
     * Answers arrive keyed by the row's POSITION ("satisfaction[0]"), not by its
     * text: a row labelled "Support / Service" would otherwise put a slash and a
     * space into a validation key, and dot-notation rules cannot address that.
     * The position is mapped back to the row on the way into storage.
     *
     * A required multiple-choice grid means every row answered — a half-filled
     * grid is the thing this is guarding against.
     */
    private function gridRules(bool $required): array
    {
        $name    = $this->inputName();
        $columns = $this->columns->pluck('value')->all();
        $rules   = [$name => [$required ? 'required' : 'nullable', 'array']];

        foreach ($this->rows as $index => $row) {
            $key = "{$name}.{$index}";

            $rules[$key] = $this->field_type === FormFieldType::TICK_GRID
                ? [$required ? 'required' : 'nullable', 'array']
                : [$required ? 'required' : 'nullable', 'string', Rule::in($columns)];

            if ($this->field_type === FormFieldType::TICK_GRID) {
                $rules["{$key}.*"] = ['string', Rule::in($columns)];
            }
        }

        return $rules;
    }

    /** @return array<int, string> */
    public function optionValues(): array
    {
        return $this->options->pluck('value')->all();
    }

    /**
     * Validation messages worded with the admin's own label, so a visitor reads
     * "Please enter Student Name", never "The student_name field is required".
     *
     * @return array<string, string>
     */
    public function validationMessages(): array
    {
        $name  = $this->inputName();
        $label = $this->label;

        // A grid names the row that was missed, because "Satisfaction is
        // required" is no help when only one row of six is blank.
        $grid = [];

        if ($this->isGrid()) {
            foreach ($this->rows as $index => $row) {
                $grid["{$name}.{$index}.required"] = "Answer “{$row->label}” under “{$label}”.";
                $grid["{$name}.{$index}.in"]       = "Choose a listed option for “{$row->label}”.";
                $grid["{$name}.{$index}.*.in"]     = "Choose listed options for “{$row->label}”.";
            }
        }

        // "max" and "min" mean a length on text, a value on a number and a size
        // on a file. One message for all three told a visitor whose résumé was
        // too big that it was "too long".
        $isFile   = $this->control() === 'file';
        $isNumber = $this->field_type === FormFieldType::NUMBER;

        return $grid + [
            "{$name}.max"          => match (true) {
                $isFile   => "“{$label}” is larger than " . UploadLimit::label($this->maxFileKb()) . '.',
                $isNumber => "“{$label}” must be :max or less.",
                default   => "“{$label}” must be :max characters or fewer.",
            },
            "{$name}.min"          => $isNumber
                ? "“{$label}” must be :min or more."
                : "“{$label}” must be at least :min characters.",
            "{$name}.uploaded"     => "“{$label}” did not upload. Please choose the file again.",
            "{$name}.file"         => "“{$label}” did not upload. Please choose the file again.",
            "{$name}.*.uploaded"   => "A file in “{$label}” did not upload. Please choose it again.",
            "{$name}.*.file"       => "A file in “{$label}” did not upload. Please choose it again.",
            "{$name}.between"      => "Choose a value for “{$label}”.",
            "{$name}.integer"      => "“{$label}” must be a whole number.",
            "{$name}.after_or_equal" => "“{$label}” is earlier than this form allows.",
            "{$name}.before_or_equal" => "“{$label}” is later than this form allows.",
            "{$name}.required"     => "Please complete “{$label}”.",
            "{$name}.email"        => "“{$label}” must be a valid email address.",
            "{$name}.regex"        => "Enter a valid 10-digit mobile number for “{$label}”.",
            "{$name}.numeric"      => "“{$label}” must be a number.",
            "{$name}.date"         => "“{$label}” must be a valid date.",
            "{$name}.date_format"  => "“{$label}” must be a valid time.",
            "{$name}.in"           => "Choose one of the listed options for “{$label}”.",
            "{$name}.*.in"         => "Choose from the listed options for “{$label}”.",
            "{$name}.mimes"        => "“{$label}” must be a " . strtoupper(implode(', ', $this->allowedExtensions())) . ' file.',
            "{$name}.*.mimes"      => "“{$label}” must be a " . strtoupper(implode(', ', $this->allowedExtensions())) . ' file.',
            "{$name}.*.max"        => "Each file in “{$label}” must be " . UploadLimit::label($this->maxFileKb()) . ' or smaller.',
        ];
    }

    /* ================================ KEYS ================================= */

    /**
     * A storage key from a label: "Enter Your Full Name" → "enter_your_full_name".
     *
     * Punctuation is stripped, and that matters more than it looks. Str::snake
     * leaves it in place, so a perfectly ordinary question — "How satisfied are
     * you?" — produced the key `how_satisfied_are_you?`. A "?" survives as an
     * input name but a "." would be read by the validator as nesting, and the
     * field's rules would then be attached to something that does not exist.
     * Keys are restricted to letters, digits and underscores for that reason.
     *
     * Falls back to "field" for a label that reduces to nothing, so a question
     * written in a non-Latin script still gets a usable key rather than an empty
     * one that would collide with the next.
     */
    public static function keyFrom(?string $label): string
    {
        // Lower-cased BEFORE snake-casing: Str::snake splits at every capital,
        // so "Email ID" became email_i_d and "CV" became c_v. Only new
        // questions are keyed this way — a saved one keeps its key.
        $key = Str::snake(Str::lower(Str::ascii((string) $label)));
        $key = preg_replace('/[^a-z0-9_]+/', '_', $key);
        $key = trim((string) preg_replace('/_+/', '_', $key), '_');

        return $key !== '' ? $key : 'field';
    }
}
