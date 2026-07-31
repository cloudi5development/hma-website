<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

/**
 * Shared query helpers for content that is (a) toggleable active/inactive,
 * (b) ordered, and (c) shown on selected frontend pages. The frontend $page key
 * is a route name with the "frontend." prefix stripped (index, about-us, …).
 *
 * The page filter is called visibleOn() and NOT forPage(). Eloquent resolves an
 * unknown method against the model's local scopes before the base query builder,
 * so a scopeForPage() would shadow Eloquent's own forPage($page, $perPage) — the
 * method paginate() uses internally to apply LIMIT/OFFSET. The result was silent
 * and nasty: paginate() would add "where <page column> = 1" and skip the limit
 * entirely, so a listing showed the wrong rows while still reporting the right
 * total. Blog previously avoided the trait altogether just to sidestep this.
 */
trait HasPageVisibility
{
    /** Active rows, in display order. */
    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true)->orderBy('sort_order')->orderBy('id');
    }

    /** Only rows flagged for the given page. */
    public function scopeVisibleOn(Builder $q, string $page): Builder
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
