<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

/**
 * A tiny, reusable Excel export — hand it the column headings and the row data
 * and it produces a formatted .xlsx. Used by both enquiry modules.
 */
class ArrayExport implements FromArray, WithHeadings, ShouldAutoSize
{
    public function __construct(
        private array $headings,
        private array $rows,
    ) {
    }

    public function headings(): array
    {
        return $this->headings;
    }

    public function array(): array
    {
        return $this->rows;
    }
}
