<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * The Form Builder's bulk-upload template — one for a standard form, one for a
 * quiz. See FormImportSheet::columns() for how the two differ, and why.
 *
 *   Form Fields / Quiz Questions   the grid the importer reads
 *   Instructions                   how to fill it in
 *   Field Types                    every type the builder offers
 *
 * Only the first is read back (FormImportService looks for it by name), so the
 * two reference sheets can never be mistaken for questions.
 */
class FormFieldsTemplateExport implements WithMultipleSheets
{
    use Exportable;

    public function __construct(private bool $quiz = false)
    {
    }

    public function sheets(): array
    {
        return [
            new FormFieldsTemplateSheet($this->quiz),
            new FormFieldsInstructionsSheet($this->quiz),
            new FormFieldTypesSheet(),
        ];
    }
}
