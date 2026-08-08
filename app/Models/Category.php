<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Category extends Model
{
    protected $fillable = [
        'department_id', 'name', 'slug', 'description', 'icon', 'tone',
        'sort_order', 'is_active', 'show_home', 'is_featured',
    ];

    protected $casts = [
        'department_id' => 'integer',
        'is_active'     => 'boolean',
        'show_home'     => 'boolean',
        'is_featured'   => 'boolean',
        'sort_order'    => 'integer',
    ];

    /** Pastel tones cycled through when a category has no explicit tone set. */
    public const TONES = ['red', 'purple', 'teal', 'pink', 'blue', 'gold', 'peach', 'green'];

    protected static function booted(): void
    {
        static::saving(function (Category $category) {
            if (blank($category->slug)) {
                $category->slug = $category->uniqueSlug(Str::slug($category->name));
            }
        });

    }

    /**
     * The card colour for the nth card in a rendered row of categories.
     *
     * Taken from the card's position rather than stored per category, because
     * the home grid is the only place a category is drawn in colour — and the
     * grid shows a filtered subset (show_home), so a value dealt out per row
     * would land out of sequence the moment one was toggled off. By position the
     * visible cards are always a clean run through the palette.
     */
    public static function toneForIndex(int $index): string
    {
        return static::TONES[$index % count(static::TONES)];
    }

    /**
     * The slug is derived from the name (there is no slug field on the form),
     * so guard the unique index by suffixing a counter on collisions.
     */
    private function uniqueSlug(string $base): string
    {
        $base = $base ?: 'category';
        $slug = $base;

        $taken = fn (string $candidate) => static::where('slug', $candidate)
            ->when($this->exists, fn ($q) => $q->whereKeyNot($this->getKey()))
            ->exists();

        for ($i = 2; $taken($slug); $i++) {
            $slug = $base . '-' . $i;
        }

        return $slug;
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function courses(): HasMany
    {
        return $this->hasMany(Course::class);
    }

    /** Active rows, in display order. */
    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true)->orderBy('sort_order')->orderBy('id');
    }

    /** Only categories flagged for the home "Top Categories" grid. */
    public function scopeOnHome(Builder $q): Builder
    {
        return $q->where('show_home', true);
    }

    /** Public URL for the icon glyph (seeded asset path or admin upload). */
    public function getIconUrlAttribute(): ?string
    {
        return $this->icon ? asset($this->icon) : null;
    }

    /**
     * Pastel tone for the card. Falls back to a deterministic pick from the
     * palette (by id) so every card is coloured even if none was chosen.
     */
    public function getToneValueAttribute(): string
    {
        return $this->tone ?: self::TONES[($this->id ?? 0) % count(self::TONES)];
    }

    /** Zero-padded course count label, e.g. "07 Courses" — matches the design. */
    public function getCourseCountLabelAttribute(): string
    {
        $count = $this->courses_count ?? $this->courses()->where('is_active', true)->count();

        return str_pad((string) $count, 2, '0', STR_PAD_LEFT) . ' Courses';
    }
}
