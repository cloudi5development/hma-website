<?php

namespace App\Services;

use App\Exceptions\FormImportException;
use App\Models\Form;
use App\Support\FormFieldType;
use App\Support\FormImportSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use Throwable;

/**
 * Bulk upload for the Form Builder: spreadsheet in, checked questions out.
 *
 * WHAT THIS CLASS DOES NOT DO IS WRITE ANYTHING.
 *
 * It reads the file, checks every row, and hands back a preview plus the
 * questions in order. The builder screen adds them to the section Bulk Upload
 * was opened from — pages and sections are made by hand, never by a sheet — with
 * the same code its + Add Question button runs, and they reach the database the
 * way every question does: through FormBuilderService::save(), in its
 * transaction, behind FormBuilderRequest's validation.
 *
 * That is deliberate, and it is what makes an imported question behave exactly
 * like a typed one — there is no second way into form_fields to drift out of
 * step with the first. It also means an import works on a form that has not
 * been created yet, lands among whatever the admin has on screen and not yet
 * saved, and can be looked over in place before anything is kept.
 *
 * Nothing in the file is trusted. The reader never calculates a formula, never
 * reads past the row and column ceilings, and treats every cell as plain text.
 */
class FormImportService
{
    /* ================================ READ ================================= */

    /**
     * The rows of a spreadsheet, keyed by column, with their Excel row numbers.
     *
     * @return array<int, array{row: int, cells: array<string, string>, formulas: array<string, bool>}>
     *
     * @throws FormImportException  with a message fit to show the admin
     */
    public function read(string $path, bool $quiz = false): array
    {
        try {
            // Only the two spreadsheet readers. Left to guess, PhpSpreadsheet
            // will happily read an HTML or CSV file that has been renamed .xlsx.
            $reader = IOFactory::createReader(IOFactory::identify($path, ['Xlsx', 'Xls']));
        } catch (Throwable) {
            throw new FormImportException('That file is not a readable Excel spreadsheet. Save it as .xlsx or .xls and upload it again.');
        }

        // Values only — no styles, and no formula is ever calculated. A cell
        // holding "=HYPERLINK(...)" is read as that text and refused below.
        $reader->setReadDataOnly(true);
        $reader->setReadEmptyCells(false);

        // Reads no further than one row past the ceiling, so a sheet with a
        // million rows is refused without a million rows being loaded to find
        // out. The extra row is how "too many" is noticed at all.
        $reader->setReadFilter(new class (FormImportSheet::MAX_ROWS + 2, FormImportSheet::MAX_COLUMNS) implements IReadFilter
        {
            public function __construct(private int $rows, private int $columns)
            {
            }

            public function readCell($columnAddress, $row, $worksheetName = ''): bool
            {
                return $row <= $this->rows && Coordinate::columnIndexFromString($columnAddress) <= $this->columns;
            }
        });

        try {
            // This form type's own sheet first, then the other template's, then
            // simply the first sheet of whatever workbook this is.
            $names = $reader->listWorksheetNames($path);

            foreach ([FormImportSheet::sheetName($quiz), FormImportSheet::sheetName(! $quiz)] as $name) {
                if (in_array($name, $names, true)) {
                    $reader->setLoadSheetsOnly([$name]);

                    break;
                }
            }

            $sheet = $reader->load($path)->getSheet(0);
        } catch (Throwable) {
            throw new FormImportException('That spreadsheet could not be opened. It may be damaged or password-protected.');
        }

        $columns = $this->columns($sheet);

        $missing = array_diff(FormImportSheet::REQUIRED_COLUMNS, $columns);

        if ($missing) {
            throw new FormImportException(
                'The sheet is missing the ' . $this->list(array_map(fn ($key) => '“' . FormImportSheet::heading($key, $quiz) . '”', $missing))
                . ' column' . (count($missing) === 1 ? '' : 's') . '. Download a fresh template and keep its headings as they are.',
            );
        }

        $rows    = [];
        $highest = min($sheet->getHighestDataRow(), FormImportSheet::MAX_ROWS + 2);

        for ($r = 2; $r <= $highest; $r++) {
            $cells    = [];
            $formulas = [];

            foreach ($columns as $index => $key) {
                $cell = $sheet->getCell(Coordinate::stringFromColumnIndex($index) . $r);
                $raw  = $cell->getValue();

                if ($cell->getDataType() === DataType::TYPE_FORMULA
                    || (is_string($raw) && str_starts_with(ltrim($raw), '='))) {
                    $formulas[$key] = true;
                }

                $cells[$key] = $this->text($raw);
            }

            // A row with nothing in it is a gap, not a question.
            if (implode('', $cells) === '' && ! $formulas) {
                continue;
            }

            $rows[] = ['row' => $r, 'cells' => $cells, 'formulas' => $formulas];
        }

        if ($rows === []) {
            throw new FormImportException('The sheet has no questions in it. Add one question per row, under the headings.');
        }

        if (count($rows) > FormImportSheet::MAX_ROWS) {
            throw new FormImportException(
                'That sheet has more than ' . FormImportSheet::MAX_ROWS . ' questions, which is the most one form can hold. Split it into smaller files.',
            );
        }

        return $rows;
    }

    /** Column index => column key, read from the heading row. The first of a repeated heading wins. */
    private function columns($sheet): array
    {
        $columns = [];

        for ($c = 1; $c <= FormImportSheet::MAX_COLUMNS; $c++) {
            $key = FormImportSheet::columnFor($this->text($sheet->getCell(Coordinate::stringFromColumnIndex($c) . '1')->getValue()));

            if ($key !== null && ! in_array($key, $columns, true)) {
                $columns[$c] = $key;
            }
        }

        return $columns;
    }

    /**
     * One cell as a single line of plain text.
     *
     * Whole numbers lose Excel's ".0" ("3" rather than "3.0"), booleans become
     * the words Excel shows, and control characters — including line breaks,
     * which a label cannot display — become spaces.
     */
    private function text(mixed $value): string
    {
        $text = match (true) {
            $value instanceof RichText               => $value->getPlainText(),
            is_bool($value)                          => $value ? 'TRUE' : 'FALSE',
            is_float($value) && floor($value) === $value && abs($value) < PHP_INT_MAX => (string) (int) $value,
            $value === null                          => '',
            default                                  => (string) $value,
        };

        return trim((string) preg_replace('/\s+/u', ' ', preg_replace('/[\x00-\x1F\x7F]/u', ' ', $text)));
    }

    /* =============================== PREVIEW =============================== */

    /**
     * Check every row, and hand back the questions in the order they will appear.
     *
     * $context describes the builder as it is on screen — which may not be saved:
     *
     *   form_type  standard | quiz  — a quiz needs a correct answer on each
     *                                Multiple choice question, and calls its
     *                                Label column "Question"
     *   questions  how many questions the builder already holds, so an import
     *              cannot take the form past its ceiling
     *
     * Where the questions go is not the server's business: every one of them is
     * added to the section Bulk Upload was opened from, after what is there.
     */
    public function preview(array $rows, array $context): array
    {
        $quiz      = ($context['form_type'] ?? null) === Form::QUIZ;
        $questions = max(0, (int) ($context['questions'] ?? 0));

        $checked = array_map(fn ($row) => $this->check($row, $quiz), $rows);

        $this->flagDuplicateOrders($checked);

        // The Order column decides the order, whatever order the rows are in.
        // A stable sort, with rows whose order is unusable kept at the end in
        // file order, so the preview still lists them where they can be found.
        foreach ($checked as $i => &$row) {
            $row['_position'] = $i;
        }
        unset($row);

        usort($checked, fn ($a, $b) => [$a['order'] === null, $a['order'] ?? 0, $a['_position']]
            <=> [$b['order'] === null, $b['order'] ?? 0, $b['_position']]);

        foreach ($checked as &$row) {
            unset($row['_position']);
        }
        unset($row);

        $valid  = array_values(array_filter($checked, fn ($row) => $row['errors'] === []));
        $errors = [];

        if ($questions + count($valid) > FormBuilderService::MAX_FIELDS) {
            $errors[] = 'This form already has ' . $questions . ' question' . ($questions === 1 ? '' : 's')
                . ', and a form can hold ' . FormBuilderService::MAX_FIELDS . '. Import at most '
                . max(0, FormBuilderService::MAX_FIELDS - $questions) . ' more.';
        }

        $invalid    = count($checked) - count($valid);
        $importable = $invalid === 0 && $errors === [] && $valid !== [];

        return [
            'summary' => [
                'total'      => count($checked),
                'valid'      => count($valid),
                'invalid'    => $invalid,
                'warnings'   => count(array_filter($checked, fn ($row) => $row['warnings'] !== [])),
                'importable' => $importable,
            ],
            'errors' => $errors,
            'rows'   => $checked,
            // Handed over only when there is nothing left to fix: the builder
            // cannot be given half a file.
            'fields' => $importable ? array_map(fn ($row) => $this->field($row), $valid) : [],
        ];
    }

    /* ================================ ROWS ================================= */

    private function check(array $source, bool $quiz): array
    {
        $cells    = $source['cells'];
        $cell     = fn (string $key) => (string) ($cells[$key] ?? '');
        $name     = fn (string $key) => FormImportSheet::heading($key, $quiz);   // "Question" on a quiz
        $errors   = [];
        $warnings = [];

        foreach (array_keys($source['formulas'] ?? []) as $key) {
            $errors[] = $name($key) . ' holds a formula. Type the text itself instead.';
        }

        /* ---- Order ---- */
        $order = null;

        if ($cell('order') === '') {
            $errors[] = 'Order is required.';
        } elseif (! preg_match('/^\d+$/', $cell('order')) || (int) $cell('order') < 1) {
            $errors[] = 'Order must be a whole number from 1 up (found “' . $cell('order') . '”).';
        } else {
            $order = (int) $cell('order');
        }

        /* ---- Label / Question ---- */
        $label = $cell('label');

        if ($label === '') {
            $errors[] = $name('label') . ' is required.';
        } elseif (mb_strlen($label) > 190) {
            $errors[] = $name('label') . ' is longer than 190 characters.';
        }

        /* ---- Type ---- */
        $type = FormImportSheet::typeFor($cell('type'));

        if ($cell('type') === '') {
            $errors[] = 'Type is required.';
        } elseif ($type === null) {
            $errors[] = 'Unsupported field type “' . $cell('type') . '”. Choose one from the Type list.';
        }

        /* ---- Required ---- */
        // Yes or No and nothing else — not Y, not TRUE, not 1. The template
        // offers exactly those two; anything else is a guess this should not
        // make on the admin's behalf.
        $required = match (strtolower($cell('required'))) {
            'yes'   => true,
            'no'    => false,
            default => null,
        };

        if ($required === null) {
            $errors[] = $cell('required') === ''
                ? 'Required must be Yes or No.'
                : 'Required must be Yes or No (found “' . $cell('required') . '”).';
        }

        /* ---- Placeholder, description ---- */
        // A quiz template has no Placeholder column; a sheet that brings one
        // anyway is read the same as on a standard form.
        if (mb_strlen($cell('placeholder')) > 190) {
            $errors[] = 'Placeholder is longer than 190 characters.';
        }

        if (mb_strlen($cell('description')) > 500) {
            $errors[] = 'Description is longer than 500 characters.';
        }

        /* ---- Options ---- */
        [$options, $gridRows, $gridColumns] = $this->choices($type, $cell('options'), $errors, $warnings);

        /* ---- Correct answer ---- */
        $answer = $cell('correct_answer');

        if ($type === FormFieldType::RADIO) {
            if ($answer === '' && $quiz) {
                $errors[] = 'Correct Answer is required — this form is a Quiz.';
            } elseif ($answer !== '' && str_contains($answer, FormImportSheet::OPTION_SEPARATOR)) {
                $errors[] = 'Only one Correct Answer is allowed for a Multiple choice question.';
            } elseif ($answer !== '' && $options !== [] && ! in_array($answer, $options, true)) {
                $errors[] = 'Correct Answer “' . $answer . '” does not exist in the provided options.';
            }
        } elseif ($answer !== '' && $type !== null) {
            $errors[] = 'Correct Answer only applies to Multiple choice questions, not ' . FormFieldType::label($type) . '.';
        }

        return [
            'row'            => $source['row'],
            'order'          => $order,
            'order_input'    => $cell('order'),
            'label'          => $label,
            'type'           => $type,
            'type_input'     => $cell('type'),
            'type_label'     => $type ? FormFieldType::label($type) : $cell('type'),
            'required'       => $required,
            'required_input' => $cell('required'),
            'placeholder'    => $cell('placeholder'),
            'description'    => $cell('description'),
            'options'        => $options,
            'grid_rows'      => $gridRows,
            'grid_columns'   => $gridColumns,
            'correct_answer' => $type === FormFieldType::RADIO && $answer !== '' ? $answer : null,
            'errors'         => $errors,
            'warnings'       => $warnings,
        ];
    }

    /**
     * A row's choices, checked for the type it declares.
     *
     * @return array{0: array<int, string>, 1: array<int, string>, 2: array<int, string>}
     */
    private function choices(?string $type, string $cell, array &$errors, array &$warnings): array
    {
        if ($type === null) {
            return [[], [], []];
        }

        // Yes / No brings its own two options, as it does in the builder.
        if ($type === FormFieldType::YES_NO) {
            if ($cell !== '') {
                $warnings[] = 'Yes / No makes its own two options, so the Options column was not used.';
            }

            return [array_column(FormFieldType::YES_NO_OPTIONS, 'label'), [], []];
        }

        if (FormFieldType::needsGrid($type)) {
            $grid = $cell === '' ? null : FormImportSheet::splitGrid($cell);

            if ($grid === null || $grid['rows'] === [] || $grid['columns'] === []) {
                $errors[] = FormFieldType::label($type) . ' needs rows and columns, written as “Rows: Product | Support ; Columns: Poor | Good”.';

                return [[], [], []];
            }

            $this->checkList($grid['rows'], 'Row', $errors);
            $this->checkList($grid['columns'], 'Column', $errors);

            return [[], $grid['rows'], $grid['columns']];
        }

        if (FormFieldType::needsOptions($type)) {
            $options = FormImportSheet::splitOptions($cell);

            if ($options === []) {
                $errors[] = 'Options are required for ' . FormFieldType::label($type) . ' — separate them with “|”, e.g. Male | Female | Other.';

                return [[], [], []];
            }

            $this->checkList($options, 'Option', $errors);

            return [$options, [], []];
        }

        if ($cell !== '') {
            $warnings[] = FormFieldType::label($type) . ' has no options, so the Options column was not used.';
        }

        return [[], [], []];
    }

    /** Duplicates, over-long entries and too many of them, in one list. */
    private function checkList(array $items, string $noun, array &$errors): void
    {
        if (count($items) > FormBuilderService::MAX_OPTIONS) {
            $errors[] = 'At most ' . FormBuilderService::MAX_OPTIONS . ' ' . strtolower($noun) . 's are allowed.';
        }

        $seen = [];

        foreach ($items as $item) {
            if (mb_strlen($item) > 190) {
                $errors[] = "{$noun} “" . mb_substr($item, 0, 40) . '…” is longer than 190 characters.';
            }

            // Case-insensitive: "PHP" and "php" would read as two choices and
            // be stored as one answer nobody could tell apart.
            $folded = mb_strtolower($item);

            if (isset($seen[$folded])) {
                $errors[] = "{$noun} “{$item}” is listed more than once.";
            }

            $seen[$folded] = true;
        }
    }

    /** Every row sharing an Order value is told which other rows share it. */
    private function flagDuplicateOrders(array &$rows): void
    {
        $byOrder = [];

        foreach ($rows as $i => $row) {
            if ($row['order'] !== null) {
                $byOrder[$row['order']][] = $i;
            }
        }

        foreach ($byOrder as $order => $indexes) {
            if (count($indexes) < 2) {
                continue;
            }

            $numbers = array_map(fn ($i) => $rows[$i]['row'], $indexes);

            foreach ($indexes as $i) {
                $rows[$i]['errors'][] = "Duplicate Order value {$order} found in rows " . $this->list($numbers) . '.';
            }
        }
    }

    /** A checked row, in the shape one question row of the builder posts. */
    private function field(array $row): array
    {
        return [
            'field_type'     => $row['type'],
            'label'          => $row['label'],
            'is_required'    => (bool) $row['required'],
            'placeholder'    => $row['placeholder'],
            'help_text'      => $row['description'],
            // Yes / No's two are made by the builder itself, exactly as when the
            // type is picked by hand, so they are not sent twice.
            'options'        => $row['type'] === FormFieldType::YES_NO ? [] : $row['options'],
            'rows'           => $row['grid_rows'],
            'columns'        => $row['grid_columns'],
            'correct_answer' => $row['correct_answer'],
        ];
    }

    /** "3", "3 and 7", "3, 7 and 9". */
    private function list(array $items): string
    {
        $items = array_values($items);

        return count($items) < 2
            ? (string) ($items[0] ?? '')
            : implode(', ', array_slice($items, 0, -1)) . ' and ' . end($items);
    }
}
