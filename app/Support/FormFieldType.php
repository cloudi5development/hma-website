<?php

namespace App\Support;

/**
 * Every field type the form builder offers, described in one place.
 *
 * This is the module's extensibility seam. Five things read this registry and
 * nothing else knows the list:
 *
 *   the builder's field-type dropdown   (which types can be picked, and its icons)
 *   the field config panel              (which settings that type shows)
 *   FormField::validationRules()        (how a submitted value is checked)
 *   the public renderer                 (which control is drawn)
 *   FormResponseValue                   (how a stored answer reads back)
 *
 * So adding a type — a signature, a country picker — is an entry here plus a
 * branch in the renderer partial. It is not a schema change and it is not a
 * rewrite of the module.
 *
 * Per-type keys:
 *   label       what the admin sees in the type menu
 *   group       the heading it sits under in that menu
 *   input       the HTML input `type`, for the types drawn as a plain <input>
 *   control     how the renderer draws it — the @switch in form-fields.blade.php
 *   options     'option' when it needs the options manager, 'grid' when it needs
 *               row and column managers, false when it needs neither
 *   multiple    true when a submitted value is a list rather than a scalar
 *   validations which validation settings the config panel offers
 *   icon        inline SVG path data for the type dropdown
 *
 * The type KEYS are storage, not display: they are written into
 * form_fields.field_type and into every response's snapshot, so renaming one
 * would strand existing data. Labels are free to change; keys are not.
 */
class FormFieldType
{
    public const SHORT_TEXT = 'short_text';
    public const LONG_TEXT  = 'long_text';
    public const EMAIL      = 'email';
    public const MOBILE     = 'mobile';
    public const NUMBER     = 'number';
    public const DROPDOWN   = 'dropdown';
    public const RADIO      = 'radio';
    public const CHECKBOX   = 'checkbox';
    public const YES_NO     = 'yes_no';
    public const FILE       = 'file';
    public const LINEAR_SCALE = 'linear_scale';
    public const RATING       = 'rating';
    public const MC_GRID      = 'mc_grid';
    public const TICK_GRID    = 'tick_grid';
    public const HIDDEN     = 'hidden';
    public const DATE       = 'date';
    public const TIME       = 'time';
    public const DATETIME   = 'datetime';

    public const TYPES = [
        /* ------------------------------- BASIC ------------------------------- */
        self::SHORT_TEXT => [
            'label' => 'Short answer', 'group' => 'Basic', 'control' => 'input', 'input' => 'text',
            'options' => false, 'multiple' => false, 'validations' => ['min_length', 'max_length'],
            'icon' => '<path d="M4 9h16M4 15h9"/>',
        ],
        self::LONG_TEXT => [
            'label' => 'Paragraph', 'group' => 'Basic', 'control' => 'textarea', 'input' => null,
            'options' => false, 'multiple' => false, 'validations' => ['min_length', 'max_length'],
            'icon' => '<path d="M4 6h16M4 10h16M4 14h16M4 18h10"/>',
        ],
        self::EMAIL => [
            'label' => 'Email', 'group' => 'Basic', 'control' => 'input', 'input' => 'email',
            'options' => false, 'multiple' => false, 'validations' => ['max_length'],
            'icon' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3.5 7 8.5 6 8.5-6"/>',
        ],
        self::MOBILE => [
            // No length settings: a mobile number is always exactly ten digits.
            'label' => 'Mobile number', 'group' => 'Basic', 'control' => 'input', 'input' => 'tel',
            'options' => false, 'multiple' => false, 'validations' => [],
            'icon' => '<rect x="7" y="2.5" width="10" height="19" rx="2.5"/><path d="M11 18.5h2"/>',
        ],
        self::NUMBER => [
            'label' => 'Number', 'group' => 'Basic', 'control' => 'input', 'input' => 'number',
            'options' => false, 'multiple' => false, 'validations' => ['min_value', 'max_value'],
            'icon' => '<path d="M9 4 7 20M17 4l-2 16M4.5 9h15M3.5 15h15"/>',
        ],

        /* ------------------------------- CHOICE ------------------------------ */
        self::RADIO => [
            'label' => 'Multiple choice', 'group' => 'Choice', 'control' => 'radio', 'input' => null,
            'options' => 'option', 'multiple' => false, 'validations' => [],
            'icon' => '<circle cx="12" cy="12" r="8.5"/><circle cx="12" cy="12" r="3.5" fill="currentColor" stroke="none"/>',
        ],
        self::CHECKBOX => [
            'label' => 'Checkboxes', 'group' => 'Choice', 'control' => 'checkbox', 'input' => null,
            'options' => 'option', 'multiple' => true, 'validations' => [],
            'icon' => '<rect x="3.5" y="3.5" width="17" height="17" rx="3"/><path d="m8 12.2 2.8 2.8L16 9.5"/>',
        ],
        self::DROPDOWN => [
            'label' => 'Drop-down', 'group' => 'Choice', 'control' => 'select', 'input' => null,
            'options' => 'option', 'multiple' => false, 'validations' => [],
            'icon' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m9 10.5 3 3 3-3"/>',
        ],
        // Yes/No carries options like any other choice field rather than being a
        // boolean somewhere: the admin can relabel them ("Agree"/"Decline")
        // without the module growing a special case.
        self::YES_NO => [
            'label' => 'Yes / No', 'group' => 'Choice', 'control' => 'radio', 'input' => null,
            'options' => 'option', 'multiple' => false, 'validations' => [],
            'icon' => '<path d="m4 12.5 3 3 5.5-6.5"/><path d="M14 8.5 20 15M20 8.5 14 15"/>',
        ],

        /* -------------------------------- OTHER ------------------------------ */
        self::FILE => [
            'label' => 'File upload', 'group' => 'Other', 'control' => 'file', 'input' => 'file',
            'options' => false, 'multiple' => false, 'validations' => ['file_types', 'max_file_size_kb'],
            'icon' => '<path d="M12 16V4m0 0 4 4m-4-4L8 8"/><path d="M4 15v3a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-3"/>',
        ],
        self::LINEAR_SCALE => [
            'label' => 'Linear scale', 'group' => 'Other', 'control' => 'scale', 'input' => null,
            'options' => false, 'multiple' => false, 'validations' => ['scale_min', 'scale_max', 'scale_min_label', 'scale_max_label'],
            'icon' => '<path d="M4 12h16"/><path d="M6 9.5v5M12 9.5v5M18 9.5v5"/>',
        ],
        self::RATING => [
            'label' => 'Rating', 'group' => 'Other', 'control' => 'rating', 'input' => null,
            'options' => false, 'multiple' => false, 'validations' => ['rating_count', 'rating_icon'],
            'icon' => '<path d="m12 4 2.4 4.9 5.4.8-3.9 3.8.9 5.4-4.8-2.5-4.8 2.5.9-5.4L4.2 9.7l5.4-.8Z"/>',
        ],
        self::MC_GRID => [
            'label' => 'Multiple-choice grid', 'group' => 'Other', 'control' => 'mc_grid', 'input' => null,
            'options' => 'grid', 'multiple' => true, 'validations' => [],
            'icon' => '<path d="M3.5 8.5h17M3.5 15.5h17M9.5 4v16"/><rect x="3.5" y="4" width="17" height="16" rx="2"/>',
        ],
        self::TICK_GRID => [
            'label' => 'Tick box grid', 'group' => 'Other', 'control' => 'tick_grid', 'input' => null,
            'options' => 'grid', 'multiple' => true, 'validations' => [],
            'icon' => '<rect x="3.5" y="4" width="17" height="16" rx="2"/><path d="M3.5 8.5h17M9.5 4v16"/><path d="m12.2 12.4 1.4 1.4 2.6-2.8"/>',
        ],
        self::HIDDEN => [
            'label' => 'Hidden field', 'group' => 'Other', 'control' => 'hidden', 'input' => 'hidden',
            'options' => false, 'multiple' => false, 'validations' => [],
            'icon' => '<path d="M3 3l18 18"/><path d="M10.6 10.7a2 2 0 0 0 2.8 2.8"/><path d="M9.4 5.4A9.5 9.5 0 0 1 12 5c6.4 0 10 7 10 7a17 17 0 0 1-2.6 3.6M6.2 7.2A16.7 16.7 0 0 0 2 12s3.6 7 10 7a9.4 9.4 0 0 0 4-.9"/>',
        ],

        /* ----------------------------- DATE & TIME --------------------------- */
        self::DATE => [
            'label' => 'Date', 'group' => 'Date & Time', 'control' => 'input', 'input' => 'date',
            'options' => false, 'multiple' => false, 'validations' => ['min_date', 'max_date'],
            'icon' => '<rect x="3.5" y="5" width="17" height="15" rx="2"/><path d="M3.5 9.5h17M8 3v4M16 3v4"/>',
        ],
        self::TIME => [
            'label' => 'Time', 'group' => 'Date & Time', 'control' => 'input', 'input' => 'time',
            'options' => false, 'multiple' => false, 'validations' => ['min_time', 'max_time'],
            'icon' => '<circle cx="12" cy="12" r="8.5"/><path d="M12 7.5V12l3 1.8"/>',
        ],
        self::DATETIME => [
            'label' => 'Date & time', 'group' => 'Date & Time', 'control' => 'input', 'input' => 'datetime-local',
            'options' => false, 'multiple' => false, 'validations' => [],
            'icon' => '<rect x="3.5" y="5" width="17" height="15" rx="2"/><path d="M3.5 9.5h17M8 3v4M16 3v4"/><path d="M12 12.5V15l1.8 1"/>',
        ],
    ];

    /** The order the groups appear in the type dropdown. */
    public const GROUP_ORDER = ['Basic', 'Choice', 'Other', 'Date & Time'];

    /**
     * Extensions a file field may accept, and nothing outside this list is ever
     * allowed through — see FormField::validationRules().
     *
     * Deliberately no php, phtml, exe, sh, bat, js, html or svg. An SVG is a
     * document that can carry script, so it is not an image as far as an upload
     * an admin will later open in a browser tab is concerned.
     */
    public const ALLOWED_FILE_EXTENSIONS = [
        'pdf', 'doc', 'docx', 'txt', 'rtf', 'odt',
        'xls', 'xlsx', 'csv', 'ppt', 'pptx',
        'jpg', 'jpeg', 'png', 'webp', 'gif',
        'zip',
    ];

    /** What a file field accepts when the admin names nothing. */
    public const DEFAULT_FILE_EXTENSIONS = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];

    /** The two options a Yes/No field is created with. The admin may relabel them. */
    public const YES_NO_OPTIONS = [
        ['label' => 'Yes', 'value' => 'Yes'],
        ['label' => 'No',  'value' => 'No'],
    ];

    /**
     * Linear scale defaults, used only when the admin sets nothing. The range is
     * configurable — 1-5 is a starting point, never a hardcoded scale.
     */
    public const SCALE_DEFAULT_MIN = 1;
    public const SCALE_DEFAULT_MAX = 5;

    /** Rating defaults and the shapes a rating may be drawn with. No upper count. */
    public const RATING_DEFAULT_COUNT = 5;
    public const RATING_ICONS = ['star' => 'Stars', 'heart' => 'Hearts', 'circle' => 'Circles'];

    public static function keys(): array
    {
        return array_keys(self::TYPES);
    }

    public static function exists(?string $type): bool
    {
        return $type !== null && array_key_exists($type, self::TYPES);
    }

    /** One type's spec, or the Short answer spec for anything unrecognised. */
    public static function spec(?string $type): array
    {
        return self::TYPES[$type] ?? self::TYPES[self::SHORT_TEXT];
    }

    public static function label(?string $type): string
    {
        return self::spec($type)['label'];
    }

    public static function control(?string $type): string
    {
        return self::spec($type)['control'];
    }

    public static function icon(?string $type): string
    {
        return self::spec($type)['icon'];
    }

    /** True for a type that needs the plain options manager. */
    public static function needsOptions(?string $type): bool
    {
        return self::spec($type)['options'] === 'option';
    }

    /** True for a grid — two managers, rows and columns. */
    public static function needsGrid(?string $type): bool
    {
        return self::spec($type)['options'] === 'grid';
    }

    /** True for either kind, i.e. the field carries choices of some sort. */
    public static function hasChoices(?string $type): bool
    {
        return self::spec($type)['options'] !== false;
    }

    public static function isMultiple(?string $type): bool
    {
        return self::spec($type)['multiple'];
    }

    public static function validations(?string $type): array
    {
        return self::spec($type)['validations'];
    }

    /**
     * Types grouped for the dropdown, in GROUP_ORDER:
     * ['Basic' => ['short_text' => ['label' => …, 'icon' => …]], …]
     */
    public static function grouped(): array
    {
        $grouped = [];

        foreach (self::GROUP_ORDER as $group) {
            foreach (self::TYPES as $key => $spec) {
                if ($spec['group'] === $group) {
                    $grouped[$group][$key] = ['label' => $spec['label'], 'icon' => $spec['icon']];
                }
            }
        }

        return $grouped;
    }

    /** The registry as the builder's JavaScript needs it, to show/hide panels. */
    public static function forJavascript(): array
    {
        return collect(self::TYPES)->map(fn (array $spec) => [
            'label'       => $spec['label'],
            'options'     => $spec['options'],
            'multiple'    => $spec['multiple'],
            'validations' => $spec['validations'],
            'control'     => $spec['control'],
            'icon'        => $spec['icon'],
        ])->all();
    }
}
