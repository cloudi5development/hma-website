<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;

/**
 * A form's responses as .xlsx — ArrayExport, with every answer written as what
 * it is: text a visitor typed.
 *
 * The default binder guesses a type from the text, which is wrong for answers
 * in two ways that matter. Anything starting with "=" is stored as a FORMULA —
 * so a public visitor could put =HYPERLINK(...) into the admin's spreadsheet,
 * to run when it is opened. And "+919876543210" is read as a number: the phone
 * loses its "+" and shows as 9.19877E+11. A plain number ("25", "4.5") still
 * goes in as a number, so a numeric question can be summed.
 */
class FormResponsesExport extends ArrayExport implements WithCustomValueBinder
{
    public function bindValue(Cell $cell, $value): bool
    {
        if (is_string($value) && ! preg_match('/^-?(0|[1-9]\d{0,14})(\.\d+)?$/', $value)) {
            $cell->setValueExplicit($value, DataType::TYPE_STRING);

            return true;
        }

        return (new DefaultValueBinder())->bindValue($cell, $value);
    }
}
