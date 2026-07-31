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
        'title', 'slug', 'excerpt', 'content', 'image', 'author',
        'category', 'published_at', 'sort_order', 'is_active',
        'show_home', 'is_latest',
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
