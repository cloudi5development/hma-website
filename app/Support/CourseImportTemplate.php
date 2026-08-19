<?php

namespace App\Support;

use App\Models\Category;
use App\Models\Course;

/**
 * The shape of the bulk-course spreadsheet, in one place.
 *
 * The downloadable template, the Instructions sheet, the importer's header check
 * and the error report all read their columns from here, so the file an admin
 * downloads can never drift from the file the importer expects.
 *
 * Deliberately absent: image, brochure, category_id, department_id and every
 * internal id. Artwork and PDFs stay with the manual Course Edit screen, and the
 * department is derived from the category (see CourseBulkImportService), so an
 * admin never types a database key.
 *
 * Also absent, since 2026-08-19: batch_start and mode. Both moved onto the batch
 * — a course has as many start dates and modes as it has intakes — and batches
 * are managed at Courses → Schedule, which this file has never covered.
 */
class CourseImportTemplate
{
    /**
     * Every column, in the order they appear on the sheet.
     *
     * `required` mirrors the manual Course form: those are the fields
     * CourseRequest refuses to save without. `help` is what the Instructions
     * sheet prints beside the column name.
     */
    public const COLUMNS = [
        'course_name' => [
            'required' => true,
            'help'     => 'The course title. Up to 180 characters.',
        ],
        'category' => [
            'required' => true,
            'help'     => 'An existing category NAME (or its slug). The department is worked out from it — do not enter a department.',
        ],
        'slug' => [
            'required' => false,
            'help'     => 'Leave blank to generate it from the course name. Letters, numbers, dashes and underscores only. Must be unique.',
        ],
        // No batch_start and no mode: both belong to the batch, and batches are
        // not part of this file at all — they are managed at Courses → Schedule.
        'duration' => [
            'required' => true,
            'help'     => 'Free text, as on the course form: "30 Days", "6 Months", "12 Weeks". Up to 60 characters.',
        ],
        'skill_level' => [
            'required' => true,
            'help'     => 'One of: ' . self::LEVELS_HELP,
        ],
        'short_description' => [
            'required' => false,
            'help'     => 'Card summary. Up to 400 characters.',
        ],
        'full_description' => [
            'required' => false,
            'help'     => 'Main body copy. Up to 5000 characters.',
        ],
        'course_overview' => [
            'required' => false,
            'help'     => 'Overview block on the course page. Up to 5000 characters.',
        ],
        'learning_outcomes' => [
            'required' => false,
            'help'     => 'ONE PER LINE. Press Alt+Enter inside the cell to start a new line. Up to 5000 characters.',
        ],
        'prerequisites' => [
            'required' => false,
            'help'     => 'Up to 2000 characters.',
        ],
        'certification_details' => [
            'required' => false,
            'help'     => 'Up to 2000 characters.',
        ],
        'audience' => [
            'required' => false,
            'help'     => 'Who the course is for, e.g. "Freshers, working professionals". ONE PER LINE also works — press Alt+Enter inside the cell. Up to 2000 characters. May be left blank.',
        ],
        'status' => [
            'required' => false,
            'help'     => 'Active or Inactive. Yes/No and 1/0 also work. Blank means Active.',
        ],
        'order' => [
            'required' => false,
            'help'     => 'Whole number, lowest shows first. Blank means 0.',
        ],
        'rating' => [
            'required' => false,
            'help'     => 'Between 0 and 5, one decimal place, e.g. 4.5. Blank means 4.5.',
        ],
        'show_in_popular' => [
            'required' => false,
            'help'     => 'Yes or No. Blank means No.',
        ],
        'show_in_continue_learning' => [
            'required' => false,
            'help'     => 'Yes or No. Blank means No.',
        ],
        'featured' => [
            'required' => false,
            'help'     => 'Yes or No. Blank means No.',
        ],
        'faq_1_question' => ['required' => false, 'help' => 'FAQ 1 question. Leave the pair blank to skip it.'],
        'faq_1_answer'   => ['required' => false, 'help' => 'FAQ 1 answer. Required if the question is filled in.'],
        'faq_2_question' => ['required' => false, 'help' => 'FAQ 2 question.'],
        'faq_2_answer'   => ['required' => false, 'help' => 'FAQ 2 answer.'],
        'faq_3_question' => ['required' => false, 'help' => 'FAQ 3 question.'],
        'faq_3_answer'   => ['required' => false, 'help' => 'FAQ 3 answer.'],
        'faq_4_question' => ['required' => false, 'help' => 'FAQ 4 question.'],
        'faq_4_answer'   => ['required' => false, 'help' => 'FAQ 4 answer.'],
        'faq_5_question' => ['required' => false, 'help' => 'FAQ 5 question.'],
        'faq_5_answer'   => ['required' => false, 'help' => 'FAQ 5 answer.'],
        'meta_title'       => ['required' => false, 'help' => 'SEO title. Up to 180 characters. Left blank, nothing is invented for you.'],
        'meta_keywords'    => ['required' => false, 'help' => 'Comma separated. Up to 255 characters.'],
        'meta_description' => ['required' => false, 'help' => 'Up to 300 characters.'],
    ];

    private const LEVELS_HELP = 'Beginner, Intermediate, Advanced';

    /**
     * Columns the file MUST carry for the importer to run at all.
     *
     * Only the ones the manual form requires. Everything else may be left out of
     * the sheet entirely — an admin who only wants name/category/duration should
     * not have to keep 25 empty columns around.
     */
    public static function requiredHeadings(): array
    {
        return array_keys(array_filter(
            self::COLUMNS,
            fn (array $spec) => $spec['required'],
        ));
    }

    /** Every column key, in sheet order. */
    public static function headings(): array
    {
        return array_keys(self::COLUMNS);
    }

    /** What a "yes" looks like in a boolean column, lower-cased. */
    private const TRUTHY = ['1', 'yes', 'y', 'true', 'active', 'show', 'enabled', 'on'];

    /** ...and a "no". */
    private const FALSY = ['0', 'no', 'n', 'false', 'inactive', 'hidden', 'disabled', 'off'];

    /**
     * Read a Yes/No cell.
     *
     * Returns null when the cell says something that is neither — the caller then
     * reports it as a row error rather than quietly treating "maybe" as No, which
     * would publish or hide a course the admin did not ask for.
     */
    public static function toBool(mixed $value, ?bool $blankDefault = false): ?bool
    {
        if (self::isBlank($value)) {
            return $blankDefault;
        }

        if (is_bool($value)) {
            return $value;
        }

        $needle = strtolower(trim((string) $value));

        if (in_array($needle, self::TRUTHY, true)) {
            return true;
        }

        if (in_array($needle, self::FALSY, true)) {
            return false;
        }

        return null;
    }

    /**
     * Trim a text cell, keeping the line breaks inside it.
     *
     * learning_outcomes is "one per line" on the course form, so the newlines an
     * admin typed with Alt+Enter are content, not whitespace. Only the ends are
     * trimmed, and \r\n is flattened to \n so Windows-authored files match what
     * the form itself stores.
     */
    public static function toText(mixed $value): ?string
    {
        if (self::isBlank($value)) {
            return null;
        }

        $text = str_replace(["\r\n", "\r"], "\n", (string) $value);

        // Trim spaces/tabs from each end without eating the interior newlines.
        $text = trim($text);

        return $text === '' ? null : $text;
    }

    /** True for null, '' and a cell holding only whitespace. */
    public static function isBlank(mixed $value): bool
    {
        if ($value === null || is_bool($value)) {
            return $value === null;
        }

        return trim((string) $value) === '';
    }

    /** The allowed values sheet — human-readable, never ids. */
    public static function allowedValues(): array
    {
        return [
            'skill_level' => Course::SKILL_LEVELS,
            'status'      => ['Active', 'Inactive'],
            'yes_no'      => ['Yes', 'No'],
        ];
    }

    /**
     * One filled-in example row, so the template is not a blank grid.
     *
     * Keyed by column so it cannot fall out of step with headings() when a
     * column is added.
     */
    public static function sampleRow(?string $category = null): array
    {
        $sample = [
            'course_name'               => 'Full Stack Development',
            'category'                  => $category ?? 'IT & Software',
            'slug'                      => '',
            'duration'                  => '6 Months',
            'skill_level'               => 'Beginner',
            'short_description'         => 'Build complete web applications end to end.',
            'full_description'          => 'A practical, project-led programme covering the front end, the back end and everything that joins them.',
            'course_overview'           => 'Twelve guided projects, weekly mentor reviews and a capstone build.',
            'learning_outcomes'         => "Master core concepts\nBuild real-world projects\nPrepare for interviews\nEarn an industry-recognised certificate",
            'prerequisites'             => 'Comfortable with basic computer use. No prior coding required.',
            'certification_details'     => 'HireMinds Academy certificate on completion of the capstone project.',
            'audience'                  => 'Final-year students, freshers and working professionals moving into web development.',
            'status'                    => 'Active',
            'order'                     => 1,
            'rating'                    => 4.5,
            'show_in_popular'           => 'Yes',
            'show_in_continue_learning' => 'Yes',
            'featured'                  => 'No',
            'faq_1_question'            => 'Do I need a laptop?',
            'faq_1_answer'              => 'Yes — any machine from the last five years is fine.',
            'faq_2_question'            => 'Is placement support included?',
            'faq_2_answer'              => 'Yes, from resume review through to mock interviews.',
            'meta_title'                => 'Full Stack Development Course | HireMinds Academy',
            'meta_keywords'             => 'full stack, web development, training',
            'meta_description'          => 'Learn full stack web development with hands-on projects and placement support.',
        ];

        // Fill the columns the sample leaves out, in sheet order.
        return array_map(
            fn (string $key) => $sample[$key] ?? '',
            self::headings(),
        );
    }

    /**
     * Columns whose value must come from master data, and where that list lives.
     *
     * 'sheet' means the list is long or contains characters Excel's inline list
     * syntax cannot carry (a comma splits it), so it is written to the hidden
     * helper sheet and referenced by range. 'inline' lists are short, fixed and
     * safe to embed straight into the validation formula.
     *
     * Anything named here gets a dropdown on the Courses sheet AND is checked
     * against the same list on import — one definition drives both, so a value
     * the sheet offers can never be a value the importer rejects.
     */
    public const MASTER_COLUMNS = [
        'category'                  => 'sheet',
        'skill_level'               => 'inline',
        'status'                    => 'inline',
        'show_in_popular'           => 'inline',
        'show_in_continue_learning' => 'inline',
        'featured'                  => 'inline',
    ];

    /**
     * The actual values behind each dropdown, for this installation, right now.
     *
     * Categories are read from the database — a renamed category changes the
     * dropdown on the next download, and the import validates against the same
     * query, so the two cannot drift.
     *
     * @return array<string, array<int, string>>
     */
    public static function dropdownLists(): array
    {
        $yesNo = ['Yes', 'No'];

        return [
            'category'                  => Category::query()->orderBy('name')->pluck('name')->unique()->values()->all(),
            'skill_level'               => Course::SKILL_LEVELS,
            'status'                    => ['Active', 'Inactive'],
            'show_in_popular'           => $yesNo,
            'show_in_continue_learning' => $yesNo,
            'featured'                  => $yesNo,
        ];
    }

    /** The spreadsheet column letter for a heading, e.g. 'category' => 'B'. */
    public static function columnLetter(string $heading): ?string
    {
        $index = array_search($heading, self::headings(), true);

        return $index === false
            ? null
            : \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index + 1);
    }

    /** Columns holding a number, for the template's numeric validation. */
    public const NUMERIC_COLUMNS = ['order', 'rating'];
}
