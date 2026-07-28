<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Str;

class Department extends Model
{
    protected $fillable = [
        'name', 'slug', 'description', 'image', 'sort_order', 'is_active',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'sort_order' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saving(function (Department $department) {
            if (blank($department->slug)) {
                $department->slug = $department->uniqueSlug(Str::slug($department->name));
            }
        });
    }

    /**
     * The slug is derived from the name (there is no slug field on the form),
     * so guard the unique index by suffixing a counter on collisions.
     */
    private function uniqueSlug(string $base): string
    {
        $base = $base ?: 'department';
        $slug = $base;

        $taken = fn (string $candidate) => static::where('slug', $candidate)
            ->when($this->exists, fn ($q) => $q->whereKeyNot($this->getKey()))
            ->exists();

        for ($i = 2; $taken($slug); $i++) {
            $slug = $base . '-' . $i;
        }

        return $slug;
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    public function courses(): HasManyThrough
    {
        return $this->hasManyThrough(Course::class, Category::class);
    }

    /** Active rows, in display order. */
    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true)->orderBy('sort_order')->orderBy('id');
    }

    /** Public URL for the mega-menu image (seeded asset path or admin upload). */
    public function getImageUrlAttribute(): ?string
    {
        return $this->image ? asset($this->image) : null;
    }
}
