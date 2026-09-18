<?php

namespace App\Support;

use App\Models\FormField;

/**
 * What a Form Builder bulk-upload spreadsheet looks like.
 *
 * One place for the columns, the accepted values and the sample rows, so the
 * template an admin downloads and the reader that checks it cannot drift apart.
 *
 * IT IS NOT A SECOND FIELD-TYPE SYSTEM. Every type the sheet accepts is read
 * straight off App\Support\FormFieldType: the dropdown in the Type column is the
 * registry's own labels, and the snake_case spellings ("multiple_choice",
 * "drop_down") are those labels normalised by the same FormField::keyFrom()
 * that turns a question into its storage key. Add a type to the registry and
 * the sheet accepts it; rename a label and both spellings follow.
 */
class FormImportSheet
{
    /* ============================ THE TWO SHEETS ===========================
       One template per form type, because the two ask different things:

         Standard form   Order | Label    | Type | Required | Placeholder | Description | Options
         Quiz / MCQ      Order | Question | Type | Required | Options | Correct Answer

       A quiz's rows ARE questions, so the column says so; a placeholder is the
       grey hint inside a text box, which a multiple choice question does not
       have; a quiz shows no description under its questions, so it has nowhere
       to put one; and a correct answer means nothing on a form that is not
       marked.

       Neither has Page or Section. Pages and sections are made by hand on the
       builder, and a sheet fills the section its Bulk Upload button sits in.

       The READER is more forgiving than either template: it understands every
       column below under either heading, so a sheet made from the other
       template, or an older download, is still read. */

    /** The sheet name each template writes. The reader takes either, or else the first sheet. */
    public const SHEET = 'Form Fields';

    public const QUIZ_SHEET = 'Quiz Questions';

    /** Every column the reader understands, with its standard-form heading. */
    public const COLUMNS = [
        'order'          => 'Order',
        'label'          => 'Label',
        'type'           => 'Type',
        'required'       => 'Required',
        'placeholder'    => 'Placeholder',
        'description'    => 'Description',
        'options'        => 'Options',
        'correct_answer' => 'Correct Answer',
    ];

    /** Headings that name the same column under another word. */
    private const HEADING_ALIASES = ['question' => 'label'];

    /** A sheet without these cannot describe a question at all. The rest may be absent. */
    public const REQUIRED_COLUMNS = ['order', 'label', 'type', 'required'];

    /** Column key => heading, in the order this form type's template writes them. */
    public static function columns(bool $quiz): array
    {
        return $quiz
            ? ['order' => 'Order', 'label' => 'Question', 'type' => 'Type', 'required' => 'Required',
               'options' => 'Options', 'correct_answer' => 'Correct Answer']
            : ['order' => 'Order', 'label' => 'Label', 'type' => 'Type', 'required' => 'Required',
               'placeholder' => 'Placeholder', 'description' => 'Description', 'options' => 'Options'];
    }

    /** What a column is called on this form type's template — "Question" on a quiz. */
    public static function heading(string $key, bool $quiz): string
    {
        return self::columns($quiz)[$key] ?? self::COLUMNS[$key];
    }

    public static function sheetName(bool $quiz): string
    {
        return $quiz ? self::QUIZ_SHEET : self::SHEET;
    }

    public const OPTION_SEPARATOR = '|';

    /*
     * No ceiling on rows, file size, or how long a cell may be. The only size
     * limit left on an upload is the server's own (upload_max_filesize and
     * post_max_size in php.ini), which PHP applies before this code runs.
     */

    /* =============================== HEADINGS ============================== */

    /** The column a heading names, forgiving case and spacing: "CORRECT  answer" works. */
    public static function columnFor(mixed $heading): ?string
    {
        $key = self::normalise($heading);
        $key = self::HEADING_ALIASES[$key] ?? $key;

        return array_key_exists($key, self::COLUMNS) ? $key : null;
    }

    /**
     * A cell reduced to the snake_case shape its column and type names take.
     *
     * Lower-cased FIRST. FormField::keyFrom goes through Str::snake, which reads
     * every capital as the start of a new word — "EMAIL" came out as
     * "e_m_a_i_l" and matched nothing, and capitals are exactly what people
     * type into a spreadsheet.
     */
    private static function normalise(mixed $value): string
    {
        return FormField::keyFrom(mb_strtolower(trim((string) $value)));
    }

    /* ================================ TYPES ================================ */

    /**
     * The internal type key a cell names, or null.
     *
     * Accepts the label the dropdown offers ("Multiple choice"), its snake_case
     * spelling ("multiple_choice") and the stored key itself ("radio").
     */
    public static function typeFor(mixed $value): ?string
    {
        $wanted = self::normalise($value);

        return $wanted === 'field' ? null : (self::typeMap()[$wanted] ?? null);
    }

    /** The labels, in the registry's own order, for the Type dropdown. */
    public static function typeLabels(): array
    {
        return array_values(array_map(fn ($spec) => $spec['label'], FormFieldType::TYPES));
    }

    /** snake_case spelling of each type — what the spec and the Field Types sheet call it. */
    public static function typeAlias(string $type): string
    {
        return self::normalise(FormFieldType::label($type));
    }

    private static ?array $typeMap = null;

    private static function typeMap(): array
    {
        if (self::$typeMap !== null) {
            return self::$typeMap;
        }

        $map = [];

        foreach (FormFieldType::TYPES as $key => $spec) {
            $map[self::normalise($spec['label'])] = $key;
            $map[$key]                            = $key;
        }

        return self::$typeMap = $map;
    }

    /* =============================== OPTIONS =============================== */

    /** "Male | Female | Other" → ["Male", "Female", "Other"]. Blanks between bars are dropped. */
    public static function splitOptions(string $cell): array
    {
        return array_values(array_filter(
            array_map('trim', explode(self::OPTION_SEPARATOR, $cell)),
            fn ($option) => $option !== '',
        ));
    }

    /**
     * A grid's two lists, from "Rows: Product | Support ; Columns: Poor | Good".
     *
     * A grid needs rows AND columns and the sheet has one Options cell, so the
     * two are named inside it. Returns null when the cell is not in that shape.
     *
     * @return array{rows: array<int, string>, columns: array<int, string>}|null
     */
    public static function splitGrid(string $cell): ?array
    {
        $lists = ['rows' => null, 'columns' => null];

        foreach (explode(';', $cell) as $part) {
            if (! preg_match('/^\s*(rows?|columns?)\s*:(.*)$/is', $part, $m)) {
                if (trim($part) !== '') {
                    return null;
                }

                continue;
            }

            $lists[str_starts_with(strtolower($m[1]), 'row') ? 'rows' : 'columns'] = self::splitOptions($m[2]);
        }

        return $lists['rows'] === null || $lists['columns'] === null ? null : $lists;
    }

    /* =============================== SAMPLES =============================== */

    /**
     * The example rows the template ships with. Written as the admin would
     * write them — labels in the Type column, Yes/No in Required — so the
     * template demonstrates the dropdowns rather than the storage keys.
     */
    public static function sampleRows(bool $quiz): array
    {
        // In the template's own column order — see columns().
        return $quiz
            ? [
                [1, 'What is Python?', 'Multiple choice', 'Yes', 'Programming Language | Database | OS | Browser', 'Programming Language'],
                [2, 'Which symbol is used for comments?', 'Multiple choice', 'Yes', '# | // | <!-- --> | **', '#'],
                [3, 'Which is a valid variable name?', 'Multiple choice', 'Yes', 'my_name | 123name | my-name | class', 'my_name'],
            ]
            : [
                [1, 'Full Name', 'Short answer', 'Yes', 'Enter your name', '', ''],
                [2, 'Email', 'Email', 'Yes', 'Enter your email', '', ''],
                [3, 'Gender', 'Multiple choice', 'Yes', '', 'Select gender', 'Male | Female | Other'],
                [4, 'Technologies Known', 'Checkboxes', 'No', '', 'Select all applicable', 'PHP | Laravel | React'],
            ];
    }
}
