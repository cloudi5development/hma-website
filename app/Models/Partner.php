<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Partner extends Model
{
    protected $fillable = [
        'name', 'logo', 'sort_order', 'is_active',
        'show_home', 'show_about', 'show_testimonials',
    ];

    protected $casts = [
        'is_active'         => 'boolean',
        'show_home'         => 'boolean',
        'show_about'        => 'boolean',
        'show_testimonials' => 'boolean',
        'sort_order'        => 'integer',
    ];

    /**
     * Public URL for the logo. `logo` stores a path relative to /public, so this
     * works for both seeded assets (assets/images/...) and admin uploads
     * (storage/...) with no special-casing in the view.
     */
    public function getLogoUrlAttribute(): string
    {
        return asset($this->logo);
    }

    /** Active rows, in display order. */
    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * Only rows flagged to appear on the given page. $page is a frontend route
     * key: index | about-us | testimonials. Unknown pages fall back to home.
     *
     * Named visibleOn() and not forPage(): a scopeForPage() shadows Eloquent's own
     * forPage($page, $perPage), which paginate() calls internally — see the note
     * in Concerns\HasPageVisibility.
     */
    public function scopeVisibleOn(Builder $q, string $page): Builder
    {
        $column = match ($page) {
            'about-us'     => 'show_about',
            'testimonials' => 'show_testimonials',
            default        => 'show_home',
        };

        return $q->where($column, true);
    }
}
