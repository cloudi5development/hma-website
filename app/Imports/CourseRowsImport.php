<?php

namespace App\Imports;

use App\Support\CourseImportTemplate;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\HeadingRowImport;

/**
 * Reads the spreadsheet and hands back plain heading-keyed rows. It does not
 * validate and it does not write — CourseBulkImportService does both.
 *
 * Kept apart on purpose: the file is read twice (once to preview, once to
 * confirm), and the reader is the only part that has to change if the sheet
 * format ever does.
 *
 * WithChunkReading is what keeps a 1,000-row file off the heap: PhpSpreadsheet
 * loads CHUNK rows at a time rather than inflating the whole workbook, which for
 * a wide sheet like this one is the difference between a few MB and hundreds.
 */
class CourseRowsImport implements ToCollection, WithHeadingRow, WithChunkReading, WithMultipleSheets
{
    /** Rows held in memory at once. */
    private const CHUNK = 250;

    /**
     * Hard ceiling on rows read from one file.
     *
     * Not a business limit so much as a guard: it stops a malformed or hostile
     * upload from turning into an unbounded read. Comfortably above the
     * "1,000+ courses" this feature is built for.
     */
    public const MAX_ROWS = 5000;

    /** @var array<int, array{line:int, data:array}> */
    private array $rows = [];

    /** Headings actually present in the file, from the first chunk. */
    private array $headings = [];

    private bool $headingsSeen = false;

    /**
     * The heading row is row 1, so a data row's spreadsheet line number is its
     * offset + 2. Tracked as an absolute counter because collection() is called
     * once per chunk, each starting from index 0 again.
     */
    private int $consumed = 0;

    /** Data rows seen past MAX_ROWS. */
    private int $truncated = 0;

    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            $data = $row->toArray();

            if (! $this->headingsSeen) {
                $this->headings = array_keys($data);
                $this->headingsSeen = true;
            }

            $this->consumed++;

            // A row where every cell is empty is spacing, not a course. Skipped
            // silently rather than reported as 30 missing fields.
            if ($this->isBlankRow($data)) {
                continue;
            }

            // Enforced here rather than through WithLimit, which fights chunk
            // reading over the read filter. Rows past the cap are counted so the
            // admin is told what was left out — never silently dropped.
            if (count($this->rows) >= self::MAX_ROWS) {
                $this->truncated++;
                continue;
            }

            $this->rows[] = [
                'line' => $this->consumed + 1,   // +1 for the heading row
                'data' => $data,
            ];
        }
    }

    /**
     * Read the FIRST sheet only.
     *
     * Without this, an import reads every sheet in the workbook — and the
     * template ships with Instructions and Allowed Values behind the Courses
     * grid, so a straight upload of the downloaded file reported some seventy
     * phantom rows, each "missing" every required column.
     *
     * Keyed by index rather than by the name "Courses" on purpose: a CSV has one
     * unnamed sheet, and an admin who renames the tab should still be able to
     * upload. The first sheet is the grid in every case.
     */
    public function sheets(): array
    {
        return [0 => $this];
    }

    public function chunkSize(): int
    {
        return self::CHUNK;
    }

    /** Data rows past MAX_ROWS that were not read. */
    public function truncatedCount(): int
    {
        return $this->truncated;
    }

    /** @return Collection<int, array{line:int, data:array}> */
    public function rows(): Collection
    {
        return collect($this->rows);
    }

    /** The column headings the file actually carries. */
    public function headings(): array
    {
        return $this->headings;
    }

    /**
     * Supply the headings when there were no data rows to learn them from.
     *
     * collection() only sees rows, so a file with a perfectly good heading row
     * and nothing under it looked to this reader like a file with no columns at
     * all — and was rejected as "missing every required column" instead of the
     * truthful "no course rows". A blank template is exactly that file.
     */
    public function applyFallbackHeadings(array $headings): void
    {
        if (! $this->headingsSeen) {
            $this->headings = $headings;
            $this->headingsSeen = true;
        }
    }

    /**
     * Read just the heading row of the first sheet, without the data.
     *
     * Laravel Excel's own heading reader returns one entry per sheet; the grid
     * is always the first, matching sheets() above.
     */
    public static function headingsOf(string $path, ?string $disk = null): array
    {
        $sheets = (new HeadingRowImport(1))->toArray($path, $disk);
        $first  = $sheets[0][0] ?? [];

        return array_values(array_filter(
            array_map(fn ($heading) => is_string($heading) ? $heading : (string) $heading, $first),
            fn ($heading) => $heading !== '',
        ));
    }

    /**
     * Required columns the file is missing.
     *
     * A file with no heading row at all reads as "everything missing", which is
     * the right answer — the importer cannot map a single cell without one.
     */
    public function missingHeadings(): array
    {
        return array_values(array_diff(
            CourseImportTemplate::requiredHeadings(),
            $this->headings,
        ));
    }

    private function isBlankRow(array $data): bool
    {
        foreach ($data as $value) {
            if (! CourseImportTemplate::isBlank($value)) {
                return false;
            }
        }

        return true;
    }
}
