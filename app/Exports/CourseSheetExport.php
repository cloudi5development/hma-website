<?php

namespace App\Exports;

use App\Models\Course;
use App\Support\CourseImportTemplate;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;

/**
 * The "Courses" grid — the one sheet that is both downloaded and uploaded.
 *
 * It serves two jobs from one definition, because they have to stay identical:
 *
 *   empty  → the blank template (headers + dropdowns, nothing else)
 *   filled → an export of existing courses, ready to edit and upload back
 *
 * Round trip is the whole point: a course exported here, changed and uploaded
 * again must update rather than duplicate, so the `slug` column is written out
 * as the row's identity and must not be edited by hand.
 *
 * Headings sit in row 1 and nowhere else — no title block, no notes, no spacer.
 * Everything explanatory lives on the Instructions sheet.
 */
class CourseSheetExport implements FromArray, WithHeadings, WithTitle, WithEvents, WithStrictNullComparison, ShouldAutoSize
{
    /** Rows the dropdowns are applied to beyond the last data row. */
    private const SPARE_ROWS = 500;

    public function __construct(private Collection $courses)
    {
    }

    /** The blank template: headers and dropdowns, no data. */
    public static function template(): self
    {
        return new self(collect());
    }

    /** Every course, oldest first, with the FAQs the sheet round-trips. */
    public static function all(): self
    {
        return new self(
            Course::with(['category:id,name', 'faqs'])->orderBy('id')->get(),
        );
    }

    public function title(): string
    {
        return 'Courses';
    }

    public function headings(): array
    {
        return CourseImportTemplate::headings();
    }

    public function array(): array
    {
        return $this->courses->map(fn (Course $course) => $this->row($course))->all();
    }

    /**
     * One course as a sheet row, in heading order.
     *
     * Values are written the way the importer reads them back — dates as
     * DD-MM-YYYY, flags as Yes/No, status as Active/Inactive — so an untouched
     * export re-imports to exactly the same course.
     */
    private function row(Course $course): array
    {
        $faqs = $course->faqs->values();

        $cells = [
            'course_name'               => $course->name,
            'category'                  => $course->category?->name,
            'slug'                      => $course->slug,
            'duration'                  => $course->duration,
            'skill_level'               => $course->skill_level,
            'short_description'         => $course->short_description,
            'full_description'          => $course->full_description,
            'course_overview'           => $course->overview,
            'learning_outcomes'         => $course->learning_outcomes,
            'prerequisites'             => $course->prerequisites,
            'certification_details'     => $course->certification,
            'audience'                  => $course->audience,
            'status'                    => $course->is_active ? 'Active' : 'Inactive',
            'order'                     => $course->sort_order,
            'rating'                    => $course->rating === null ? null : (float) $course->rating,
            'show_in_popular'           => $course->is_popular ? 'Yes' : 'No',
            'show_in_continue_learning' => $course->is_continue_learning ? 'Yes' : 'No',
            'featured'                  => $course->is_featured ? 'Yes' : 'No',
            'meta_title'                => $course->meta_title,
            'meta_keywords'             => $course->meta_keywords,
            'meta_description'          => $course->meta_description,
        ];

        for ($i = 1; $i <= Course::MAX_FAQS; $i++) {
            $faq = $faqs[$i - 1] ?? null;
            $cells["faq_{$i}_question"] = $faq?->question;
            $cells["faq_{$i}_answer"]   = $faq?->answer;
        }

        // Ordered by the heading list, so adding a column cannot shift the data
        // out of line with its header.
        return array_map(
            fn (string $heading) => $cells[$heading] ?? null,
            CourseImportTemplate::headings(),
        );
    }

    /**
     * Attach the master-data dropdowns and keep the header row in view.
     *
     * Each list lives on the hidden `_master` sheet and is referenced by range
     * rather than embedded in the formula: Excel caps an inline list at 255
     * characters and treats a comma as the separator, so a long category list —
     * or a single category with a comma in its name — would silently break one.
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Headers stay on screen while an admin scrolls a long export.
                $sheet->freezePane('A2');
                $sheet->getStyle('A1:' . $sheet->getHighestColumn() . '1')->getFont()->setBold(true);

                $lastRow = max($this->courses->count() + 1, 1) + self::SPARE_ROWS;

                // ---- Master-data dropdowns --------------------------------
                foreach (array_keys(CourseImportTemplate::MASTER_COLUMNS) as $column) {
                    $letter = CourseImportTemplate::columnLetter($column);
                    $source = CourseMasterDataSheet::sourceFor($column);

                    if (! $letter || ! $source) {
                        continue;
                    }

                    $sheet->setDataValidation(
                        "{$letter}2:{$letter}{$lastRow}",
                        $this->listValidation($source, $column),
                    );
                }

                // The sheet carries no date column any more — batch dates moved
                // to Courses → Schedule, which is not part of this file.

                // ---- Numbers -----------------------------------------------
                foreach ([
                    'order'  => [DataValidation::TYPE_WHOLE, '0', '9999', 'A whole number, 0 or more. Lowest shows first.'],
                    'rating' => [DataValidation::TYPE_DECIMAL, '0', '5', 'A number between 0 and 5, e.g. 4.5.'],
                ] as $column => [$type, $min, $max, $hint]) {
                    $letter = CourseImportTemplate::columnLetter($column);

                    if (! $letter) {
                        continue;
                    }

                    $sheet->setDataValidation(
                        "{$letter}2:{$letter}{$lastRow}",
                        $this->numberValidation($column, $type, $min, $max, $hint),
                    );
                }
            },
        ];
    }

    /**
     * A drop-down over one of the master lists.
     *
     * The source is normally the values themselves, inline — see
     * CourseMasterDataSheet for why anything else proved fragile.
     *
     * setShowDropDown(TRUE) is what puts the arrow in the cell, despite the name
     * reading the other way round. PhpSpreadsheet writes the OOXML attribute
     * inverted — `showDropDown` in the file means "suppress the arrow", so the
     * writer emits `!getShowDropDown()`. Passing false here produced a validated
     * cell with no picker on it, which is the one thing this must not do.
     *
     * Blanks are allowed because most of these columns are optional and fall
     * back to a documented default.
     */
    private function listValidation(string $source, string $column): DataValidation
    {
        $validation = $this->baseValidation(
            ucfirst(str_replace('_', ' ', $column)),
            'Pick a value from the list.',
            'Not a valid ' . str_replace('_', ' ', $column),
            'Choose one of the listed values. The importer rejects anything else.',
        );

        $validation->setType(DataValidation::TYPE_LIST);
        $validation->setFormula1($source);

        // A list has nothing to be "between". PhpSpreadsheet defaults every rule
        // to OPERATOR_BETWEEN and writes it, which leaves a list validation
        // carrying an attribute Excel does not expect on one.
        $validation->setOperator('');

        return $validation;
    }

    /** A whole-number or decimal rule, for `order` and `rating`. */
    private function numberValidation(string $column, string $type, string $min, string $max, string $hint): DataValidation
    {
        $validation = $this->baseValidation(
            ucfirst($column),
            $hint,
            'Not a valid ' . $column,
            $hint,
        );

        $validation->setType($type);
        $validation->setOperator(DataValidation::OPERATOR_BETWEEN);
        $validation->setFormula1($min);
        $validation->setFormula2($max);

        return $validation;
    }

    /** The settings every rule on this sheet shares. */
    private function baseValidation(string $promptTitle, string $prompt, string $errorTitle, string $error): DataValidation
    {
        $validation = new DataValidation();

        $validation->setErrorStyle(DataValidation::STYLE_STOP);
        $validation->setAllowBlank(true);
        $validation->setShowDropDown(true);
        $validation->setShowInputMessage(true);
        $validation->setShowErrorMessage(true);
        $validation->setPromptTitle($promptTitle);
        $validation->setPrompt($prompt);
        $validation->setErrorTitle($errorTitle);
        $validation->setError($error);

        return $validation;
    }
}
