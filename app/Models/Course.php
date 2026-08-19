<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Course extends Model
{
    protected $fillable = [
        'category_id', 'name', 'slug', 'image', 'brochure', 'batch_start_date', 'duration',
        'training_mode', 'skill_level', 'rating', 'short_description',
        'full_description', 'overview', 'learning_outcomes', 'prerequisites',
        'certification', 'audience', 'sort_order', 'is_active', 'is_popular',
        'is_continue_learning', 'is_featured',
        'meta_title', 'meta_description', 'meta_keywords',
    ];

    protected $casts = [
        'category_id'          => 'integer',
        'batch_start_date'     => 'date',
        'rating'               => 'decimal:1',
        'sort_order'           => 'integer',
        'is_active'            => 'boolean',
        'is_popular'           => 'boolean',
        'is_continue_learning' => 'boolean',
        'is_featured'          => 'boolean',
    ];

    /** A course may carry at most this many FAQs. */
    public const MAX_FAQS = 5;

    public const TRAINING_MODES = ['Online', 'Offline', 'Hybrid'];

    public const SKILL_LEVELS = ['Beginner', 'Intermediate', 'Advanced'];

    protected static function booted(): void
    {
        static::saving(function (Course $course) {
            if (blank($course->slug)) {
                $course->slug = Str::slug($course->name);
            }
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function faqs(): HasMany
    {
        return $this->hasMany(CourseFaq::class)->orderBy('sort_order')->orderBy('id');
    }

    /** Upcoming batches, soonest first — see Admin → Courses → Schedule. */
    public function schedules(): HasMany
    {
        return $this->hasMany(CourseSchedule::class)->orderBy('start_date')->orderBy('id');
    }

    /** Enquiries submitted for this course (newest first). */
    public function enquiries(): HasMany
    {
        return $this->hasMany(CourseEnquiry::class)->latest();
    }

    /** The category's department, hopped through the category relation. */
    public function department()
    {
        return $this->category?->department;
    }

    /** Active rows, in display order. */
    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true)->orderBy('sort_order')->orderBy('id');
    }

    public function scopePopular(Builder $q): Builder
    {
        return $q->where('is_popular', true);
    }

    public function scopeContinueLearning(Builder $q): Builder
    {
        return $q->where('is_continue_learning', true);
    }

    /** Public URL for the thumbnail (seeded asset path or admin upload). */
    public function getImageUrlAttribute(): ?string
    {
        return $this->image ? asset($this->image) : null;
    }

    /**
     * True when a brochure PDF has been uploaded AND the file is still there.
     *
     * Uploads live on the "public" disk and are stored as "storage/<path>", which
     * only resolves under public/ when the storage symlink exists — and that link
     * is gitignored, so plenty of hosts do not have it (see the storage fallback
     * route in routes/web.php). So the disk is asked directly, and only a seeded
     * /public asset path falls back to a filesystem check.
     */
    public function getHasBrochureAttribute(): bool
    {
        if (blank($this->brochure)) {
            return false;
        }

        return $this->brochureOnPublicDisk()
            ? \Illuminate\Support\Facades\Storage::disk('public')->exists($this->brochureDiskPath())
            : is_file(public_path($this->brochure));
    }

    /** Whether the stored path points at the "public" disk rather than /public. */
    public function brochureOnPublicDisk(): bool
    {
        return str_starts_with((string) $this->brochure, 'storage/');
    }

    /** The brochure's path relative to the "public" disk root. */
    public function brochureDiskPath(): string
    {
        return substr((string) $this->brochure, strlen('storage/'));
    }

    /**
     * Filename the visitor's browser saves the brochure as. The stored file has a
     * random name, which would download as "8f3c…pdf" and tell nobody anything.
     */
    public function getBrochureFilenameAttribute(): string
    {
        return \Illuminate\Support\Str::slug($this->name ?: 'course') . '-brochure.pdf';
    }

    /** Category name — the pill/badge text on the course card. */
    public function getBadgeAttribute(): string
    {
        return $this->category?->name ?? '';
    }
}
