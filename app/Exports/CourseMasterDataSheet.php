<?php

namespace App\Exports;

use App\Support\CourseImportTemplate;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\NamedRange;

/**
 * Where the Courses dropdowns get their values.
 *
 * Two ways of feeding a dropdown, and the short one is strongly preferred:
 *
 *   INLINE   the values live in the validation itself — `"Online,Offline,Hybrid"`.
 *            Nothing to reference, nothing to resolve, works in every version of
 *            Excel, LibreOffice and Google Sheets.
 *
 *   RANGE    the values live on this sheet and the validation points at a named
 *            range. Only used when a list is too long for Excel's 255-character
 *            inline limit, or contains a comma (which would split it).
 *
 * The first attempt at this used a range for everything, sourced from a HIDDEN
 * sheet. Every part of the file was correct — the names, the scope, the values —
 * and Excel still drew each dropdown as an arrow over 400 blank rows: it
 * resolved the range but read nothing from it. Inline lists have no reference to
 * get wrong, so that whole class of failure disappears. When a range genuinely is
 * needed, this sheet is left VISIBLE for the same reason.
 */
class CourseMasterDataSheet implements FromArray, WithTitle, WithEvents, ShouldAutoSize
{
    public const SHEET = 'Master Data';

    /**
     * Longest joined list that still fits an inline validation.
     *
     * Excel's own cap is 255 characters for the whole formula; 250 leaves room
     * for the wrapping quotes rather than sitting exactly on the edge.
     */
    private const MAX_INLINE = 250;

    /** How many rows a named range reserves, so it never runs short. */
    private const CAPACITY = 400;

    /** Cached so the range helper and the sheet body agree on column order. */
    private static ?array $lists = null;

    public function title(): string
    {
        return self::SHEET;
    }

    /**
     * What a column's validation should point at: an inline list where it fits,
     * otherwise the name of a range on this sheet.
     */
    public static function sourceFor(string $column): ?string
    {
        $lists = self::lists();

        if (! isset($lists[$column])) {
            return null;
        }

        return self::inlineFor($lists[$column]) ?? self::nameFor($column);
    }

    /** True when at least one list is too long or too awkward to inline. */
    public static function needsSheet(): bool
    {
        foreach (self::lists() as $values) {
            if (self::inlineFor($values) === null) {
                return true;
            }
        }

        return false;
    }

    /** True when THIS column falls back to the sheet. */
    public static function usesRange(string $column): bool
    {
        $lists = self::lists();

        return isset($lists[$column]) && self::inlineFor($lists[$column]) === null;
    }

    /**
     * The values as an inline Excel list, or null when they will not fit.
     *
     * A comma inside a value is fatal — it is the separator, so "Sales, B2B"
     * would silently become two options.
     */
    private static function inlineFor(array $values): ?string
    {
        foreach ($values as $value) {
            if (str_contains((string) $value, ',')) {
                return null;
            }
        }

        $joined = implode(',', $values);

        return strlen($joined) <= self::MAX_INLINE ? '"' . $joined . '"' : null;
    }

    /** The workbook-level name a long list is published under. */
    public static function nameFor(string $column): ?string
    {
        return array_key_exists($column, self::lists()) ? 'hm_' . $column : null;
    }

    /** The absolute range that name resolves to, e.g. `$A$2:$A$401`. */
    private static function rangeFor(string $column): ?string
    {
        $index = array_search($column, array_keys(self::lists()), true);

        if ($index === false) {
            return null;
        }

        $letter = Coordinate::stringFromColumnIndex($index + 1);

        return sprintf('$%s$2:$%s$%d', $letter, $letter, self::CAPACITY + 1);
    }

    /**
     * Column 1 = first list, column 2 = second, and so on. Row 1 holds the
     * column name so the sheet reads sensibly — it is visible, after all.
     */
    public function array(): array
    {
        $lists = self::lists();
        $rows  = [array_keys($lists)];
        $depth = max(array_map('count', $lists) ?: [0]);

        for ($i = 0; $i < $depth; $i++) {
            $row = [];

            foreach ($lists as $values) {
                $row[] = $values[$i] ?? null;
            }

            $rows[] = $row;
        }

        return $rows;
    }

    /** @return array<string, array<int, string>> */
    private static function lists(): array
    {
        return self::$lists ??= CourseImportTemplate::dropdownLists();
    }

    /** Forget the cached lists — categories change between downloads. */
    public static function flush(): void
    {
        self::$lists = null;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Names for the columns that actually need one. Declared here
                // because this sheet is written last, so it is the first point
                // at which a Worksheet exists to attach them to.
                foreach (array_keys(self::lists()) as $column) {
                    if (! self::usesRange($column)) {
                        continue;
                    }

                    $name  = self::nameFor($column);
                    $range = self::rangeFor($column);

                    if ($name && $range) {
                        $sheet->getParent()->addNamedRange(new NamedRange($name, $sheet, $range));
                    }
                }
            },
        ];
    }
}
