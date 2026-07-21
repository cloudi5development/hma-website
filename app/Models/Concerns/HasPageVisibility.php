<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

/**
 * Shared query helpers for content that is (a) toggleable active/inactive,
 * (b) ordered, and (c) shown on selected frontend pages. The frontend $page key
 * is a route name with the "frontend." prefix stripped (index, about-us, …).
 */
trait HasPageVisibility
{
    /** Active rows, in display order. */
    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true)->orderBy('sort_order')->orderBy('id');
    }

    /** Only rows flagged for the given page. */
    public function scopeForPage(Builder $q, string $page): Builder
    {
        return $q->where($this->pageColumn($page), true);
    }

    /** Map a page key to its visibility column. Models may override to add pages. */
    protected function pageColumn(string $page): string
    {
        return match ($page) {
            'about-us'     => 'show_about',
            'testimonials' => 'show_testimonials',
            default        => 'show_home',   // index, contact-us, … fall back to home
        };
    }
}
