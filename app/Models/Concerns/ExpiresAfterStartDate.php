<?php

namespace App\Models\Concerns;

use Illuminate\Support\Carbon;

/**
 * Content that is listed up to and including its start date, then switched off
 * automatically the day after — events and course batches.
 *
 * Expiring flips is_active off and stamps expired_at. The stamp is the whole
 * trick: the sweep only picks rows with no stamp, so an admin who re-ticks
 * "Active" on an expired row keeps it on, rather than the next sweep switching
 * it off again. Moving the start date to today or later clears the stamp, so
 * the row gets a fresh life and expires after its new date.
 *
 * The using model names its date column in expiryDateColumn().
 */
trait ExpiresAfterStartDate
{
    abstract public function expiryDateColumn(): string;

    public static function bootExpiresAfterStartDate(): void
    {
        static::saving(function (self $model) {
            $column = $model->expiryDateColumn();

            if ($model->expired_at && $model->isDirty($column) && $model->{$column}?->gte(Carbon::today())) {
                $model->expired_at = null;
            }
        });
    }

    /**
     * Switch off every active row whose start date is before today and that has
     * never expired. Returns how many rows were switched off.
     *
     * A bulk update, so the saving hook above does not run — it does not need to.
     */
    public static function expirePast(): int
    {
        $column = (new static)->expiryDateColumn();

        return static::query()
            ->where('is_active', true)
            ->whereNull('expired_at')
            ->whereNotNull($column)
            ->whereDate($column, '<', Carbon::today()->toDateString())
            ->update(['is_active' => false, 'expired_at' => now()]);
    }

    /** Switched off by the sweep and not yet brought back — badged "Expired" in the panel. */
    public function getIsExpiredAttribute(): bool
    {
        return ! $this->is_active && $this->expired_at !== null;
    }
}
