<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One upcoming batch of a course — the rows behind "Upcoming Course Schedules"
 * on the home page and the /schedules listing. Managed in Admin → Courses →
 * Schedule, which owns the whole set across every course.
 */
class CourseSchedule extends Model
{
    /** How many batches the home-page section shows before "View All Schedules". */
    public const MAX_HOME = 4;

    /**
     * How the batch is taught. Lived on the course until 2026-08-19, which meant
     * one mode for every intake — an evening online batch and a weekend campus
     * batch of the same course could not both be described. It belongs to the
     * batch, so it lives here.
     */
    public const TRAINING_MODES = ['Online', 'Offline', 'Hybrid'];

    protected $fillable = [
        'course_id', 'start_date', 'end_date', 'duration', 'training_mode',
        'start_time', 'end_time', 'fee', 'show_fee', 'is_active',
    ];

    // The times are deliberately NOT cast to a date type: 'datetime' would
    // resolve a bare "10:00" against today and start printing a date with it.
    // They are stored and read as the plain "HH:MM:SS" strings MySQL keeps, and
    // formatted through the label accessors below.
    protected $casts = [
        'course_id'  => 'integer',
        'start_date' => 'date',
        'end_date'   => 'date',
        'fee'        => 'decimal:2',
        'show_fee'   => 'boolean',
        'is_active'  => 'boolean',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true);
    }

    /**
     * Batches the site is allowed to list: anything that has not finished.
     *
     * A batch that has already started but is still running counts — it is what
     * an admin means by "upcoming" when they enter a course running to the end
     * of September. The earlier rule required the start date to be in the future
     * too, which quietly hid a live batch the day it began.
     *
     * Without an end date there is nothing to expire against, so such a batch
     * falls back to the old rule and drops off the day it starts.
     *
     * Kept in step with $shows_on_site below — change one, change the other.
     */
    public function scopeUpcoming(Builder $q): Builder
    {
        $today = Carbon::today()->toDateString();

        return $q->where(fn ($sub) => $sub
            ->whereDate('end_date', '>=', $today)
            ->orWhere(fn ($noEnd) => $noEnd
                ->whereNull('end_date')
                ->whereDate('start_date', '>=', $today)));
    }

    /**
     * Whether the website will actually list this batch — the row-level twin of
     * scopeUpcoming, plus the two active flags the queries also apply.
     *
     * The panel badges rows with this rather than re-deriving the rule, so the
     * listing can never claim a batch is live while the site leaves it out. That
     * mismatch is what sent the admin looking for a bug on 2026-08-13.
     */
    public function getShowsOnSiteAttribute(): bool
    {
        if (! $this->is_active || ! $this->course?->is_active) {
            return false;
        }

        $today = Carbon::today();

        return $this->end_date
            ? $this->end_date->gte($today)
            : $this->start_date->gte($today);
    }

    /** Started, but not finished — listed on the site, and badged differently. */
    public function getIsRunningAttribute(): bool
    {
        return $this->shows_on_site && $this->start_date->lt(Carbon::today());
    }

    /** "20 Aug 2026" — the first line of a date cell. */
    public function getStartDateLabelAttribute(): string
    {
        return $this->start_date->format('d M Y');
    }

    /** "Thursday" — the muted second line under it. */
    public function getStartDayLabelAttribute(): string
    {
        return $this->start_date->format('l');
    }

    public function getEndDateLabelAttribute(): ?string
    {
        return $this->end_date?->format('d M Y');
    }

    public function getEndDayLabelAttribute(): ?string
    {
        return $this->end_date?->format('l');
    }

    /** "10:00 AM", or null when no start time was entered. */
    public function getStartTimeLabelAttribute(): ?string
    {
        return $this->timeLabel($this->start_time);
    }

    /** "01:30 PM", or null when no end time was entered. */
    public function getEndTimeLabelAttribute(): ?string
    {
        return $this->timeLabel($this->end_time);
    }

    /**
     * The batch's daily timing as one string — "10:00 AM – 01:30 PM", or just
     * the start when there is no end, or null when neither was entered. Times
     * are optional, so every caller has to cope with all three cases; doing it
     * once here keeps that out of the views.
     */
    public function getTimeRangeLabelAttribute(): ?string
    {
        $start = $this->start_time_label;
        $end   = $this->end_time_label;

        if ($start && $end) {
            return $start . ' – ' . $end;
        }

        return $start ?: $end;
    }

    /** "HH:MM" for the admin form's <input type="time">. */
    public function getStartTimeInputAttribute(): ?string
    {
        return blank($this->start_time) ? null : substr($this->start_time, 0, 5);
    }

    public function getEndTimeInputAttribute(): ?string
    {
        return blank($this->end_time) ? null : substr($this->end_time, 0, 5);
    }

    /**
     * "10:00 AM" from what the column holds. MySQL hands a TIME back as
     * "HH:MM:SS"; a value that has not been through the database yet (old input
     * on a failed submit) is the "HH:MM" the form posted, so both are accepted.
     */
    private function timeLabel(?string $time): ?string
    {
        if (blank($time)) {
            return null;
        }

        [$hour, $minute] = array_pad(explode(':', $time), 2, '0');

        return Carbon::createFromTime((int) $hour, (int) $minute)->format('h:i A');
    }

    /**
     * The fee exactly as the section should print it, or null when the admin
     * has hidden it or never entered one — the caller then shows "Contact for
     * Fee" rather than ₹0, N/A or a gap.
     */
    public function getFeeLabelAttribute(): ?string
    {
        if (! $this->show_fee || $this->fee === null) {
            return null;
        }

        $fee = (float) $this->fee;

        // Whole rupees lose the ".00"; anything with paise keeps both digits.
        return '₹' . number_format($fee, fmod($fee, 1) === 0.0 ? 0 : 2);
    }
}
