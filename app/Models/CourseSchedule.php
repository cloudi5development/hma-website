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

    protected $fillable = [
        'course_id', 'start_date', 'end_date', 'duration',
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
     * Batches that have not started yet and have not finished — what the home
     * page is allowed to show. A batch with no end date is judged on its start
     * date alone.
     */
    public function scopeUpcoming(Builder $q): Builder
    {
        $today = Carbon::today()->toDateString();

        return $q->whereDate('start_date', '>=', $today)
            ->where(fn ($sub) => $sub->whereNull('end_date')->orWhereDate('end_date', '>=', $today));
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
