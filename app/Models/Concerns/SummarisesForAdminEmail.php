<?php

namespace App\Models\Concerns;

use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Turns a submission into the label/value rows the admin notification email
 * prints.
 *
 * The rows are read off the record itself rather than listed by hand, so a
 * column added to an enquiry later shows up in the email without anyone
 * remembering to add it here. A model only declares what cannot be guessed:
 * the columns that are the system's own business, and the few labels that
 * "Looking For" style title-casing gets wrong.
 */
trait SummarisesForAdminEmail
{
    /** Columns every submission carries for the panel, not for the reader. */
    private const HOUSEKEEPING = [
        'id', 'created_at', 'updated_at', 'status', 'is_read', 'ip_address',
    ];

    /**
     * Columns this model keeps out of the email, on top of the housekeeping
     * ones — typically a foreign key whose readable snapshot is also stored.
     *
     * @return array<int, string>
     */
    protected function adminEmailSkips(): array
    {
        return [];
    }

    /**
     * Labels that Str::headline() would get wrong.
     *
     * @return array<string, string>
     */
    protected function adminEmailLabels(): array
    {
        return [];
    }

    /**
     * Every answered field, in the order the record stores them.
     *
     * @return array<int, array{label: string, value: string}>
     */
    public function adminEmailRows(): array
    {
        $skip   = array_merge(self::HOUSEKEEPING, $this->adminEmailSkips());
        $labels = $this->adminEmailLabels();
        $rows   = [];

        foreach ($this->getAttributes() as $column => $raw) {
            if (in_array($column, $skip, true)) {
                continue;
            }

            $value = $this->adminEmailValue($this->{$column});

            // An unanswered optional question is left out rather than printed
            // as a blank row — the email should read as what was actually sent.
            if ($value === '') {
                continue;
            }

            $rows[] = [
                'label' => $labels[$column] ?? Str::headline($column),
                'value' => $value,
            ];
        }

        return $rows;
    }

    /** One stored value as the email should read it. */
    private function adminEmailValue(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        if ($value instanceof Carbon) {
            return $value->format('d M Y, g:i a');
        }

        if (is_array($value)) {
            return implode(', ', array_filter(array_map('strval', $value), 'strlen'));
        }

        return trim((string) $value);
    }

    /** When it arrived, for the line under the table. */
    public function adminEmailReceivedAt(): string
    {
        return optional($this->created_at)->format('d M Y, g:i a') ?? '';
    }
}
