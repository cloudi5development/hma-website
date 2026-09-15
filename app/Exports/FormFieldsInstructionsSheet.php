<?php

namespace App\Exports;

use App\Support\FormImportSheet;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;

/** "Instructions" — how to fill in the grid sheet, for a standard form or a quiz. Never read on import. */
class FormFieldsInstructionsSheet implements FromArray, WithTitle, WithEvents
{
    public function __construct(private bool $quiz = false)
    {
    }

    public function title(): string
    {
        return 'Instructions';
    }

    public function array(): array
    {
        $sheet    = FormImportSheet::sheetName($this->quiz);
        $question = FormImportSheet::heading('label', $this->quiz);

        $steps = $this->quiz
            ? [
                "1.  Enter one question per row on the “{$sheet}” sheet, starting on row 2.",
                '2.  Order controls the order the questions appear in. Use whole numbers — no two rows may share one.',
                "3.  {$question} is the question people will read.",
                '4.  Choose a Type from the dropdown. Quiz questions are usually Multiple choice; the “Field Types” sheet lists them all.',
                '5.  Required must be Yes or No.',
                '6.  Use | to separate the options, e.g.  PHP | Laravel | React | Java',
                '7.  Every Multiple choice question needs a Correct Answer, and it must match one of its options exactly.',
                '     Only one correct answer is allowed per question.',
                '8.  Do not rename the column headers.',
            ]
            : [
                "1.  Enter one question per row on the “{$sheet}” sheet, starting on row 2.",
                '2.  Order controls the order the questions appear in. Use whole numbers — no two rows may share one.',
                '3.  Choose a Type from the dropdown. The “Field Types” sheet explains each one.',
                '4.  Required must be Yes or No.',
                '5.  Placeholder is the grey hint shown inside a text box before anyone types. Leave it blank if you do not need one.',
                '6.  Use | to separate multiple options, e.g.  Male | Female | Other',
                '7.  Do not rename the column headers.',
            ];

        return array_merge(
            [['How to fill in this template'], ['']],
            array_map(fn ($line) => [$line], $steps),
            [
                [''],
                ['Where the questions go'],
                ['     Into the section you opened Bulk Upload from, after the questions already in it.'],
                ['     Create pages and sections on the builder first, then upload into each one.'],
                [''],
                ['Grids'],
                ['     Multiple-choice grid and Tick box grid need rows and columns. Write them in Options as:'],
                ['     Rows: Product | Support ; Columns: Poor | Good | Excellent'],
                [''],
                ['Limits'],
                ['     Up to ' . FormImportSheet::MAX_ROWS . " questions per file. {$question} up to 190 characters"
                    . ($this->quiz ? '.' : ', Description up to 500.')],
                ['     Type plain text only — cells containing formulas are refused.'],
                [''],
                ['Nothing is saved when you upload. You will see every row checked first, and the questions are added'],
                ['to your form only when you confirm. Save the form afterwards to keep them.'],
            ],
        );
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $sheet->getColumnDimension('A')->setWidth(115);
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

                // The sub-headings, wherever they fall for this template.
                foreach ($sheet->getRowIterator() as $row) {
                    $cell = $sheet->getCell('A' . $row->getRowIndex());

                    if (in_array($cell->getValue(), ['Where the questions go', 'Grids', 'Limits'], true)) {
                        $cell->getStyle()->getFont()->setBold(true)->setSize(12);
                    }
                }
            },
        ];
    }
}
