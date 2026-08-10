<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One upcoming batch of a course — the rows behind "Upcoming Course Schedules"
 * on the home page. Created inside Admin → Courses → Create/Edit Course, not in
 * a module of its own.
 */
class CourseSchedule extends Model
{
    /** How many batches the home-page section shows before "View All Schedules". */
    public const MAX_HOME = 4;

    protected $fillable = [
        'course_id', 'start_date', 'end_date', 'duration',
        'fee', 'show_fee', 'is_active',
    ];

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
