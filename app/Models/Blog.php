<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Blog extends Model
{
    // NOTE: intentionally NOT using HasPageVisibility — it carries page-visibility
    // helpers this model does not need (it has forHome()/forLatest() instead). The
    // clash that used to make the trait unsafe here is gone: its page filter was
    // renamed to visibleOn() so it no longer shadows Eloquent's forPage().

    protected $fillable = [
        'title', 'slug', 'excerpt', 'content', 'image', 'image_alt', 'author',
        'category', 'published_at', 'sort_order', 'is_active',
        'show_home', 'is_latest',
        'meta_title', 'meta_description', 'meta_keywords',
    ];

    protected $casts = [
        'published_at' => 'date',
        'is_active'    => 'boolean',
        'show_home'    => 'boolean',
        'is_latest'    => 'boolean',
        'sort_order'   => 'integer',
    ];

    /** Active posts, in display order. */
    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * The categories the posts themselves use, for the listing's filter.
     *
     * Read from the posts rather than kept as a list somewhere: a category is
     * free text on the post, so the only categories that can be chosen are the
     * ones that would actually return something.
     *
     * Deliberately NOT built on scopeActive(): that orders by sort_order, and
     * MySQL refuses an ORDER BY on a column a SELECT DISTINCT does not carry.
     *
     * @return \Illuminate\Support\Collection<int, string>
     */
    public static function categories(): \Illuminate\Support\Collection
    {
        return static::query()
            ->where('is_active', true)
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');
    }

    /** Posts flagged for the home "Latest Blog" grid. */
    public function scopeForHome(Builder $q): Builder
    {
        return $q->where('show_home', true);
    }

    /** Posts flagged for the blog-details "The Latest" sidebar.
     *  Named forLatest() — NOT latest() — because Eloquent already ships a
     *  latest() query method (orderBy created_at desc) that would shadow it. */
    public function scopeForLatest(Builder $q): Builder
    {
        return $q->where('is_latest', true);
    }

    /** Public URL for the image (seeded asset path or admin upload). */
    public function getImageUrlAttribute(): string
    {
        return asset($this->image);
    }

    /**
     * Alt text for the cover image, used on the card grids and the article
     * hero. The admin writes it on the post; left blank it falls back to the
     * title, which is what every one of those images used before the field
     * existed.
     */
    public function getImageAltTextAttribute(): string
    {
        return trim((string) $this->image_alt) ?: (string) $this->title;
    }

    /** "20 July, 2024" — matches the design's date styling. */
    public function getDisplayDateAttribute(): string
    {
        return optional($this->published_at)->format('d F, Y') ?? '';
    }

    /** "2024-07-20" — the machine-readable <time datetime> value. */
    public function getIsoDateAttribute(): string
    {
        return optional($this->published_at)->format('Y-m-d') ?? '';
    }
}
