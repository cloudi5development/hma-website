<?php

namespace App\Exports;

use App\Models\Category;
use App\Models\Course;
use App\Support\CourseImportTemplate;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * The course workbook — downloaded empty as a template, or filled as an export
 * of what is already in the database. The same file either way, so an export can
 * be edited and uploaded straight back.
 *
 * Sheets:
 *   Courses         the grid. Headings in row 1, data from row 2. Nothing else.
 *   Instructions    what each column wants
 *   Allowed Values  the categories and skill levels this installation accepts today
 *   _master         hidden; the source ranges behind the Courses dropdowns
 *
 * Only the Courses sheet is read on import (see CourseRowsImport::sheets), so the
 * three reference sheets can never be mistaken for course rows.
 */
class CourseTemplateExport implements WithMultipleSheets
{
    use Exportable;

    public function __construct(private ?Collection $courses = null)
    {
    }

    /** Headers and dropdowns, no rows. */
    public static function template(): self
    {
        return new self(collect());
    }

    /** Every existing course, ready to edit and re-upload. */
    public static function withData(): self
    {
        return new self(null);
    }

    public function sheets(): array
    {
        // Rebuilt per download so a category added a minute ago is in the list.
        CourseMasterDataSheet::flush();

        $sheets = [
            $this->courses === null ? CourseSheetExport::all() : new CourseSheetExport($this->courses),
            new CourseTemplateInstructionsSheet(),
            new CourseTemplateAllowedValuesSheet(),
        ];

        // Only carried when a list is too long to sit inside the validation
        // itself. Normally every dropdown is inline and this sheet is absent —
        // one less thing for Excel to resolve.
        if (CourseMasterDataSheet::needsSheet()) {
            $sheets[] = new CourseMasterDataSheet();
        }

        return $sheets;
    }
}

/**
 * Sheet 2 — what every column wants.
 *
 * One header row, in row 1, and nothing that looks like a second one further
 * down: the sheet used to restate COLUMN / REQUIRED? / NOTES half way through,
 * which reads as a second header and breaks any tool scanning for one.
 */
class CourseTemplateInstructionsSheet implements FromArray, WithHeadings, WithTitle, ShouldAutoSize
{
    public function title(): string
    {
        return 'Instructions';
    }

    public function headings(): array
    {
        return ['Column', 'Required?', 'What to put in it'];
    }

    public function array(): array
    {
        $rows = [];

        // Every column first, so the grid's own documentation is the top of the
        // sheet and lines up one-to-one with the Courses headings.
        foreach (CourseImportTemplate::COLUMNS as $column => $spec) {
            $rows[] = [$column, $spec['required'] ? 'Required' : 'Optional', $spec['help']];
        }

        // General notes follow, in the same three columns — no new header row.
        foreach ([
            ['How to use this file', '', 'Fill in the "Courses" sheet, one row per course, then upload it at Admin → Courses → Bulk Upload. You see a preview before anything is saved.'],
            ['Create or update', '', 'The "slug" column is the identity of a row. A slug that already exists UPDATES that course; a new slug CREATES one. Leave slug blank on a new course and it is built from the course name.'],
            ['Editing existing courses', '', 'Use Export Courses to download what is already there, edit the rows, and upload the same file back. Do not change the slug of a row you mean to update.'],
            ['Images and brochures', '', 'NOT part of this file. Add them from Courses → Edit after importing. An import never changes a course image or brochure.'],
            ['Batches / schedules', '', 'NOT part of this file. Batch start dates, end dates and the training Mode are managed at Courses → Schedule — the website reads them off the soonest upcoming batch.'],
            ['Yes / No columns', '', 'Yes, No, Y, N, 1, 0, True, False, Active and Inactive are all understood. Anything else is reported as an error rather than guessed at.'],
            ['FAQ rules', '', 'Up to ' . Course::MAX_FAQS . ' per course. Leave both cells of a pair blank to skip it. A question without an answer (or the reverse) is an error. On update, the FAQ columns replace that course\'s existing FAQs.'],
            ['Learning outcomes', '', 'One outcome per line. Press Alt+Enter inside the cell to add a line. Line breaks are kept exactly as typed.'],
            ['Category', '', 'Pick from the dropdown. The department follows from the category automatically — there is no department column.'],
        ] as $note) {
            $rows[] = $note;
        }

        return $rows;
    }
}

/** Sheet 3 — what this installation will actually accept today. */
class CourseTemplateAllowedValuesSheet implements FromArray, WithHeadings, WithTitle, ShouldAutoSize
{
    public function title(): string
    {
        return 'Allowed Values';
    }

    public function headings(): array
    {
        return ['Column', 'Allowed value', 'Notes'];
    }

    public function array(): array
    {
        $rows = [];

        // Categories, with the department they belong to shown for orientation
        // only — the importer derives it, the admin never types it.
        $categories = Category::query()
            ->with('department:id,name')
            ->orderBy('department_id')->orderBy('name')
            ->get(['id', 'name', 'slug', 'department_id', 'is_active']);

        foreach ($categories as $category) {
            $rows[] = [
                'category',
                $category->name,
                trim('Department: ' . ($category->department?->name ?? '—')
                    . ' · slug: ' . $category->slug
                    . ($category->is_active ? '' : ' · (category currently hidden)')),
            ];
        }

        if ($categories->isEmpty()) {
            $rows[] = ['category', '(none yet)', 'Create a category first, under Courses → Categories.'];
        }

        foreach (Course::SKILL_LEVELS as $level) {
            $rows[] = ['skill_level', $level, ''];
        }

        $rows[] = ['status', 'Active', 'The course is visible on the website.'];
        $rows[] = ['status', 'Inactive', 'The course is saved but hidden.'];

        foreach (['show_in_popular', 'show_in_continue_learning', 'featured'] as $flag) {
            $rows[] = [$flag, 'Yes', ''];
            $rows[] = [$flag, 'No', ''];
        }

        return $rows;
    }
}
