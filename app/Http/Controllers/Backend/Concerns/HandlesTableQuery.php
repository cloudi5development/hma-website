<?php

namespace App\Http\Controllers\Backend\Concerns;

use Illuminate\Database\Eloquent\Builder;

/**
 * Shared behaviour for the toolbar that sits above every admin table:
 * a keyword search, an Active/Hidden filter and a "Show N entries" length menu.
 * Controllers pass the columns their list should be searchable by.
 */
trait HandlesTableQuery
{
    /**
     * Apply the toolbar's search box and status filter to a list query.
     *
     * $statusColumn may be null for a list whose status is not the usual
     * is_active boolean — Forms has a three-way string status it filters itself.
     * The body already skipped on a falsy value; the signature now says so.
     */
    protected function applyTableFilters(Builder $query, array $searchable = [], ?string $statusColumn = 'is_active'): Builder
    {
        $term = trim((string) request('q'));

        if ($term !== '' && $searchable) {
            $query->where(function (Builder $q) use ($searchable, $term) {
                foreach ($searchable as $column) {
                    // Dot notation targets a relation, e.g. "category.name".
                    if (str_contains($column, '.')) {
                        [$relation, $field] = explode('.', $column, 2);
                        $q->orWhereHas($relation, fn (Builder $r) => $r->where($field, 'like', "%{$term}%"));
                    } else {
                        $q->orWhere($column, 'like', "%{$term}%");
                    }
                }
            });
        }

        $status = request('status');

        if ($statusColumn && in_array($status, ['active', 'inactive'], true)) {
            $query->where($statusColumn, $status === 'active');
        }

        return $query;
    }

    /** Page size from the toolbar, clamped to the offered options. */
    protected function perPage(): int
    {
        $options   = config('admin.per_page_options', [10]);
        $requested = (int) request('per_page');

        return in_array($requested, $options, true) ? $requested : $options[0];
    }
}
