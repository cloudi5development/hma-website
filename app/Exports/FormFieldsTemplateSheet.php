<?php

namespace App\Exports;

use App\Support\FormImportSheet;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * "Form Fields" (or "Quiz Questions") — the sheet an admin fills in and the
 * importer reads. Which columns it has depends on the form type; see
 * FormImportSheet::columns().
 *
 * Type and Required are dropdowns, Order only takes whole numbers. The rules
 * are the same ones the importer applies, so a value Excel accepts is one the
 * upload will accept too; the importer still checks every cell, because a
 * dropdown in Excel is a suggestion that pasting walks straight past.
 *
 * TWO THINGS LEARNT BUILDING THE COURSE TEMPLATE, KEPT HERE:
 *
 * 1. setShowDropDown(TRUE) is what puts the arrow in the cell. PhpSpreadsheet
 *    writes the OOXML attribute inverted, and `showDropDown="1"` in the file
 *    means "hide it".
 * 2. The lists are written INLINE ("Yes,No"), never as a range on another sheet.
 *    Excel was seen resolving a perfectly valid range and still showing an
 *    empty list. The Type list is ~200 characters — inside Excel's 255 — and
 *    no label has a comma in it; if either stopped being true the list falls
 *    back to the Field Types sheet, which is visible, never hidden.
 */
class FormFieldsTemplateSheet implements FromArray, WithTitle, WithEvents
{
    public function __construct(private bool $quiz = false)
    {
    }

    public function title(): string
    {
        return FormImportSheet::sheetName($this->quiz);
    }

    public function array(): array
    {
        return array_merge([array_values($this->columns())], FormImportSheet::sampleRows($this->quiz));
    }

    /** This template's columns — Question and Correct Answer on a quiz, Label and Placeholder otherwise. */
    private function columns(): array
    {
        return FormImportSheet::columns($this->quiz);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet   = $event->sheet->getDelegate();
                // Every row of the sheet gets the dropdowns — there is no last
                // row a template is meant for.
                $last    = 1048576;
                $lastCol = $this->letter(array_key_last($this->columns()));

                // Headings stay on screen, and look like headings.
                $sheet->freezePane('A2');
                $header = $sheet->getStyle("A1:{$lastCol}1");
                $header->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
                $header->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('843D21');

                foreach (['order' => 8, 'label' => 40, 'type' => 22, 'required' => 11, 'placeholder' => 24,
                          'description' => 30, 'options' => 44, 'correct_answer' => 24] as $key => $width) {
                    if ($this->has($key)) {
                        $sheet->getColumnDimension($this->letter($key))->setWidth($width);
                    }
                }

                $sheet->setDataValidation("{$this->letter('type')}2:{$this->letter('type')}{$last}", $this->list(
                    $this->typeSource(),
                    'Type',
                    'Pick the kind of question from the list.',
                ));

                $sheet->setDataValidation("{$this->letter('required')}2:{$this->letter('required')}{$last}", $this->list(
                    '"Yes,No"',
                    'Required',
                    'Yes if people must answer it, No if they may skip it.',
                ));

                $order = $this->base('Order', 'A whole number. Lowest comes first; no two rows may share one.');
                $order->setType(DataValidation::TYPE_WHOLE);
                $order->setOperator(DataValidation::OPERATOR_GREATERTHANOREQUAL);
                $order->setFormula1('1');
                $sheet->setDataValidation("{$this->letter('order')}2:{$this->letter('order')}{$last}", $order);

                // The free-text columns with a format to follow get a hint. It
                // has to ride on a real rule — a validation of type "none" is
                // silently left out of the file by the writer — so it is a
                // length rule at 32,767, which is Excel's own most for a cell and
                // no limit of this module's: the importer takes any length.
                foreach ([
                    'label'          => [32767, 'The question people will read.'],
                    'options'        => [32767, 'Separate choices with |  e.g.  Male | Female | Other — or one per line (Alt+Enter).'],
                    'correct_answer' => [32767, 'Multiple choice only. Must match one of the Options exactly.'],
                ] as $key => [$max, $hint]) {
                    if (! $this->has($key)) {
                        continue;
                    }

                    $rule = $this->base($this->columns()[$key], $hint);
                    $rule->setType(DataValidation::TYPE_TEXTLENGTH);
                    $rule->setOperator(DataValidation::OPERATOR_LESSTHANOREQUAL);
                    $rule->setFormula1((string) $max);
                    $sheet->setDataValidation("{$this->letter($key)}2:{$this->letter($key)}{$last}", $rule);
                }
            },
        ];
    }

    /** Inline when Excel can take it; otherwise the visible Field Types sheet. */
    private function typeSource(): string
    {
        $labels = FormImportSheet::typeLabels();
        $inline = implode(',', $labels);

        $fits = strlen($inline) <= 250
            && ! array_filter($labels, fn ($label) => str_contains($label, ',') || str_contains($label, '"'));

        return $fits
            ? '"' . $inline . '"'
            : "'Field Types'!\$A\$2:\$A\$" . (count($labels) + 1);
    }

    private function list(string $source, string $title, string $prompt): DataValidation
    {
        $validation = $this->base($title, $prompt);

        $validation->setType(DataValidation::TYPE_LIST);
        $validation->setFormula1($source);
        // A list has nothing to be "between"; PhpSpreadsheet defaults the
        // operator anyway and Excel does not expect one on a list.
        $validation->setOperator('');

        return $validation;
    }

    private function base(string $title, string $prompt): DataValidation
    {
        $validation = new DataValidation();

        $validation->setErrorStyle(DataValidation::STYLE_STOP);
        $validation->setAllowBlank(true);
        $validation->setShowDropDown(true);   // TRUE shows the arrow — see the class comment
        $validation->setShowInputMessage(true);
        $validation->setShowErrorMessage(true);
        $validation->setPromptTitle($title);
        $validation->setPrompt($prompt);
        $validation->setErrorTitle("Not a valid {$title}");
        $validation->setError($prompt);

        return $validation;
    }

    private function has(string $key): bool
    {
        return array_key_exists($key, $this->columns());
    }

    private function letter(string $key): string
    {
        return chr(ord('A') + array_search($key, array_keys($this->columns()), true));
    }
}
