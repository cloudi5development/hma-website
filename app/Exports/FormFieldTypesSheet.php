<?php

namespace App\Exports;

use App\Support\FormFieldType;
use App\Support\FormImportSheet;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;

/**
 * "Field Types" — every type the builder offers.
 *
 * Built from App\Support\FormFieldType, so a type added to the registry is
 * listed here the next time the template is downloaded. Only the one-line
 * descriptions are written by hand; a type without one says which group it is in.
 */
class FormFieldTypesSheet implements FromArray, WithTitle, WithEvents
{
    private const DESCRIPTIONS = [
        FormFieldType::SHORT_TEXT   => 'A single line of text, such as a name.',
        FormFieldType::LONG_TEXT    => 'A longer answer over several lines.',
        FormFieldType::EMAIL        => 'An email address, checked as one.',
        FormFieldType::MOBILE       => 'A phone number.',
        FormFieldType::NUMBER       => 'A number.',
        FormFieldType::RADIO        => 'Pick exactly one option. Can have a Correct Answer.',
        FormFieldType::CHECKBOX     => 'Tick any number of options.',
        FormFieldType::DROPDOWN     => 'Pick one option from a drop-down list.',
        FormFieldType::YES_NO       => 'Yes or No. The two options are created for you.',
        FormFieldType::FILE         => 'Upload a file, e.g. a resume.',
        FormFieldType::LINEAR_SCALE => 'Pick a number on a scale (1 to 5 unless changed in the builder).',
        FormFieldType::RATING       => 'A star rating.',
        FormFieldType::MC_GRID      => 'One choice per row. Options: Rows: a | b ; Columns: x | y',
        FormFieldType::TICK_GRID    => 'Any number of choices per row. Options: Rows: a | b ; Columns: x | y',
        FormFieldType::HIDDEN       => 'Not shown to people. Carries a value the page sets.',
        FormFieldType::DATE         => 'A calendar date.',
        FormFieldType::TIME         => 'A time of day.',
        FormFieldType::DATETIME     => 'A date and a time together.',
    ];

    public function title(): string
    {
        return 'Field Types';
    }

    public function array(): array
    {
        $rows = [['Type (as in the dropdown)', 'Also accepted', 'Group', 'Needs Options', 'What it is']];

        foreach (FormFieldType::TYPES as $key => $spec) {
            $rows[] = [
                $spec['label'],
                FormImportSheet::typeAlias($key),
                $spec['group'],
                FormFieldType::needsOptions($key) && $key !== FormFieldType::YES_NO
                    ? 'Yes'
                    : (FormFieldType::needsGrid($key) ? 'Rows and columns' : 'No'),
                self::DESCRIPTIONS[$key] ?? $spec['group'] . ' question.',
            ];
        }

        return $rows;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $sheet->freezePane('A2');
                $sheet->getStyle('A1:E1')->getFont()->setBold(true);

                foreach (['A' => 26, 'B' => 24, 'C' => 14, 'D' => 18, 'E' => 70] as $column => $width) {
                    $sheet->getColumnDimension($column)->setWidth($width);
                }
            },
        ];
    }
}
